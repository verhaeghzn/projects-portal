<?php

namespace App\Services\Saml;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OneLogin\Saml2\IdPMetadataParser;
use OneLogin\Saml2\Utils;

class SurfIdpCertificateLoader
{
    /**
     * Load SURF IdP signing certificate(s) for SAML response validation.
     *
     * @return array{
     *     x509cert?: string,
     *     x509certMulti?: array{signing: string[]},
     *     certFingerprint?: string,
     *     certFingerprintAlgorithm?: string,
     *     source?: string
     * }
     */
    public static function load(): array
    {
        self::$lastSource = 'none';

        $fromMetadata = self::loadFromMetadata();

        if ($fromMetadata !== null) {
            self::$lastSource = 'metadata';

            return self::withFingerprint($fromMetadata);
        }

        $fromPem = self::loadFromAssertionSigningPem();

        if ($fromPem !== null) {
            self::$lastSource = 'pem_url';

            return self::withFingerprint($fromPem);
        }

        $fromFile = self::loadFromFile();

        if ($fromFile !== []) {
            self::$lastSource = 'file';

            return self::withFingerprint($fromFile);
        }

        return [];
    }

    public static function lastSource(): string
    {
        return self::$lastSource;
    }

    private static string $lastSource = 'none';

    public static function clearCache(): void
    {
        $metadataUrl = (string) config('saml.surf.metadata_url');
        $entityId = (string) config('saml.surf.entity_id');
        Cache::forget(self::cacheKey($metadataUrl, $entityId));
    }

    /**
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}|null
     */
    protected static function loadFromMetadata(): ?array
    {
        $metadataUrl = config('saml.surf.metadata_url');
        $entityId = config('saml.surf.entity_id');

        if (empty($metadataUrl)) {
            return null;
        }

        try {
            return Cache::remember(
                self::cacheKey((string) $metadataUrl, (string) $entityId),
                (int) config('saml.idp_metadata_cache_ttl', 86400),
                fn () => self::parseMetadataUrl((string) $metadataUrl, (string) $entityId),
            );
        } catch (\Throwable $e) {
            Log::warning('SAML: Could not load IdP certificates from metadata', [
                'url' => $metadataUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}|null
     */
    protected static function loadFromAssertionSigningPem(): ?array
    {
        $pemUrl = (string) config('saml.surf.assertion_signing_cert_url');
        if ($pemUrl === '') {
            return null;
        }

        try {
            $pem = self::fetchUrl($pemUrl);
            $certs = self::parsePemCertificates($pem);

            if ($certs === []) {
                return null;
            }

            return self::certsToIdpConfig($certs);
        } catch (\Throwable $e) {
            Log::warning('SAML: Could not load SURF assertion signing PEM', [
                'url' => $pemUrl,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected static function fetchUrl(string $url): string
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException($error);
        }
        curl_close($ch);

        return $body;
    }

    /**
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    public static function parseMetadataUrl(string $metadataUrl, string $entityId): array
    {
        $parsed = IdPMetadataParser::parseRemoteXML($metadataUrl, $entityId ?: null);
        $idp = $parsed['idp'] ?? [];

        if (empty($idp['x509cert']) && empty($idp['x509certMulti']['signing'] ?? null)) {
            throw new \RuntimeException('No signing certificate found in SURF metadata');
        }

        $certs = self::normalizeIdpCerts($idp);

        return self::mergeWithAssertionSigningCert($certs);
    }

    /**
     * Also fetch SURF's dedicated assertion signing PEM (can differ during key rollover).
     *
     * @param  array{x509cert?: string, x509certMulti?: array{signing: string[]}}  $certs
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    protected static function mergeWithAssertionSigningCert(array $certs): array
    {
        $pemUrl = (string) config('saml.surf.assertion_signing_cert_url');
        if ($pemUrl === '') {
            return $certs;
        }

        try {
            $pem = self::fetchUrl($pemUrl);
            $extra = self::parsePemCertificates($pem);
            $existing = $certs['x509certMulti']['signing'] ?? [$certs['x509cert'] ?? ''];
            $merged = array_values(array_unique(array_merge($existing, $extra)));

            return self::certsToIdpConfig($merged);
        } catch (\Throwable $e) {
            Log::warning('SAML: Could not load SURF assertion signing PEM', [
                'url' => $pemUrl,
                'error' => $e->getMessage(),
            ]);

            return $certs;
        }
    }

    /**
     * @param  array{x509cert?: string, x509certMulti?: array{signing: string[]}}  $certs
     * @return array{
     *     x509cert?: string,
     *     x509certMulti?: array{signing: string[]},
     *     certFingerprint?: string,
     *     certFingerprintAlgorithm?: string
     * }
     */
    protected static function withFingerprint(array $certs): array
    {
        $primary = $certs['x509cert'] ?? $certs['x509certMulti']['signing'][0] ?? null;

        if ($primary !== null && $primary !== '') {
            $certs['certFingerprint'] = Utils::calculateX509Fingerprint(
                Utils::formatCert($primary, true),
                'sha256',
            );
            $certs['certFingerprintAlgorithm'] = 'sha256';
        }

        return $certs;
    }

    /**
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    protected static function loadFromFile(): array
    {
        $path = self::resolveCertPath((string) config('saml.surf.public_cert_path'));

        if ($path === null || ! is_readable($path)) {
            Log::error('SAML SURF public certificate not found', [
                'path' => config('saml.surf.public_cert_path'),
            ]);

            return [];
        }

        $certs = self::parsePemCertificates((string) file_get_contents($path));

        if ($certs === []) {
            Log::error('SAML SURF public certificate file is empty or invalid', ['path' => $path]);

            return [];
        }

        return self::certsToIdpConfig($certs);
    }

    /**
     * @param  array{x509cert?: string, x509certMulti?: array{signing?: string[]}}  $idp
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    protected static function normalizeIdpCerts(array $idp): array
    {
        if (! empty($idp['x509certMulti']['signing'])) {
            $signing = array_values(array_map(
                fn (string $cert) => Utils::formatCert($cert, false),
                $idp['x509certMulti']['signing'],
            ));

            return self::certsToIdpConfig($signing);
        }

        if (! empty($idp['x509cert'])) {
            return self::certsToIdpConfig([Utils::formatCert($idp['x509cert'], false)]);
        }

        return [];
    }

    /**
     * @param  string[]  $certs
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    protected static function certsToIdpConfig(array $certs): array
    {
        $certs = array_values(array_filter($certs));

        if ($certs === []) {
            return [];
        }

        if (count($certs) === 1) {
            return ['x509cert' => $certs[0]];
        }

        return [
            'x509cert' => $certs[0],
            'x509certMulti' => ['signing' => $certs],
        ];
    }

    /**
     * @return string[]
     */
    public static function parsePemCertificates(string $content): array
    {
        if (! preg_match_all('/-----BEGIN CERTIFICATE-----.*?-----END CERTIFICATE-----/s', $content, $matches)) {
            $trimmed = trim($content);
            if ($trimmed !== '') {
                return [Utils::formatCert($trimmed, false)];
            }

            return [];
        }

        return array_map(fn (string $pem) => Utils::formatCert($pem, false), $matches[0]);
    }

    /**
     * Save signing certificates from metadata to the local PEM file.
     */
    public static function downloadToFile(?string $targetPath = null): int
    {
        $metadataUrl = (string) config('saml.surf.metadata_url');
        $entityId = (string) config('saml.surf.entity_id');
        $targetPath = $targetPath ?? self::resolveCertPath((string) config('saml.surf.public_cert_path'))
            ?? storage_path('app/saml/surf_public.crt');

        $idpCerts = self::parseMetadataUrl($metadataUrl, $entityId);
        $signing = $idpCerts['x509certMulti']['signing'] ?? [$idpCerts['x509cert'] ?? ''];
        $signing = array_values(array_filter($signing));

        if ($signing === []) {
            throw new \RuntimeException('No signing certificates to save');
        }

        $pem = implode("\n", array_map(fn (string $cert) => Utils::formatCert($cert, true), $signing));

        if (! is_dir(dirname($targetPath))) {
            mkdir(dirname($targetPath), 0755, true);
        }

        file_put_contents($targetPath, $pem . "\n");
        chmod($targetPath, 0644);
        self::clearCache();

        return count($signing);
    }

    protected static function cacheKey(string $metadataUrl, string $entityId): string
    {
        return 'saml.surf_idp_certs:' . md5($metadataUrl . '|' . $entityId);
    }

    protected static function resolveCertPath(string $originalPath): ?string
    {
        $pathsToTry = array_filter([
            $originalPath,
            ! str_starts_with($originalPath, '/') ? base_path($originalPath) : null,
            str_contains($originalPath, 'storage/') ? storage_path(str_replace('storage/', '', $originalPath)) : null,
            storage_path('app/saml/surf_public.crt'),
        ]);

        foreach ($pathsToTry as $path) {
            if (file_exists($path) && is_readable($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Short SHA-256 fingerprint for diagnostics (first 16 hex chars).
     */
    public static function fingerprintShort(string $cert): ?string
    {
        $fp = self::fingerprint($cert);

        return $fp !== null ? substr($fp, 0, 16) : null;
    }

    /**
     * Full lowercase SHA-256 fingerprint (php-saml format).
     */
    public static function fingerprint(string $cert): ?string
    {
        if ($cert === '') {
            return null;
        }

        return Utils::calculateX509Fingerprint(Utils::formatCert($cert, true), 'sha256');
    }

    /**
     * @return string[]
     */
    public static function certificatesFromSamlResponse(?string $samlResponse): array
    {
        if ($samlResponse === null || $samlResponse === '') {
            return [];
        }

        $xml = base64_decode($samlResponse, true);
        if ($xml === false || $xml === '') {
            return [];
        }

        $doc = new \DOMDocument();
        if (! @$doc->loadXML($xml)) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $nodes = $xpath->query('//ds:X509Certificate');

        $certs = [];
        for ($i = 0; $i < $nodes->length; $i++) {
            $certs[] = Utils::formatCert($nodes->item($i)->nodeValue, false);
        }

        return self::deduplicateCerts($certs);
    }

    /**
     * @param  string[]  $certs
     * @return string[]
     */
    public static function deduplicateCerts(array $certs): array
    {
        $seen = [];
        $unique = [];

        foreach ($certs as $cert) {
            if ($cert === '') {
                continue;
            }

            $fp = self::fingerprint($cert) ?? $cert;
            if (isset($seen[$fp])) {
                continue;
            }

            $seen[$fp] = true;
            $unique[] = $cert;
        }

        return $unique;
    }

    /**
     * @return string[]
     */
    public static function trustedFingerprints(array $trusted): array
    {
        $signing = $trusted['x509certMulti']['signing']
            ?? array_values(array_filter([$trusted['x509cert'] ?? '']));

        $fps = [];
        foreach ($signing as $cert) {
            $fp = self::fingerprint($cert);
            if ($fp !== null) {
                $fps[] = $fp;
            }
        }

        return array_values(array_unique($fps));
    }

    /**
     * Keep only response-embedded certs whose fingerprint matches a trusted IdP cert.
     *
     * @param  string[]  $responseCerts
     * @param  string[]  $trustedFingerprints
     * @return string[]
     */
    public static function filterResponseCertsByTrustedFingerprints(array $responseCerts, array $trustedFingerprints): array
    {
        if ($trustedFingerprints === []) {
            return self::deduplicateCerts($responseCerts);
        }

        $allowed = array_fill_keys($trustedFingerprints, true);
        $matched = [];

        foreach ($responseCerts as $cert) {
            $fp = self::fingerprint($cert);
            if ($fp !== null && isset($allowed[$fp])) {
                $matched[] = $cert;
            }
        }

        return self::deduplicateCerts($matched);
    }

    /**
     * Merge trusted IdP certs with certs embedded in the SAML response signatures.
     *
     * @param  array{x509cert?: string, x509certMulti?: array{signing: string[]}}  $trusted
     * @param  string[]  $responseCerts
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    public static function mergeWithResponseCerts(array $trusted, array $responseCerts): array
    {
        $trustedSigning = $trusted['x509certMulti']['signing']
            ?? array_values(array_filter([$trusted['x509cert'] ?? '']));
        $trustedFps = self::trustedFingerprints($trusted);

        $validatedResponse = self::filterResponseCertsByTrustedFingerprints($responseCerts, $trustedFps);

        // Prefer exact cert bytes from the response (avoids PEM formatting mismatches).
        $merged = array_merge($validatedResponse, $trustedSigning);

        return self::certsToIdpConfig(self::deduplicateCerts($merged));
    }

    /**
     * @return string[]
     */
    public static function shortFingerprints(array $fingerprints): array
    {
        return array_values(array_unique(array_map(
            fn (string $fp) => substr($fp, 0, 16),
            $fingerprints,
        )));
    }

    /**
     * @return array{
     *     exists: bool,
     *     resolved_path: ?string,
     *     fingerprint: ?string
     * }
     */
    public static function certFileInfo(string $configuredPath): array
    {
        $resolved = self::resolveCertPath($configuredPath) ?? (
            ! str_starts_with($configuredPath, '/') ? base_path($configuredPath) : $configuredPath
        );

        if (! is_readable($resolved)) {
            return [
                'exists' => false,
                'resolved_path' => $resolved,
                'fingerprint' => null,
            ];
        }

        $certs = self::parsePemCertificates((string) file_get_contents($resolved));
        $fp = isset($certs[0]) ? self::fingerprintShort($certs[0]) : null;

        return [
            'exists' => true,
            'resolved_path' => $resolved,
            'fingerprint' => $fp,
        ];
    }

    /**
     * Parse non-sensitive SAML response metadata for diagnostics.
     *
     * @return array<string, mixed>
     */
    public static function responseDiagnostics(?string $samlResponse): array
    {
        if ($samlResponse === null || $samlResponse === '') {
            return [
                'saml_response_length' => 0,
                'response_cert_count' => 0,
            ];
        }

        $xml = base64_decode($samlResponse, true);
        if ($xml === false || $xml === '') {
            return [
                'saml_response_length' => strlen($samlResponse),
                'response_decode_failed' => true,
            ];
        }

        $doc = new \DOMDocument();
        if (! @$doc->loadXML($xml)) {
            return [
                'saml_response_length' => strlen($samlResponse),
                'response_xml_invalid' => true,
            ];
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('samlp', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

        $destination = $doc->documentElement->hasAttribute('Destination')
            ? $doc->documentElement->getAttribute('Destination')
            : null;

        $audiences = [];
        $audienceNodes = $xpath->query('//saml:Audience');
        for ($i = 0; $i < $audienceNodes->length; $i++) {
            $audiences[] = trim($audienceNodes->item($i)->textContent);
        }

        $issuers = [];
        $issuerNodes = $xpath->query('//saml:Issuer | //samlp:Issuer');
        for ($i = 0; $i < $issuerNodes->length; $i++) {
            $issuers[] = trim($issuerNodes->item($i)->textContent);
        }

        $statusCode = null;
        $statusNodes = $xpath->query('//samlp:Status/samlp:StatusCode');
        if ($statusNodes->length > 0 && $statusNodes->item(0)->hasAttribute('Value')) {
            $statusCode = $statusNodes->item(0)->getAttribute('Value');
        }

        return [
            'saml_response_length' => strlen($samlResponse),
            'response_xml_length' => strlen($xml),
            'response_destination' => $destination,
            'response_audiences' => array_values(array_unique(array_filter($audiences))),
            'response_issuers' => array_values(array_unique(array_filter($issuers))),
            'response_status_code' => $statusCode,
            'has_response_signature' => $xpath->query('/samlp:Response/ds:Signature')->length > 0,
            'has_assertion_signature' => $xpath->query('//saml:Assertion/ds:Signature')->length > 0,
            'response_cert_count' => $xpath->query('//ds:X509Certificate')->length,
            'response_cert_fps' => self::fingerprintsFromSamlResponse($samlResponse),
        ];
    }

    /**
     * Whether any response-embedded cert matches configured cert bytes exactly.
     *
     * @param  string[]  $responseCerts
     */
    public static function responseCertBytesMatchConfigured(array $responseCerts, string $configuredCert): bool
    {
        if ($configuredCert === '') {
            return false;
        }

        $configuredClean = preg_replace('/\s+/', '', Utils::formatCert($configuredCert, false));

        foreach ($responseCerts as $cert) {
            $responseClean = preg_replace('/\s+/', '', Utils::formatCert($cert, false));
            if ($responseClean === $configuredClean) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract SHA-256 fingerprints of all X509 certs embedded in a SAML response.
     *
     * @return string[]
     */
    public static function fingerprintsFromSamlResponse(?string $samlResponse): array
    {
        if ($samlResponse === null || $samlResponse === '') {
            return [];
        }

        $xml = base64_decode($samlResponse, true);
        if ($xml === false || $xml === '') {
            return [];
        }

        $doc = new \DOMDocument();
        if (! @$doc->loadXML($xml)) {
            return [];
        }

        $xpath = new \DOMXPath($doc);
        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
        $nodes = $xpath->query('//ds:X509Certificate');
        $fps = [];

        for ($i = 0; $i < $nodes->length; $i++) {
            $pem = Utils::formatCert($nodes->item($i)->nodeValue, true);
            $fp = Utils::calculateX509Fingerprint($pem, 'sha256');
            if ($fp !== null) {
                $fps[] = substr($fp, 0, 16);
            }
        }

        return array_values(array_unique($fps));
    }
}
