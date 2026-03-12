<?php

namespace App\Helpers;

class SamlHelper
{
    /**
     * Check if SAML is enabled by verifying required environment variables are set.
     * When enabled, metadata (/saml/metadata) is available and SAML login flows work.
     */
    public static function isEnabled(): bool
    {
        return !empty(env('SURF_ENTITY_ID'))
            && !empty(env('SAML_SP_ENTITY_ID'))
            && !empty(env('SAML_SP_ACS_URL'));
    }

    /**
     * Check if SAML is required for accessing public pages (home, projects, etc.).
     * When false, the site is public: no redirect to SAML login, but metadata remains exposed.
     * Controlled by SAML_REQUIRE_LOGIN env (default: true when SAML is enabled).
     */
    public static function isLoginRequired(): bool
    {
        return self::isEnabled()
            && filter_var(env('SAML_REQUIRE_LOGIN', true), FILTER_VALIDATE_BOOLEAN);
    }
}
