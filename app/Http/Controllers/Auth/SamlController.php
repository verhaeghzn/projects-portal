<?php

namespace App\Http\Controllers\Auth;

use App\Auth\StudentsUser;
use App\Helpers\SamlHelper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use OneLogin\Saml2\Auth as SamlAuth;
use OneLogin\Saml2\Settings;
use OneLogin\Saml2\Utils;

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
    protected function getSamlAuth(?string $guard = null): SamlAuth
    {
        $settings = $this->getSamlSettings();
        
        // Validate that required certificates are present
        if (empty($settings['idp']['x509cert'])) {
            $certPath = config('saml.surf.public_cert_path');
            $triedPaths = [
                $certPath,
                base_path($certPath),
                storage_path('app/saml/surf_public.crt'),
            ];
            
            Log::error('SAML IDP certificate not found. Tried paths: ' . implode(', ', $triedPaths));
            throw new \RuntimeException(
                'SURF Conext certificate is required but not found. ' .
                'Certificate path: ' . $certPath . '. ' .
                'Please download it using: php artisan saml:install ' .
                'or set SURF_PUBLIC_CERT environment variable.'
            );
        }
        
        // Log certificate info for debugging (first 50 chars only)
        $certPreview = substr($settings['idp']['x509cert'], 0, 50);
        Log::debug("SAML IDP certificate loaded: {$certPreview}... (total: " . strlen($settings['idp']['x509cert']) . " chars)");
        
        try {
            return new SamlAuth($settings);
        } catch (\Exception $e) {
            // If it's a certificate error, provide more helpful message
            if (str_contains($e->getMessage(), 'cert') || str_contains($e->getMessage(), 'fingerprint')) {
                Log::error('SAML configuration error: ' . $e->getMessage());
                Log::error('IDP certificate status: ' . (empty($settings['idp']['x509cert']) ? 'MISSING' : 'PRESENT (' . strlen($settings['idp']['x509cert']) . ' chars)'));
                Log::error('IDP certificate preview: ' . substr($settings['idp']['x509cert'] ?? '', 0, 100));
                throw new \RuntimeException(
                    'SAML configuration error: IDP certificate issue. ' .
                    'Please ensure the SURF Conext certificate is properly downloaded and configured. ' .
                    'Run: php artisan saml:install. Error: ' . $e->getMessage()
                );
            }
            throw $e;
        }
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

        // Load IDP certificate - required for SAML authentication when wantAssertionsSigned is true
        if (empty($config['idp']['x509cert'])) {
            // First try environment variable (can be PEM or base64)
            $certContent = env('SURF_PUBLIC_CERT');
            if (!empty($certContent)) {
                $config['idp']['x509cert'] = $this->normalizeCertificate($certContent);
                Log::debug('SAML: Loaded IDP certificate from SURF_PUBLIC_CERT environment variable');
            } elseif (!empty(config('saml.surf.public_cert_path'))) {
                // Then try file path
                $originalPath = config('saml.surf.public_cert_path');
                $certPath = $originalPath;
                
                // storage_path() returns absolute path, so if it's already that, use it
                // Otherwise try different path resolutions
                $pathsToTry = [
                    $originalPath, // Try as-is first
                ];
                
                // If it's not absolute, try relative to base_path
                if (!str_starts_with($originalPath, '/')) {
                    $pathsToTry[] = base_path($originalPath);
                }
                
                // Also try direct storage_path
                if (str_contains($originalPath, 'storage/')) {
                    $pathsToTry[] = storage_path(str_replace('storage/', '', $originalPath));
                }
                
                // Try storage_path('app/saml/surf_public.crt') directly
                $pathsToTry[] = storage_path('app/saml/surf_public.crt');
                
                $found = false;
                foreach ($pathsToTry as $tryPath) {
                    if (file_exists($tryPath) && is_readable($tryPath)) {
                        $certPath = $tryPath;
                        $found = true;
                        break;
                    }
                }
                
                if ($found) {
                    $fileContent = file_get_contents($certPath);
                    if (!empty($fileContent)) {
                        $normalized = $this->normalizeCertificate($fileContent);
                        if (!empty($normalized)) {
                            $config['idp']['x509cert'] = $normalized;
                            Log::debug("SAML: Loaded IDP certificate from file: {$certPath} (" . strlen($config['idp']['x509cert']) . " chars)");
                        } else {
                            Log::error("SAML SURF public certificate file content is invalid after normalization: {$certPath}");
                        }
                    } else {
                        Log::error("SAML SURF public certificate file is empty: {$certPath}");
                    }
                } else {
                    Log::error("SAML SURF public certificate not found. Tried paths: " . implode(', ', $pathsToTry));
                    Log::error("SAML authentication will fail without the IDP certificate. Run: php artisan saml:install");
                }
            }
        } else {
            // Normalize existing certificate content
            $config['idp']['x509cert'] = $this->normalizeCertificate($config['idp']['x509cert']);
        }

        return $config;
    }

    /**
     * Build RelayState string that survives the redirect to IdP and back.
     * Encodes guard and return URL so we don't depend on session (session is often lost on cross-domain redirect).
     */
    protected function buildRelayState(string $guard, string $returnUrl): string
    {
        return base64_encode(json_encode([
            'guard' => $guard,
            'return' => $returnUrl,
        ]));
    }

    /**
     * Parse RelayState from IdP response. Returns [guard, returnUrl] or null if not our format.
     */
    protected function parseRelayState(?string $relayState): ?array
    {
        if (empty($relayState)) {
            return null;
        }
        $decoded = @json_decode(base64_decode($relayState, true) ?: '', true);
        if (is_array($decoded) && isset($decoded['guard'], $decoded['return'])) {
            return [(string) $decoded['guard'], (string) $decoded['return']];
        }
        return null;
    }

    /**
     * Initiate SAML SSO login
     */
    public function login(Request $request)
    {
        $this->ensureSamlEnabled();
        
        $guard = $request->get('guard', 'students');
        $returnUrl = $request->get('return', '/');

        // Store in session as fallback (in case RelayState is not echoed back by IdP)
        session(['saml_return_url' => $returnUrl, 'saml_guard' => $guard]);

        // RelayState is sent to IdP and returned in the response – use it so we don't depend on session
        $relayState = $this->buildRelayState($guard, $returnUrl);

        try {
            $samlAuth = $this->getSamlAuth($guard);
            $samlAuth->login($relayState);

            // Store the request ID for validation in ACS
            $requestId = $samlAuth->getLastRequestID();
            if ($requestId) {
                session(['saml_request_id' => $requestId]);
                Log::debug('SAML login initiated with request ID: ' . $requestId);
            }
        } catch (\Exception $e) {
            Log::error('SAML login error: ' . $e->getMessage());
            Log::error('SAML login error trace: ' . $e->getTraceAsString());
            return redirect('/')->with('error', 'Authentication failed. Please try again.');
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
        if ($parsed !== null) {
            [$guard, $returnUrl] = $parsed;
            Log::info('SAML ACS: Using guard and return URL from RelayState', ['guard' => $guard, 'returnUrl' => $returnUrl]);
        } else {
            $guard = session('saml_guard', 'students');
            $returnUrl = session('saml_return_url', '/');
            Log::info('SAML ACS: Using guard and return URL from session (RelayState missing or invalid)', ['guard' => $guard, 'returnUrl' => $returnUrl, 'relayStatePresent' => $relayStateRaw !== null]);
        }
        $requestId = session('saml_request_id');

        try {
            $samlAuth = $this->getSamlAuth($guard);

            if (config('saml.settings.strict', false) || env('SAML_DEBUG', false)) {
                Log::debug('SAML ACS: Request ID from session: ' . ($requestId ?? 'none'));
                if ($request->has('SAMLResponse')) {
                    Log::debug('SAML ACS: SAMLResponse POST parameter present');
                    $responsePreview = substr($request->input('SAMLResponse'), 0, 500);
                    Log::debug('SAML ACS: Response preview: ' . $responsePreview . '...');
                }
            }

            $samlAuth->processResponse($requestId);

            $errors = $samlAuth->getErrors();

            if (!empty($errors)) {
                $errorReason = $samlAuth->getLastErrorReason();
                $errorException = $samlAuth->getLastErrorException();
                
                Log::error('SAML ACS errors: ' . implode(', ', $errors));
                if ($errorReason) {
                    Log::error('SAML ACS error reason: ' . $errorReason);
                }
                if ($errorException) {
                    Log::error('SAML ACS error exception: ' . $errorException->getMessage());
                    Log::error('SAML ACS error exception trace: ' . $errorException->getTraceAsString());
                }
                
                // Always log detailed certificate comparison for signature errors
                $lastResponseXML = $samlAuth->getLastResponseXML();
                if ($lastResponseXML) {
                    try {
                        $responseDoc = new \DOMDocument();
                        $responseDoc->loadXML($lastResponseXML);
                        $xpath = new \DOMXPath($responseDoc);
                        $xpath->registerNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');
                        $xpath->registerNamespace('saml', 'urn:oasis:names:tc:SAML:2.0:assertion');
                        
                        // Find certificates in the response
                        $certNodes = $xpath->query('//ds:X509Certificate');
                        Log::error('SAML ACS: Found ' . $certNodes->length . ' certificate(s) in response');
                        
                        if ($certNodes->length > 0) {
                            for ($i = 0; $i < $certNodes->length; $i++) {
                                $responseCert = $certNodes->item($i)->nodeValue;
                                $responseCertClean = preg_replace('/\s+/', '', $responseCert);
                                Log::error("SAML ACS: Response cert #{$i} (first 100 chars): " . substr($responseCertClean, 0, 100));
                            }
                            
                            // Compare with our certificate
                            $idpData = $samlAuth->getSettings()->getIdPData();
                            $ourCert = $idpData['x509cert'] ?? '';
                            if ($ourCert) {
                                $ourCertClean = preg_replace('/\s+/', '', $ourCert);
                                Log::error('SAML ACS: Our configured cert (first 100 chars): ' . substr($ourCertClean, 0, 100));
                                
                                $foundMatch = false;
                                for ($i = 0; $i < $certNodes->length; $i++) {
                                    $responseCertClean = preg_replace('/\s+/', '', $certNodes->item($i)->nodeValue);
                                    if ($ourCertClean === $responseCertClean) {
                                        Log::error("SAML ACS: Certificate #{$i} MATCHES our configured certificate!");
                                        $foundMatch = true;
                                        break;
                                    }
                                }
                                
                                if (!$foundMatch) {
                                    Log::error('SAML ACS: NONE of the response certificates match our configured certificate!');
                                    Log::error('SAML ACS: This suggests the wrong certificate was extracted from SURF metadata.');
                                    Log::error('SAML ACS: Please re-extract the certificate from SURF metadata or check if multiple certificates are needed.');
                                }
                            }
                        } else {
                            Log::error('SAML ACS: No X509Certificate found in response - response may not be signed');
                        }
                    } catch (\Exception $e) {
                        Log::error('SAML ACS: Could not extract certificate from response: ' . $e->getMessage());
                    }
                }
                
                // Log full response XML if debug is enabled
                if (config('saml.settings.strict', false) || env('SAML_DEBUG', false)) {
                    if ($lastResponseXML) {
                        Log::debug('SAML ACS Last Response XML: ' . $lastResponseXML);
                    }
                }
                
                return redirect('/')->with('error', 'Authentication failed. Please try again.');
            }

            if (!$samlAuth->isAuthenticated()) {
                return redirect('/')->with('error', 'Authentication failed. Please try again.');
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
                return redirect('/')->with('error', 'Authentication failed: Missing user identifier.');
            }

            // Handle authentication based on guard
            if ($guard === 'students') {
                return $this->handleStudentsAuth($persistentId, $eduAffiliation, $email, $returnUrl);
            } else {
                return $this->handleAdminAuth($persistentId, $email, $returnUrl);
            }
        } catch (\Exception $e) {
            Log::error('SAML ACS error: ' . $e->getMessage());
            return redirect('/')->with('error', 'Authentication failed. Please try again.');
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
            if (empty($email)) {
                Log::warning('SAML Admin: No email in response and no user linked to this SURF identity.');
                return redirect('/admin/login')->with('error', 'SURF Conext did not provide your email. For first-time admin login we need the mail attribute. Please log in with your password once, or ask your administrator to request the mail attribute for this application in SURF Conext.');
            }
            Log::warning('SAML Admin: User not found with email: ' . $email);
            return redirect('/admin/login')->with('error', 'No account found with this email address.');
        }

        // Check if user has access to admin panel
        $panel = \Filament\Facades\Filament::getPanel('admin');
        if ($panel && !$user->canAccessPanel($panel)) {
            return redirect('/admin/login')->with('error', 'You do not have access to the admin panel.');
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
