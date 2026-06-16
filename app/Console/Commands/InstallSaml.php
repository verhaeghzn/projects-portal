<?php

namespace App\Console\Commands;

use App\Services\Saml\SurfIdpCertificateLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class InstallSaml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'saml:install
                            {--force : Overwrite existing SP certificates}
                            {--refresh-surf : Download the latest SURF IdP signing certificate from metadata}
                            {--domain= : Domain name for the certificate (defaults to APP_URL domain)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install and configure SAML certificates for SURF Conext authentication';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Installing SAML configuration...');
        $this->newLine();

        // Create SAML directory
        $samlDir = storage_path('app/saml');
        if (!File::exists($samlDir)) {
            File::makeDirectory($samlDir, 0755, true);
            $this->info("✓ Created directory: {$samlDir}");
        } else {
            $this->line("Directory already exists: {$samlDir}");
        }

        // Generate SP certificates (unless only refreshing SURF cert)
        if (! $this->option('refresh-surf')) {
            $this->generateSpCertificates($samlDir);
        }

        $this->refreshSurfCertificate($samlDir);

        // Display configuration information
        $this->displayConfigurationInfo();

        $this->newLine();
        $this->info('✓ SAML installation complete!');
        $this->newLine();
        $this->comment('Next steps:');
        $this->line('1. Configure your .env file with SURF Conext settings');
        $this->line('2. Download SURF Conext certificate (see instructions above)');
        $this->line('3. Register your application in SURF Conext');
        $this->line('4. Provide your SP metadata URL: ' . config('app.url') . '/saml/metadata');

        return Command::SUCCESS;
    }

    protected function generateSpCertificates(string $samlDir): void
    {
        $privateKeyPath = $samlDir . '/sp_private.key';
        $publicCertPath = $samlDir . '/sp_public.crt';

        // Check if certificates already exist
        if (File::exists($privateKeyPath) && File::exists($publicCertPath) && !$this->option('force')) {
            $this->line('SP certificates already exist. Use --force to overwrite.');
            return;
        }

        $this->info('Generating SP certificates...');

        // Get domain from option or APP_URL
        $domain = $this->option('domain') ?: parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';

        // Generate private key
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKey = openssl_pkey_new($config);
        if (!$privateKey) {
            $this->error('Failed to generate private key: ' . openssl_error_string());
            return;
        }

        // Export private key
        openssl_pkey_export($privateKey, $privateKeyPem);
        File::put($privateKeyPath, $privateKeyPem);
        chmod($privateKeyPath, 0600); // Secure permissions
        $this->info("✓ Generated private key: {$privateKeyPath}");

        // Generate certificate signing request
        $dn = [
            'countryName' => 'NL',
            'stateOrProvinceName' => 'Netherlands',
            'localityName' => 'Eindhoven',
            'organizationName' => config('app.name', 'Projects Portal'),
            'organizationalUnitName' => 'IT',
            'commonName' => $domain,
            'emailAddress' => config('mail.from.address', 'admin@example.com'),
        ];

        $csr = openssl_csr_new($dn, $privateKey, $config);
        if (!$csr) {
            $this->error('Failed to generate CSR: ' . openssl_error_string());
            return;
        }

        // Generate self-signed certificate (valid for 1 year)
        $cert = openssl_csr_sign($csr, null, $privateKey, 365, $config);
        if (!$cert) {
            $this->error('Failed to generate certificate: ' . openssl_error_string());
            return;
        }

        // Export certificate
        openssl_x509_export($cert, $certPem);
        File::put($publicCertPath, $certPem);
        chmod($publicCertPath, 0644);
        $this->info("✓ Generated public certificate: {$publicCertPath}");

        // Display certificate info
        $certInfo = openssl_x509_parse($cert);
        $this->line("  Subject: {$certInfo['subject']['CN']}");
        $this->line("  Valid until: " . date('Y-m-d H:i:s', $certInfo['validTo_time_t']));
    }

    protected function refreshSurfCertificate(string $samlDir): void
    {
        $surfCertPath = $samlDir . '/surf_public.crt';
        $shouldDownload = $this->option('refresh-surf') || $this->option('force') || ! File::exists($surfCertPath);

        if (! $shouldDownload) {
            $this->line("✓ SURF Conext certificate found: {$surfCertPath}");
            $this->line('  Use --refresh-surf to download the latest certificate from metadata.');

            return;
        }

        $this->info('Downloading SURF Conext signing certificate from metadata...');

        try {
            $count = SurfIdpCertificateLoader::downloadToFile($surfCertPath);
            $this->info("✓ Downloaded {$count} signing certificate(s) to {$surfCertPath}");
        } catch (\Throwable $e) {
            $this->error('Failed to download SURF certificate: ' . $e->getMessage());
            $this->checkSurfCertificate($samlDir);
        }
    }

    protected function checkSurfCertificate(string $samlDir): void
    {
        $surfCertPath = $samlDir . '/surf_public.crt';

        if (File::exists($surfCertPath)) {
            $this->line("✓ SURF Conext certificate found: {$surfCertPath}");
        } else {
            $this->warn("⚠ SURF Conext certificate not found: {$surfCertPath}");
            $this->newLine();
            $this->line('  To download the SURF Conext certificate, use one of these methods:');
            $this->newLine();
            $this->line('  Method 1: Extract using xmllint (recommended, works on Linux and macOS)');
            $this->line('  Note: If SURF has multiple signing certificates, this extracts the FIRST one.');
            $this->line('  If signature validation fails, you may need to extract ALL signing certificates.');
            $this->line('  curl -s https://metadata.surfconext.nl/idp-metadata.xml | \\');
            $this->line('    xmllint --xpath \'//*[local-name()="KeyDescriptor" and @use="signing"]//*[local-name()="X509Certificate"]/text()\' - 2>/dev/null | \\');
            $this->line('    tr -d \' \\n\\r\' | \\');
            $this->line('    base64 -d 2>/dev/null | openssl x509 -inform DER -outform PEM > ' . $surfCertPath);
            $this->newLine();
            $this->line('  Method 1a: Extract ALL signing certificates (if multiple exist)');
            $this->line('  curl -s https://metadata.surfconext.nl/idp-metadata.xml | \\');
            $this->line('    xmllint --xpath \'//*[local-name()="KeyDescriptor" and @use="signing"]//*[local-name()="X509Certificate"]/text()\' - 2>/dev/null | \\');
            $this->line('    while read cert; do');
            $this->line('      echo "$cert" | tr -d \' \\n\\r\' | base64 -d | openssl x509 -inform DER -outform PEM >> ' . $surfCertPath);
            $this->line('      echo "" >> ' . $surfCertPath);
            $this->line('    done');
            $this->newLine();
            $this->line('  Method 1b: Alternative using sed/grep (Linux only, requires grep with -P support)');
            $this->line('  curl -s https://metadata.surfconext.nl/idp-metadata.xml | \\');
            $this->line('    sed -n \'/<md:KeyDescriptor[^>]*use="signing"/,/<\\/md:KeyDescriptor>/p\' | \\');
            $this->line('    grep -oP \'<ds:X509Certificate>(.*?)</ds:X509Certificate>\' | \\');
            $this->line('    sed \'s/<ds:X509Certificate>//;s/<\/ds:X509Certificate>//\' | \\');
            $this->line('    tr -d \' \\n\\r\' | \\');
            $this->line('    base64 -d 2>/dev/null | openssl x509 -inform DER -outform PEM > ' . $surfCertPath);
            $this->newLine();
            $this->line('  Method 2: Manual extraction');
            $this->line('  1. Visit: https://metadata.surfconext.nl/idp-metadata.xml');
            $this->line('  2. Find the <md:KeyDescriptor use="signing"> section');
            $this->line('  3. Copy the <ds:X509Certificate> content (base64, no line breaks)');
            $this->line('  4. Decode and convert: echo "CERT_CONTENT" | base64 -d | openssl x509 -inform DER -outform PEM > ' . $surfCertPath);
            $this->newLine();
            $this->line('  Method 3: Provide via environment variable');
            $this->line('  Set SURF_PUBLIC_CERT in your .env file with the PEM certificate content.');
        }
    }

    protected function displayConfigurationInfo(): void
    {
        $this->newLine();
        $this->info('SAML Configuration Information:');
        $this->newLine();

        $appUrl = rtrim(config('app.url'), '/');
        
        // Warn if using HTTP instead of HTTPS
        if (str_starts_with($appUrl, 'http://') && !str_contains($appUrl, 'localhost')) {
            $this->warn('⚠ Warning: APP_URL is set to HTTP instead of HTTPS!');
            $this->line('  For production, you should use HTTPS. Update your .env file:');
            $this->line('  APP_URL=https://your-domain.com');
            $this->newLine();
        }
        
        $spEntityId = $appUrl . '/saml/metadata';
        $acsUrl = $appUrl . '/saml/acs';
        $slsUrl = $appUrl . '/saml/sls';

        $this->table(
            ['Setting', 'Value'],
            [
                ['SP Entity ID', $spEntityId],
                ['ACS URL', $acsUrl],
                ['SLS URL', $slsUrl],
                ['Metadata URL', $appUrl . '/saml/metadata'],
                ['Private Key Path', storage_path('app/saml/sp_private.key')],
                ['Public Cert Path', storage_path('app/saml/sp_public.crt')],
                ['SURF Cert Path', storage_path('app/saml/surf_public.crt')],
            ]
        );

        $this->newLine();
        $this->comment('Add these to your .env file:');
        $this->newLine();
        
        // Check if APP_URL is HTTP and warn
        if (str_starts_with($appUrl, 'http://') && !str_contains($appUrl, 'localhost')) {
            $this->warn('⚠ IMPORTANT: Your APP_URL is set to HTTP. For production with SURF Conext, you MUST use HTTPS!');
            $this->line('  Update your .env file: APP_URL=https://your-domain.com');
            $this->newLine();
        }
        
        $this->line('# SURF Conext Configuration (required for SAML to be enabled)');
        $this->line("SURF_ENTITY_ID=https://engine.surfconext.nl/authentication/idp/metadata");
        $this->line("SURF_SSO_URL=https://engine.surfconext.nl/authentication/idp/single-sign-on");
        $this->line("SURF_SLO_URL=https://engine.surfconext.nl/authentication/idp/single-logout");
        $this->line("SURF_METADATA_URL=https://metadata.surfconext.nl/idp-metadata.xml");
        $this->line("SURF_PUBLIC_CERT_PATH=storage/app/saml/surf_public.crt");
        $this->newLine();
        $this->line('# Service Provider (SP) Configuration');
        $this->line('# Note: These will use APP_URL. Make sure APP_URL is set to HTTPS for production!');
        $this->line("SAML_SP_ENTITY_ID={$spEntityId}");
        $this->line("SAML_SP_ACS_URL={$acsUrl}");
        $this->line("SAML_SP_SLS_URL={$slsUrl}");
        $this->line("SAML_SP_METADATA_URL={$appUrl}/saml/metadata");
        $this->line("SAML_SP_PRIVATE_KEY_PATH=storage/app/saml/sp_private.key");
        $this->line("SAML_SP_PUBLIC_CERT_PATH=storage/app/saml/sp_public.crt");
        $this->newLine();
        $this->line('# Optional SAML Settings');
        $this->line("SAML_STRICT=true");
        $this->line("SAML_DEBUG=false");
        $this->newLine();
        $this->line('# IMPORTANT: Make sure APP_URL is set to HTTPS for production!');
        $this->line("APP_URL={$appUrl}");
    }
}
