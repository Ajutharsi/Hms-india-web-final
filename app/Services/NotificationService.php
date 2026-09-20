<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\NotificationTemplate;

class NotificationService
{
    public function send(string $channel, string $recipient, string $template, array $variables = [], ?int $patientId = null): bool
    {
        $tmpl = NotificationTemplate::where('name', $template)->where('is_active', true)->first();
        if (!$tmpl) return false;

        $message = $this->replaceVariables($tmpl->{$channel . '_template'} ?? '', $variables);

        $log = NotificationLog::create([
            'patient_id' => $patientId,
            'channel'    => $channel,
            'recipient'  => $recipient,
            'template'   => $template,
            'message'    => $message,
            'status'     => 'pending',
        ]);

        try {
            // TODO: integrate Twilio/MSG91/SMTP here
            $log->update(['status' => 'sent', 'sent_at' => now()]);
            return true;
        } catch (\Exception $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
            return false;
        }
    }

    private function replaceVariables(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', $value, $template);
        }
        return $template;
    }
}
