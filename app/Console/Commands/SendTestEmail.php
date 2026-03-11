<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test
                            {email : The email address to send the test email to}
                            {--log : Send to log driver instead (message written to storage/logs/laravel.log)}
                            {--show-config : Show current mail config and exit without sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to the given email address';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');

        if ($this->option('show-config')) {
            $this->showConfig();
            return Command::SUCCESS;
        }

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email']]
        );

        if ($validator->fails()) {
            $this->error('Please provide a valid email address.');
            return Command::FAILURE;
        }

        $useLogDriver = $this->option('log');
        if ($useLogDriver) {
            Config::set('mail.default', 'log');
        }

        try {
            Mail::raw(
                'This is a test email from the Projects Portal.' . "\n\n" .
                'If you received this, your mail configuration is working correctly.' . "\n\n" .
                'Sent at: ' . now()->toDateTimeString(),
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Test Email - Projects Portal');
                }
            );

            if ($useLogDriver) {
                $logPath = storage_path('logs/laravel.log');
                $this->info("Message written to log (no email sent). Check: {$logPath}");
                $this->line('Search for "Test Email - Projects Portal" or the recipient address.');
            } else {
                $this->info("Test email sent successfully to {$email}.");
                $this->line('If you don\'t receive it: check spam, run with <info>--log</info> to capture to the log file, or check server mail logs (e.g. Plesk mail log / Postfix).');
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function showConfig(): void
    {
        $from = config('mail.from');
        $this->table(
            ['Setting', 'Value'],
            [
                ['MAIL_MAILER (default)', config('mail.default')],
                ['From address', $from['address'] ?? '—'],
                ['From name', $from['name'] ?? '—'],
                ['Sendmail path', config('mail.mailers.sendmail.path') ?? '—'],
            ]
        );
        $this->line('Run with <info>--log</info> to send the message to storage/logs/laravel.log instead of the real mailer.');
    }
}
