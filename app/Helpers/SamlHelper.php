<?php

namespace App\Helpers;

class SamlHelper
{
    /**
     * Check if SAML is enabled.
     * When enabled, metadata (/saml/metadata) is available and SAML login flows work.
     *
     * Auto-detects from installed SP certificates unless SAML_ENABLED is set explicitly.
     */
    public static function isEnabled(): bool
    {
        $enabled = config('saml.enabled');
        if ($enabled !== null) {
            return filter_var($enabled, FILTER_VALIDATE_BOOLEAN);
        }

        return self::spCertificatesInstalled();
    }

    /**
     * Check if SAML is required for accessing public pages (home, projects, etc.).
     * When false, the site is public: no redirect to SAML login, but metadata remains exposed.
     * Controlled by SAML_REQUIRE_LOGIN env (default: true when SAML is enabled).
     */
    public static function isLoginRequired(): bool
    {
        return self::isEnabled()
            && filter_var(config('saml.require_login', true), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * SP key + cert must exist – signals that php artisan saml:install has been run.
     */
    protected static function spCertificatesInstalled(): bool
    {
        foreach (['private_key_path', 'public_cert_path'] as $key) {
            $path = config("saml.sp.{$key}");
            if (empty($path)) {
                return false;
            }

            if (! file_exists($path) && ! str_starts_with($path, '/')) {
                $path = base_path($path);
            }

            if (! file_exists($path)) {
                return false;
            }
        }

        return true;
    }
}
