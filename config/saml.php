<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SURF Conext Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SURF Conext SAML Identity Provider
    |
    */

    /*
    |--------------------------------------------------------------------------
    | SAML Enabled
    |--------------------------------------------------------------------------
    |
    | Set SAML_ENABLED=true/false to force SAML on or off. When unset, SAML is
    | auto-detected: enabled only when SP certificates exist (php artisan saml:install).
    |
    */

    'enabled' => env('SAML_ENABLED'),

    'surf' => [
        'entity_id' => env('SURF_ENTITY_ID', 'https://engine.surfconext.nl/authentication/idp/metadata'),
        'sso_url' => env('SURF_SSO_URL', 'https://engine.surfconext.nl/authentication/idp/single-sign-on'),
        'slo_url' => env('SURF_SLO_URL', 'https://engine.surfconext.nl/authentication/idp/single-logout'),
        'metadata_url' => env('SURF_METADATA_URL', 'https://metadata.surfconext.nl/idp-metadata.xml'),
        'public_cert_path' => env('SURF_PUBLIC_CERT_PATH', storage_path('app/saml/surf_public.crt')),
    ],

    /*
    |--------------------------------------------------------------------------
    | IdP Metadata Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long (seconds) to cache SURF signing certificates fetched from metadata.
    | Default: 24 hours. Run `php artisan saml:install --refresh-surf` to force refresh.
    |
    */

    'idp_metadata_cache_ttl' => env('SAML_IDP_METADATA_CACHE_TTL', 86400),

    /*
    |--------------------------------------------------------------------------
    | Service Provider (SP) Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for this application as a SAML Service Provider
    |
    */

    'sp' => [
        'entity_id' => env('SAML_SP_ENTITY_ID', rtrim(env('APP_URL', 'http://localhost'), '/') . '/saml/metadata'),
        'acs_url' => env('SAML_SP_ACS_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/saml/acs'),
        'sls_url' => env('SAML_SP_SLS_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/saml/sls'),
        'metadata_url' => env('SAML_SP_METADATA_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/saml/metadata'),
        'private_key_path' => env('SAML_SP_PRIVATE_KEY_PATH', storage_path('app/saml/sp_private.key')),
        'public_cert_path' => env('SAML_SP_PUBLIC_CERT_PATH', storage_path('app/saml/sp_public.crt')),
    ],

    /*
    |--------------------------------------------------------------------------
    | SAML Settings
    |--------------------------------------------------------------------------
    |
    | General SAML configuration options
    |
    */

    'settings' => [
        'strict' => env('SAML_STRICT', true),
        'debug' => env('SAML_DEBUG', false),
        'sp' => [
            'entityId' => env('SAML_SP_ENTITY_ID', rtrim(env('APP_URL', 'http://localhost'), '/') . '/saml/metadata'),
            'assertionConsumerService' => [
                'url' => env('SAML_SP_ACS_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/saml/acs'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST',
            ],
            'singleLogoutService' => [
                'url' => env('SAML_SP_SLS_URL', rtrim(env('APP_URL', 'http://localhost'), '/') . '/saml/sls'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'NameIDFormat' => 'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent',
            'x509cert' => env('SAML_SP_PUBLIC_CERT', ''),
            'privateKey' => env('SAML_SP_PRIVATE_KEY', ''),
        ],
        'idp' => [
            'entityId' => env('SURF_ENTITY_ID', 'https://engine.surfconext.nl/authentication/idp/metadata'),
            'singleSignOnService' => [
                'url' => env('SURF_SSO_URL', 'https://engine.surfconext.nl/authentication/idp/single-sign-on'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'singleLogoutService' => [
                'url' => env('SURF_SLO_URL', 'https://engine.surfconext.nl/authentication/idp/single-logout'),
                'binding' => 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect',
            ],
            'x509cert' => env('SURF_PUBLIC_CERT', ''),
        ],
        'security' => [
            'nameIdEncrypted' => false,
            'authnRequestsSigned' => true,
            'logoutRequestSigned' => true,
            'logoutResponseSigned' => true,
            'signMetadata' => false,
            'wantMessagesSigned' => false,
            'wantAssertionsSigned' => true,
            'wantAssertionsEncrypted' => false,
            'wantNameIdEncrypted' => false,
            'requestedAuthnContext' => false,
            'wantXMLValidation' => true,
            'relaxDestinationValidation' => false,
            'destinationStrictlyMatches' => true,
            'rejectUnsolicitedResponsesWithInResponseTo' => false,
            'signatureAlgorithm' => 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256',
            'digestAlgorithm' => 'http://www.w3.org/2001/04/xmlenc#sha256',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute Mapping
    |--------------------------------------------------------------------------
    |
    | Maps SURF Conext SAML attributes to application attributes
    |
    */

    'attributes' => [
        'email' => [
            'urn:mace:dir:attribute-def:mail',
            'urn:mace:dir:attribute-def:eduPersonPrincipalName',
        ],
        'persistent_id' => [
            'urn:oasis:names:tc:SAML:attribute:subject-id',
            'urn:mace:dir:attribute-def:eduPersonTargetedID',
            'urn:mace:dir:attribute-def:eduPersonUniqueId',
        ],
        'edu_affiliation' => [
            'urn:mace:dir:attribute-def:eduPersonScopedAffiliation',
            'urn:mace:dir:attribute-def:eduPersonAffiliation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard Configuration
    |--------------------------------------------------------------------------
    |
    | Determines which guard to use based on the authentication context
    |
    */

    'guard' => [
        'students' => 'students',
        'admin' => 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Require SAML Login for Public Pages
    |--------------------------------------------------------------------------
    |
    | When true, public routes redirect unauthenticated visitors to SURFconext.
    | When false, the site remains public while SAML metadata stays available.
    |
    */

    'require_login' => env('SAML_REQUIRE_LOGIN', true),
];
