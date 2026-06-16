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
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    public static function load(): array
    {
        $fromMetadata = self::loadFromMetadata();

        if ($fromMetadata !== null) {
            return $fromMetadata;
        }

        return self::loadFromFile();
    }

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
     * @return array{x509cert?: string, x509certMulti?: array{signing: string[]}}
     */
    public static function parseMetadataUrl(string $metadataUrl, string $entityId): array
    {
        $parsed = IdPMetadataParser::parseRemoteXML($metadataUrl, $entityId ?: null);
        $idp = $parsed['idp'] ?? [];

        if (empty($idp['x509cert']) && empty($idp['x509certMulti']['signing'] ?? null)) {
            throw new \RuntimeException('No signing certificate found in SURF metadata');
        }

        return self::normalizeIdpCerts($idp);
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
    public static function fingerprint(string $cert): ?string
    {
        if ($cert === '') {
            return null;
        }

        $pem = Utils::formatCert($cert, true);
        $fp = openssl_x509_fingerprint($pem, 'sha256');

        return $fp !== false ? substr($fp, 0, 16) : null;
    }
}
