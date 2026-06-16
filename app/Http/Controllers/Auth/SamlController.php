<?php

namespace App\Http\Controllers\Auth;

use App\Auth\StudentsUser;
use App\Exceptions\SamlAuthenticationException;
use App\Helpers\SamlHelper;
use App\Models\User;
use App\Services\Saml\SurfIdpCertificateLoader;
use Filament\Notifications\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use OneLogin\Saml2\Auth as SamlAuth;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;
use Spatie\LaravelFlare\Facades\Flare;

class SamlController extends Controller
{
    /**
     * Check if SAML is enabled, abort if not
     */
    protected function ensureSamlEnabled(): void
    {
        if (!SamlHelper::isEnabled()) {
            abort(503, 'SAML authentication is not configured. Please set the required environment variables.');
        }
    }

    /**
     * Pin OneLogin URL detection to APP_URL so destination validation works behind reverse proxies.
     */
    protected function configureOneLoginUtils(): void
    {
        Utils::setProxyVars(true);

        $baseUrl = rtrim((string) config('app.url'), '/');
        $parts = parse_url($baseUrl);

        if (! empty($parts['host'])) {
            Utils::setSelfHost($parts['host']);
        }

        if (! empty($parts['scheme'])) {
            Utils::setSelfProtocol($parts['scheme']);
        }

        if (! empty($parts['port'])) {
            Utils::setSelfPort((int) $parts['port']);
        } elseif (($parts['scheme'] ?? '') === 'https') {
            Utils::setSelfPort(443);
        } elseif (($parts['scheme'] ?? '') === 'http') {
            Utils::setSelfPort(80);
        }
    }

    protected function getSamlAuth(?string $guard = null): SamlAuth
    {
        $this->configureOneLoginUtils();

        $settings = $this->getSamlSettings();

        $hasIdpCert = ! empty($settings['idp']['x509cert'])
            || ! empty($settings['idp']['x509certMulti']['signing']);

        if (! $hasIdpCert) {
            $certPath = config('saml.surf.public_cert_path');
            Log::error('SAML IdP certificate not configured', [
                'cert_path' => $certPath,
                'metadata_url' => config('saml.surf.metadata_url'),
                'idp_cert_source' => SurfIdpCertificateLoader::lastSource(),
            ]);
            throw new \RuntimeException(
                'SURF Conext signing certificate could not be loaded. ' .
                'Run: php artisan saml:install --refresh-surf ' .
                'or ensure the server can reach ' . config('saml.surf.metadata_url')
            );
        }

        if (! empty($settings['idp']['x509cert'])) {
            Log::debug('SAML IDP certificate loaded (' . strlen($settings['idp']['x509cert']) . ' chars)');
        }
        
        try {
            return new SamlAuth($settings);
        } catch (\Exception $e) {
            if (str_contains($e->getMessage(), 'cert') || str_contains($e->getMessage(), 'fingerprint')) {
                Log::error('SAML configuration error: ' . $e->getMessage());

                throw new \RuntimeException(
                    'SAML configuration error: IDP certificate issue. ' .
                    'Run: php artisan saml:install --refresh-surf. Error: ' . $e->getMessage()
                );
            }

            throw $e;
        }
    }

    /**
     * Build SamlAuth for ACS, merging SURF's embedded signature certificates into the trust store.
     */
    protected function getSamlAuthForAcs(Request $request, ?string $guard = null): SamlAuth
    {
        $this->configureOneLoginUtils();

        $settings = $this->getSamlSettings();
        $trusted = SurfIdpCertificateLoader::load();
        $responseCerts = SurfIdpCertificateLoader::certificatesFromSamlResponse(
            $request->input('SAMLResponse')
        );
        $assertionCerts = SurfIdpCertificateLoader::certificatesFromAssertionSignature(
            $request->input('SAMLResponse')
        );

        if ($responseCerts !== [] || $trusted !== []) {
            $merged = SurfIdpCertificateLoader::mergeWithResponseCerts(
                $trusted,
                $assertionCerts !== [] ? $assertionCerts : $responseCerts,
            );
            unset(
                $settings['idp']['certFingerprint'],
                $settings['idp']['certFingerprintAlgorithm'],
            );

            if (! empty($merged['x509certMulti']['signing'])) {
                $settings['idp']['x509certMulti'] = $merged['x509certMulti'];
                $settings['idp']['x509cert'] = $merged['x509cert'] ?? $merged['x509certMulti']['signing'][0];
            } elseif (! empty($merged['x509cert'])) {
                $settings['idp']['x509cert'] = $merged['x509cert'];
                unset($settings['idp']['x509certMulti']);
            }

            $trustedFps = SurfIdpCertificateLoader::trustedFingerprints($trusted);
            $assertionFps = SurfIdpCertificateLoader::fingerprintsFromAssertionSignature(
                $request->input('SAMLResponse')
            );
            $rollover = SurfIdpCertificateLoader::detectsKeyRollover($assertionFps, $trustedFps);

            Log::debug('SAML ACS: IdP signing certs for validation', [
                'trusted_source' => SurfIdpCertificateLoader::lastSource(),
                'trusted_fps' => array_map(fn (string $fp) => substr($fp, 0, 16), $trustedFps),
                'assertion_signing_fps' => $assertionFps,
                'response_cert_count' => count($responseCerts),
                'merged_count' => count($merged['x509certMulti']['signing'] ?? array_filter([$merged['x509cert'] ?? ''])),
                'idp_key_rollover_detected' => $rollover,
            ]);

            if ($rollover) {
                Log::warning('SAML ACS: Assertion signed with cert not in SURF metadata — using response KeyInfo cert', [
                    'assertion_signing_fps' => $assertionFps,
                    'metadata_fps' => array_map(fn (string $fp) => substr($fp, 0, 16), $trustedFps),
                    'metadata_url' => config('saml.surf.metadata_url'),
                ]);
            }
        }

        $hasIdpCert = ! empty($settings['idp']['x509cert'])
            || ! empty($settings['idp']['x509certMulti']['signing']);

        if (! $hasIdpCert) {
            throw new \RuntimeException(
                'SURF Conext signing certificate could not be loaded. ' .
                'Run: php artisan saml:install --refresh-surf'
            );
        }

        return new SamlAuth($settings);
    }

    protected function getSamlSettings(): array
    {
        $config = config('saml.settings');

        // Load certificates from files if paths are provided
        if (empty($config['sp']['x509cert']) && !empty(config('saml.sp.public_cert_path'))) {
            $certPath = config('saml.sp.public_cert_path');
            // Handle both absolute and relative paths
            if (!file_exists($certPath) && !str_starts_with($certPath, '/')) {
                $certPath = base_path($certPath);
            }
            if (file_exists($certPath)) {
                $config['sp']['x509cert'] = file_get_contents($certPath);
            } else {
                Log::warning("SAML SP public certificate not found at: {$certPath}");
            }
        }

        if (empty($config['sp']['privateKey']) && !empty(config('saml.sp.private_key_path'))) {
            $keyPath = config('saml.sp.private_key_path');
            // Handle both absolute and relative paths
            if (!file_exists($keyPath) && !str_starts_with($keyPath, '/')) {
                $keyPath = base_path($keyPath);
            }
            if (file_exists($keyPath)) {
                $config['sp']['privateKey'] = file_get_contents($keyPath);
            } else {
                Log::warning("SAML SP private key not found at: {$keyPath}");
            }
        }

        // Never use a stale SURF_PUBLIC_CERT baked into config:cache — load fresh only
        $config['idp']['x509cert'] = '';
        unset($config['idp']['x509certMulti'], $config['idp']['certFingerprint'], $config['idp']['certFingerprintAlgorithm']);

        $idpCerts = SurfIdpCertificateLoader::load();

        if (! empty($idpCerts['x509certMulti']['signing'])) {
            $config['idp']['x509certMulti'] = $idpCerts['x509certMulti'];
            $config['idp']['x509cert'] = $idpCerts['x509cert'] ?? $idpCerts['x509certMulti']['signing'][0];
        } elseif (! empty($idpCerts['x509cert'])) {
            $config['idp']['x509cert'] = $idpCerts['x509cert'];
        }

        if (empty($config['idp']['x509cert']) && empty($config['idp']['x509certMulti'])) {
            Log::error('SAML SURF IdP certificate could not be loaded. Run: php artisan saml:install --refresh-surf');
        }

        return $config;
    }

    /**
     * Build RelayState string that survives the redirect to IdP and back.
     * Encodes guard, return URL and optional link_token (for "link SURF to current user" flow).
     */
    protected function buildRelayState(string $guard, string $returnUrl, ?string $linkToken = null): string
    {
        $payload = [
            'guard' => $guard,
            'return' => $returnUrl,
        ];
        if ($linkToken !== null && $linkToken !== '') {
            $payload['link_token'] = $linkToken;
        }
        return base64_encode(json_encode($payload));
    }

    /**
     * Parse RelayState from IdP response. Returns full payload (guard, return, link_token?) or null.
     */
    protected function parseRelayState(?string $relayState): ?array
    {
        if (empty($relayState)) {
            return null;
        }
        $decoded = @json_decode(base64_decode($relayState, true) ?: '', true);
        if (is_array($decoded) && isset($decoded['guard'], $decoded['return'])) {
            return [
                'guard' => (string) $decoded['guard'],
                'return' => (string) $decoded['return'],
                'link_token' => isset($decoded['link_token']) ? (string) $decoded['link_token'] : null,
            ];
        }
        return null;
    }

    /**
     * Redirect to SAML login with an error flag (avoids SURF redirect loops on ACS failure).
     */
    protected function redirectToAuthFailed(string $guard, string $returnUrl, ?string $error = null, array $extra = []): RedirectResponse
    {
        $diagnostics = array_merge([
            'ref' => strtoupper(bin2hex(random_bytes(4))),
            'at' => now()->utc()->format('Y-m-d H:i:s') . ' UTC',
            'stage' => 'acs',
            'guard' => $guard,
            'return' => $returnUrl,
            'error' => $error,
            'acs_url' => config('saml.sp.acs_url'),
            'entity_id' => config('saml.sp.entity_id'),
            'app_url' => config('app.url'),
        ], $extra);

        session(['saml_last_diagnostics' => $this->sessionDiagnostics($diagnostics)]);
        session()->save();

        $this->reportSamlFailure($diagnostics);

        return redirect()->route('saml.login', [
            'guard' => $guard,
            'return' => $returnUrl,
            'auth_failed' => 1,
        ]);
    }

    /**
     * Subset of diagnostics safe to show on the error page (no paths, versions, etc.).
     *
     * @return array<string, mixed>
     */
    protected function sessionDiagnostics(array $diagnostics): array
    {
        $keys = [
            'ref', 'at', 'stage', 'guard', 'codes', 'error',
            'idp_cert_source', 'idp_cert_fp', 'trusted_cert_fps',
            'assertion_signing_cert_fps', 'response_cert_fps',
            'acs_url', 'return',
        ];

        return array_intersect_key($diagnostics, array_flip($keys));
    }

    /**
     * Collect everything useful for debugging ACS signature / validation failures.
     *
     * @return array<string, mixed>
     */
    protected function buildAcsFailureDiagnostics(Request $request, ?SamlAuth $samlAuth = null, array $overrides = []): array
    {
        $samlResponse = $request->input('SAMLResponse');
        $trusted = SurfIdpCertificateLoader::load();
        $trustedFps = SurfIdpCertificateLoader::trustedFingerprints($trusted);
        $responseCerts = SurfIdpCertificateLoader::certificatesFromSamlResponse($samlResponse);
        $assertionCerts = SurfIdpCertificateLoader::certificatesFromAssertionSignature($samlResponse);
        $assertionCertFps = SurfIdpCertificateLoader::fingerprintsFromAssertionSignature($samlResponse);
        $validatedResponse = SurfIdpCertificateLoader::filterResponseCertsByTrustedFingerprints(
            $responseCerts,
            $trustedFps,
        );
        $merged = SurfIdpCertificateLoader::mergeWithResponseCerts($trusted, $assertionCerts !== [] ? $assertionCerts : $responseCerts);
        $mergedSigning = $merged['x509certMulti']['signing'] ?? array_values(array_filter([$merged['x509cert'] ?? '']));

        $configuredIdpCert = '';
        $configuredIdpFps = [];
        if ($samlAuth !== null) {
            $idpData = $samlAuth->getSettings()->getIdPData();
            $configuredIdpCert = (string) ($idpData['x509cert'] ?? '');
            $configuredSigning = $idpData['x509certMulti']['signing'] ?? [$configuredIdpCert];
            foreach ($configuredSigning as $cert) {
                $fp = SurfIdpCertificateLoader::fingerprint((string) $cert);
                if ($fp !== null) {
                    $configuredIdpFps[] = $fp;
                }
            }
        }

        $spCert = SurfIdpCertificateLoader::certFileInfo((string) config('saml.sp.public_cert_path'));
        $spKeyPath = (string) config('saml.sp.private_key_path');
        $spKeyResolved = ! str_starts_with($spKeyPath, '/') ? base_path($spKeyPath) : $spKeyPath;
        $surfCert = SurfIdpCertificateLoader::certFileInfo((string) config('saml.surf.public_cert_path'));
        $security = config('saml.settings.security', []);

        $selfUrl = null;
        try {
            $selfUrl = Utils::getSelfURL();
        } catch (\Throwable) {
            // OneLogin utils may not be configured yet.
        }

        return array_merge([
            'idp_cert_source' => SurfIdpCertificateLoader::lastSource(),
            'idp_cert_fp' => SurfIdpCertificateLoader::shortFingerprints($trustedFps)[0] ?? null,
            'trusted_cert_fps' => SurfIdpCertificateLoader::shortFingerprints($trustedFps),
            'configured_idp_cert_fps' => SurfIdpCertificateLoader::shortFingerprints($configuredIdpFps),
            'response_cert_fps' => SurfIdpCertificateLoader::fingerprintsFromSamlResponse($samlResponse),
            'assertion_signing_cert_fps' => $assertionCertFps,
            'idp_key_rollover_detected' => SurfIdpCertificateLoader::detectsKeyRollover($assertionCertFps, $trustedFps),
            'response_cert_count' => count($responseCerts),
            'response_matched_count' => count($validatedResponse),
            'merged_cert_count' => count($mergedSigning),
            'cert_bytes_match' => SurfIdpCertificateLoader::responseCertBytesMatchConfigured(
                $responseCerts,
                $configuredIdpCert,
            ),
            'fingerprints_match' => array_intersect(
                SurfIdpCertificateLoader::shortFingerprints($trustedFps),
                SurfIdpCertificateLoader::fingerprintsFromSamlResponse($samlResponse),
            ) !== [],
            'sp_entity_id' => config('saml.sp.entity_id'),
            'idp_entity_id' => config('saml.surf.entity_id'),
            'sp_public_cert' => $spCert,
            'sp_private_key_exists' => is_readable($spKeyResolved),
            'sp_private_key_path' => $spKeyResolved,
            'surf_public_cert' => $surfCert,
            'surf_metadata_url' => config('saml.surf.metadata_url'),
            'authn_requests_signed' => (bool) ($security['authnRequestsSigned'] ?? false),
            'want_assertions_signed' => (bool) ($security['wantAssertionsSigned'] ?? false),
            'strict' => (bool) config('saml.settings.strict', true),
            'relay_state_present' => $request->filled('RelayState'),
            'onelogin_self_url' => $selfUrl,
            'php_version' => PHP_VERSION,
            'openssl_version' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : null,
            'response' => SurfIdpCertificateLoader::responseDiagnostics($samlResponse),
        ], $overrides);
    }

    /**
     * Report SAML authentication failures to Flare with structured context for debugging.
     */
    protected function reportSamlFailure(array $diagnostics): void
    {
        Log::error('SAML authentication failed', $diagnostics);

        if (empty(config('flare.key'))) {
            return;
        }

        try {
            $message = $diagnostics['error'] ?? 'SAML authentication failed';
            $ref = $diagnostics['ref'] ?? 'unknown';
            $stage = $diagnostics['stage'] ?? 'unknown';

            Flare::report(
                new SamlAuthenticationException("[{$ref}] [{$stage}] {$message}"),
                fn ($report) => $report->context('saml', $diagnostics),
                handled: true,
            );
        } catch (\Throwable $e) {
            Log::warning('Could not report SAML failure to Flare', [
                'ref' => $diagnostics['ref'] ?? null,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Redirect to admin login with a Filament notification so the message is visible on the login page.
     *
     * @param  'danger'|'warning'  $type
     */
    protected function redirectToAdminLoginWithError(string $message, string $type = 'danger'): RedirectResponse
    {
        $notification = Notification::make()->title($message);

        if ($type === 'warning') {
            $notification->warning();
        } else {
            $notification->danger();
        }

        $notification->send();

        return redirect('/admin/login');
    }

    /**
     * Start "Link SURF Conext" flow: user is already logged in, we store their id and send them to IdP.
     * When they return, we set their surf_id and redirect back to admin. Requires auth.
     */
    public function link(Request $request)
    {
        $this->ensureSamlEnabled();

        if (!Auth::check()) {
            return $this->redirectToAdminLoginWithError('You must be logged in to link your SURF Conext account.');
        }

        $token = bin2hex(random_bytes(32));
        Cache::put('saml_link:' . $token, Auth::id(), 600); // 10 minutes

        $returnUrl = $request->get('return', '/admin');

        return redirect()->route('saml.login', [
            'guard' => 'web',
            'return' => $returnUrl,
            'link_token' => $token,
        ]);
    }

    /**
     * Initiate SAML SSO login
     */
    public function login(Request $request)
    {
        $this->ensureSamlEnabled();

        $guard = $request->get('guard', 'students');
        $returnUrl = $request->get('return', '/');

        if ($request->boolean('auth_failed')) {
            return response()->view('saml.auth-failed', [
                'retryUrl' => route('saml.login', [
                    'guard' => $guard,
                    'return' => $returnUrl,
                ]),
                'diagnostics' => session('saml_last_diagnostics', []),
            ], 403);
        }

        // Already logged in locally – skip IdP to prevent SURF redirect loops
        if ($guard === 'students' && Auth::guard('students')->check()) {
            return redirect($returnUrl);
        }
        if ($guard === 'web' && Auth::guard('web')->check() && ! $request->filled('link_token')) {
            return redirect($returnUrl ?: '/admin');
        }

        // Store in session as fallback (in case RelayState is not echoed back by IdP)
        session(['saml_return_url' => $returnUrl, 'saml_guard' => $guard]);

        // RelayState is sent to IdP and returned in the response – use it so we don't depend on session
        $linkToken = $request->get('link_token');
        $relayState = $this->buildRelayState($guard, $returnUrl, $linkToken ?: null);

        try {
            $samlAuth = $this->getSamlAuth($guard);

            // stay=true: return the IdP URL instead of header()+exit(), so Laravel can persist the session
            $idpUrl = $samlAuth->login($relayState, [], false, false, true);

            $requestId = $samlAuth->getLastRequestID();
            if ($requestId) {
                session(['saml_request_id' => $requestId]);
                Log::debug('SAML login initiated with request ID: ' . $requestId);
            }

            session()->save();

            return redirect()->away($idpUrl);
        } catch (\Exception $e) {
            Log::error('SAML login error: ' . $e->getMessage());
            Log::error('SAML login error trace: ' . $e->getTraceAsString());

            return $this->redirectToAuthFailed($guard, $returnUrl, $e->getMessage(), array_merge([
                'stage' => 'login',
                'sp_public_cert' => SurfIdpCertificateLoader::certFileInfo((string) config('saml.sp.public_cert_path')),
                'sp_private_key_path' => ! str_starts_with((string) config('saml.sp.private_key_path'), '/')
                    ? base_path((string) config('saml.sp.private_key_path'))
                    : (string) config('saml.sp.private_key_path'),
                'sp_private_key_exists' => is_readable(
                    ! str_starts_with((string) config('saml.sp.private_key_path'), '/')
                        ? base_path((string) config('saml.sp.private_key_path'))
                        : (string) config('saml.sp.private_key_path')
                ),
                'idp_cert_source' => SurfIdpCertificateLoader::lastSource(),
                'trusted_cert_fps' => SurfIdpCertificateLoader::shortFingerprints(
                    SurfIdpCertificateLoader::trustedFingerprints(SurfIdpCertificateLoader::load()),
                ),
                'exception' => [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                ],
            ]));
        }
    }

    /**
     * Handle SAML response (Assertion Consumer Service)
     */
    public function acs(Request $request)
    {
        $this->ensureSamlEnabled();

        // Prefer guard and return URL from RelayState (survives cross-domain redirect); fall back to session
        $relayStateRaw = $request->input('RelayState');
        $parsed = $this->parseRelayState($relayStateRaw);
        $linkToken = null;
        if ($parsed !== null) {
            $guard = $parsed['guard'];
            $returnUrl = $parsed['return'];
            $linkToken = $parsed['link_token'] ?? null;
            Log::info('SAML ACS: Using guard and return URL from RelayState', ['guard' => $guard, 'returnUrl' => $returnUrl, 'linkMode' => $linkToken !== null]);
        } else {
            $guard = session('saml_guard', 'students');
            $returnUrl = session('saml_return_url', '/');
            Log::info('SAML ACS: Using guard and return URL from session (RelayState missing or invalid)', ['guard' => $guard, 'returnUrl' => $returnUrl, 'relayStatePresent' => $relayStateRaw !== null]);
        }
        $requestId = null;
        // Do not pass session request ID: the ACS POST from SURF is cross-site and often
        // arrives without the login session cookie, or with a stale ID from a prior attempt.

        try {
            $samlAuth = $this->getSamlAuthForAcs($request, $guard);

            if (config('saml.settings.debug', false)) {
                Log::debug('SAML ACS: Request ID from session (not used): ' . (session('saml_request_id') ?? 'none'));
                if ($request->has('SAMLResponse')) {
                    Log::debug('SAML ACS: SAMLResponse POST parameter present');
                }
            }

            $samlAuth->processResponse($requestId);

            $errors = $samlAuth->getErrors();

            if (!empty($errors)) {
                $errorReason = $samlAuth->getLastErrorReason();
                $errorException = $samlAuth->getLastErrorException();

                return $this->redirectToAuthFailed($guard, $returnUrl, $errorReason ?: implode(', ', $errors), $this->buildAcsFailureDiagnostics($request, $samlAuth, [
                    'codes' => $errors,
                    'exception' => $errorException ? [
                        'class' => $errorException::class,
                        'message' => $errorException->getMessage(),
                    ] : null,
                ]));
            }

            if (!$samlAuth->isAuthenticated()) {
                return $this->redirectToAuthFailed($guard, $returnUrl, 'SAML response was not authenticated.', $this->buildAcsFailureDiagnostics($request, $samlAuth));
            }

            // Extract attributes
            $attributes = $samlAuth->getAttributes();
            $nameId = $samlAuth->getNameId();

            // Log which attributes we received (helps debug missing email from SURF)
            if (config('saml.settings.strict', false) || env('SAML_DEBUG', false)) {
                Log::debug('SAML ACS: Raw attributes received: ' . json_encode(array_keys($attributes)));
                Log::debug('SAML ACS: NameID: ' . $nameId);
            }

            // Map attributes
            $persistentId = $this->extractAttribute($attributes, 'persistent_id') ?? $nameId;
            $email = $this->extractAttribute($attributes, 'email');
            $eduAffiliation = $this->extractAttribute($attributes, 'edu_affiliation');

            if (empty($persistentId)) {
                Log::error('SAML: No persistent ID found in response');

                return $this->redirectToAuthFailed($guard, $returnUrl, 'Missing user identifier in SAML response.', $this->buildAcsFailureDiagnostics($request, $samlAuth));
            }

            // Link mode: connect SURF identity to the already-logged-in user (no email needed)
            if ($guard === 'web' && $linkToken !== null && $linkToken !== '') {
                $userId = Cache::get('saml_link:' . $linkToken);
                if ($userId === null) {
                    Log::warning('SAML Link: Token not found or expired', ['token_preview' => substr($linkToken, 0, 8) . '...']);
                    return $this->redirectToAdminLoginWithError('Link session expired. Please try again from the admin panel.');
                }
                $user = User::find($userId);
                if (!$user instanceof User) {
                    Cache::forget('saml_link:' . $linkToken);
                    return $this->redirectToAdminLoginWithError('User no longer found. Please log in again.');
                }
                $user->surf_id = $persistentId;
                $user->save();
                Cache::forget('saml_link:' . $linkToken);
                Auth::guard('web')->login($user);
                session()->forget(['saml_return_url', 'saml_guard', 'saml_request_id']);
                Log::info('SAML Link: Connected SURF identity to user', ['user_id' => $user->id]);

                return redirect($returnUrl ?: '/admin')->with('success', 'SURF Conext account linked successfully. You can sign in with SURF Conext next time.');
            }

            // Handle authentication based on guard
            if ($guard === 'students') {
                return $this->handleStudentsAuth($persistentId, $eduAffiliation, $email, $returnUrl);
            } else {
                return $this->handleAdminAuth($persistentId, $email, $returnUrl);
            }
        } catch (\Exception $e) {
            return $this->redirectToAuthFailed($guard ?? 'students', $returnUrl ?? '/', $e->getMessage(), $this->buildAcsFailureDiagnostics($request, null, [
                'exception' => [
                    'class' => $e::class,
                    'message' => $e->getMessage(),
                ],
            ]));
        }
    }

    /**
     * Handle students guard authentication (session-based).
     * Persistent ID is stored in the session via StudentsUser for the lifetime of the session.
     */
    protected function handleStudentsAuth(string $persistentId, ?string $eduAffiliation, ?string $email, string $returnUrl)
    {
        $user = new StudentsUser($persistentId, $eduAffiliation, $email);
        Auth::guard('students')->setUser($user);

        // Clear SAML session data
        session()->forget(['saml_return_url', 'saml_guard', 'saml_request_id']);
        session()->save();

        return redirect($returnUrl);
    }

    /**
     * Handle admin guard authentication (database lookup by email or surf_id).
     * Saves the SURF persistent ID on the user every time so the two identities stay connected.
     */
    protected function handleAdminAuth(string $persistentId, ?string $email, string $returnUrl)
    {
        $user = null;

        if (!empty($email)) {
            $user = User::where('email', $email)->first();
        }

        // No email or no match: try existing link by surf_id (returning user)
        if (!$user) {
            $user = User::where('surf_id', $persistentId)->first();
        }

        if (!$user) {
            $warning = 'Is your sign-in using Single-Sign-On (SSO) unsuccessful? Please sign in once using email and password. We\'ll prepare your SSO sign-in for future use. Thanks for understanding.';

            if (empty($email)) {
                Log::warning('SAML Admin: No email in response and no user linked to this SURF identity.');
            } else {
                Log::warning('SAML Admin: User not found with email: ' . $email);
            }

            return $this->redirectToAdminLoginWithError($warning, 'warning');
        }

        // Check if user has access to admin panel
        $panel = \Filament\Facades\Filament::getPanel('admin');
        if ($panel && !$user->canAccessPanel($panel)) {
            return $this->redirectToAdminLoginWithError('You do not have access to the admin panel.');
        }

        // Save persistent ID on every login so SURF and local user stay connected (first time and updates)
        $user->surf_id = $persistentId;
        $user->save();

        // Authenticate user
        Auth::guard('web')->login($user);

        // Clear SAML session data
        session()->forget(['saml_return_url', 'saml_guard', 'saml_request_id']);

        return redirect($returnUrl ?: '/admin');
    }

    /**
     * Initiate SAML logout
     */
    public function logout(Request $request)
    {
        $this->ensureSamlEnabled();
        
        $guard = $request->get('guard', Auth::getDefaultDriver());

        try {
            $samlAuth = $this->getSamlAuth($guard);

            // Logout from local session first
            if ($guard === 'students') {
                Auth::guard('students')->logout();
            } else {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            // Initiate SAML logout
            $returnTo = $request->get('return', url('/'));
            $samlAuth->logout($returnTo);
        } catch (\Exception $e) {
            Log::error('SAML logout error: ' . $e->getMessage());

            // Fallback to local logout
            if ($guard === 'students') {
                Auth::guard('students')->logout();
            } else {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect('/');
        }
    }

    /**
     * Handle SAML Single Logout Service callback
     */
    public function sls(Request $request)
    {
        $this->ensureSamlEnabled();
        
        try {
            $samlAuth = $this->getSamlAuth();
            $samlAuth->processSLO();

            $errors = $samlAuth->getErrors();

            if (!empty($errors)) {
                Log::error('SAML SLS errors: ' . implode(', ', $errors));
            }

            // Logout from local session
            Auth::guard('students')->logout();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('SAML SLS error: ' . $e->getMessage());

            // Fallback logout
            Auth::guard('students')->logout();
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect('/');
        }
    }

    /**
     * Generate and return SP metadata
     */
    public function metadata()
    {
        $this->ensureSamlEnabled();
        
        try {
            $samlSettings = $this->getSamlSettings();
            
            // Validate required settings
            if (empty($samlSettings['sp']['entityId'])) {
                Log::error('SAML metadata: SP entityId is missing');
                return response('SP Entity ID is not configured', 500);
            }
            
            if (empty($samlSettings['sp']['x509cert']) && empty($samlSettings['sp']['privateKey'])) {
                Log::error('SAML metadata: SP certificates are missing');
                return response('SP certificates are not configured. Run: php artisan saml:install', 500);
            }
            
            $settings = new Settings($samlSettings, true);
            $metadata = $settings->getSPMetadata();
            $errors = $settings->validateMetadata($metadata);

            if (!empty($errors)) {
                Log::error('SAML metadata validation errors: ' . implode(', ', $errors));
                return response('Metadata validation failed: ' . implode(', ', $errors), 500);
            }

            return response($metadata, 200, [
                'Content-Type' => 'application/xml',
            ]);
        } catch (\Exception $e) {
            Log::error('SAML metadata error: ' . $e->getMessage());
            Log::error('SAML metadata stack trace: ' . $e->getTraceAsString());
            return response('Metadata generation failed: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Normalize certificate content for OneLogin library
     * The library's Utils::formatCert() handles formatting, so we just clean it up
     * but keep PEM headers if present - the library will reformat as needed
     */
    protected function normalizeCertificate(string $cert): string
    {
        $cert = trim($cert);
        
        // If it's already in PEM format, keep it as-is (library will handle it)
        // If it's base64 without headers, that's also fine
        // Just ensure it's clean
        return $cert;
    }

    /**
     * Extract attribute value from SAML attributes array
     */
    protected function extractAttribute(array $attributes, string $key): ?string
    {
        $mappings = config("saml.attributes.{$key}", []);

        foreach ($mappings as $attributeName) {
            if (isset($attributes[$attributeName])) {
                $value = $attributes[$attributeName];
                // SAML attributes can be arrays, get first value
                if (is_array($value) && !empty($value)) {
                    return $value[0];
                }
                if (is_string($value)) {
                    return $value;
                }
            }
        }

        return null;
    }
}
