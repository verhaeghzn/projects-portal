<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SendTestEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {email : The email address to send the test email to}';

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

        $validator = Validator::make(
            ['email' => $email],
            ['email' => ['required', 'email']]
        );

        if ($validator->fails()) {
            $this->error('Please provide a valid email address.');
            return Command::FAILURE;
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

            $this->info("Test email sent successfully to {$email}.");
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to send test email: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
