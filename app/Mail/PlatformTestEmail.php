<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Global Settings' "Send Test Email" action — a real Mailable (not
 * `Mail::raw()`, which `Mail::fake()` cannot record for test assertions)
 * so the current mail configuration can genuinely be exercised end to end.
 * See SUPER_ADMIN_GLOBAL_SETTINGS_REPORT.md.
 */
class PlatformTestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(private readonly string $platformName)
    {
    }

    public function build(): self
    {
        return $this->subject('Test Email — ' . $this->platformName)
            ->text('emails.platform-test', ['platformName' => $this->platformName]);
    }
}
