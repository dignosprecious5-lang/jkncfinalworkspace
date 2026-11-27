<?php

namespace App\Services;

use Illuminate\Contracts\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;
use Throwable;

class EmailNotificationService
{
    /**
     * List of disallowed mock/dummy email domains and placeholders.
     */
    protected const DISALLOWED_PLACEHOLDERS = [
        'management@company.com',
        'test@example.com',
        'sample@example.com',
        'dummy@example.com',
        'company1@example.com',
        'company2@example.com',
        'company3@example.com',
    ];

    /**
     * Validate and normalize a recipient email address.
     *
     * @throws InvalidArgumentException
     */
    public static function validateRecipient(?string $email): string
    {
        $email = trim((string) $email);

        if ($email === '') {
            throw new InvalidArgumentException('Recipient email address cannot be empty.');
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("The email address '{$email}' is not in a valid email format.");
        }

        $lower = strtolower($email);

        if (in_array($lower, self::DISALLOWED_PLACEHOLDERS, true)) {
            throw new InvalidArgumentException("Cannot send to placeholder/sample email address '{$email}'. Please provide a real recipient email address.");
        }

        return $email;
    }

    /**
     * Check if an email address is valid without throwing an exception.
     */
    public static function isValidRecipient(?string $email): bool
    {
        try {
            self::validateRecipient($email);
            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Send an HTML email to a dynamic recipient.
     *
     * @param string $recipientEmail The exact recipient address entered or selected
     * @param string $subject The subject line
     * @param string $htmlContent HTML body content
     * @param array $options Optional settings: from_address, from_name, attachments, reply_to
     * @return array [success => bool, recipient => string, error => ?string]
     */
    public function sendHtml(string $recipientEmail, string $subject, string $htmlContent, array $options = []): array
    {
        try {
            $validatedRecipient = self::validateRecipient($recipientEmail);
        } catch (InvalidArgumentException $e) {
            Log::warning('Email delivery validation failed: ' . $e->getMessage(), [
                'recipient' => $recipientEmail,
                'subject' => $subject,
            ]);

            return [
                'success' => false,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
            ];
        }

        $fromAddress = $options['from_address'] ?? config('mail.from.address', 'no-reply@ordo.jknc.io');
        $fromName = $options['from_name'] ?? config('mail.from.name', 'ORDO System');
        $attachments = $options['attachments'] ?? [];

        try {
            Mail::html($htmlContent, function ($message) use ($validatedRecipient, $subject, $fromAddress, $fromName, $options, $attachments) {
                $message
                    ->from($fromAddress, $fromName)
                    ->to($validatedRecipient)
                    ->subject($subject);

                if (! empty($options['reply_to'])) {
                    $message->replyTo($options['reply_to'], $options['reply_to_name'] ?? null);
                }

                foreach ($attachments as $attachment) {
                    if (isset($attachment['data'], $attachment['name'])) {
                        $message->attachData($attachment['data'], $attachment['name'], [
                            'mime' => $attachment['mime'] ?? 'application/pdf',
                        ]);
                    } elseif (isset($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    }
                }
            });

            Log::info("Email successfully sent to recipient: {$validatedRecipient}", [
                'recipient' => $validatedRecipient,
                'subject' => $subject,
                'mailer' => config('mail.default'),
            ]);

            return [
                'success' => true,
                'recipient' => $validatedRecipient,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $errorMessage = $this->sanitizeErrorMessage($e->getMessage());

            Log::error("Email delivery failed for recipient {$validatedRecipient}: {$errorMessage}", [
                'recipient' => $validatedRecipient,
                'subject' => $subject,
                'mailer' => config('mail.default'),
                'exception' => get_class($e),
            ]);

            return [
                'success' => false,
                'recipient' => $validatedRecipient,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Send a raw plain text email to a dynamic recipient.
     */
    public function sendRaw(string $recipientEmail, string $subject, string $textContent, array $options = []): array
    {
        try {
            $validatedRecipient = self::validateRecipient($recipientEmail);
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
            ];
        }

        $fromAddress = $options['from_address'] ?? config('mail.from.address', 'no-reply@ordo.jknc.io');
        $fromName = $options['from_name'] ?? config('mail.from.name', 'ORDO System');

        try {
            Mail::raw($textContent, function ($message) use ($validatedRecipient, $subject, $fromAddress, $fromName) {
                $message
                    ->from($fromAddress, $fromName)
                    ->to($validatedRecipient)
                    ->subject($subject);
            });

            Log::info("Raw email successfully sent to recipient: {$validatedRecipient}", [
                'recipient' => $validatedRecipient,
                'subject' => $subject,
            ]);

            return [
                'success' => true,
                'recipient' => $validatedRecipient,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $errorMessage = $this->sanitizeErrorMessage($e->getMessage());

            Log::error("Raw email delivery failed for recipient {$validatedRecipient}: {$errorMessage}");

            return [
                'success' => false,
                'recipient' => $validatedRecipient,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Send a Mailable instance to a dynamic recipient.
     */
    public function sendMailable(string $recipientEmail, Mailable $mailable): array
    {
        try {
            $validatedRecipient = self::validateRecipient($recipientEmail);
        } catch (InvalidArgumentException $e) {
            return [
                'success' => false,
                'recipient' => $recipientEmail,
                'error' => $e->getMessage(),
            ];
        }

        try {
            Mail::to($validatedRecipient)->send($mailable);

            Log::info("Mailable successfully sent to recipient: {$validatedRecipient}", [
                'recipient' => $validatedRecipient,
                'mailable' => get_class($mailable),
            ]);

            return [
                'success' => true,
                'recipient' => $validatedRecipient,
                'error' => null,
            ];
        } catch (Throwable $e) {
            $errorMessage = $this->sanitizeErrorMessage($e->getMessage());

            Log::error("Mailable delivery failed for recipient {$validatedRecipient}: {$errorMessage}", [
                'recipient' => $validatedRecipient,
                'mailable' => get_class($mailable),
            ]);

            return [
                'success' => false,
                'recipient' => $validatedRecipient,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Send a test verification email to confirm SMTP connectivity and delivery.
     */
    public function sendTestVerification(string $recipientEmail): array
    {
        $validatedRecipient = self::validateRecipient($recipientEmail);

        $mailer = config('mail.default');
        $host = config("mail.mailers.{$mailer}.host", 'N/A');
        $port = config("mail.mailers.{$mailer}.port", 'N/A');
        $encryption = config("mail.mailers.{$mailer}.encryption", 'None');
        $from = config('mail.from.address');
        $fromName = config('mail.from.name');

        $html = "
        <div style='font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #e2e8f0; border-radius: 12px; background: #ffffff;'>
            <div style='padding-bottom: 16px; border-bottom: 2px solid #2563eb;'>
                <h2 style='color: #1e293b; margin: 0 0 6px;'>Email Notification Delivery Test</h2>
                <p style='color: #64748b; font-size: 14px; margin: 0;'>JK&C ORDO System Notification Test</p>
            </div>
            <div style='padding: 20px 0;'>
                <p style='color: #334155; font-size: 15px; line-height: 1.6;'>
                    This email confirms that the email notification service has successfully verified recipient delivery.
                </p>
                <table style='width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 13px; color: #475569;'>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0; font-weight: 600; width: 140px;'>Recipient:</td>
                        <td style='padding: 8px 0; color: #0f172a;'><strong>{$validatedRecipient}</strong></td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0; font-weight: 600;'>Transport / Mailer:</td>
                        <td style='padding: 8px 0;'>{$mailer}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0; font-weight: 600;'>Server Host:</td>
                        <td style='padding: 8px 0;'>{$host}:{$port}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0; font-weight: 600;'>Encryption:</td>
                        <td style='padding: 8px 0;'>{$encryption}</td>
                    </tr>
                    <tr style='border-bottom: 1px solid #f1f5f9;'>
                        <td style='padding: 8px 0; font-weight: 600;'>Sender (From):</td>
                        <td style='padding: 8px 0;'>{$fromName} &lt;{$from}&gt;</td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; font-weight: 600;'>Timestamp:</td>
                        <td style='padding: 8px 0;'>" . now()->toRfc2822String() . "</td>
                    </tr>
                </table>
            </div>
            <div style='padding-top: 16px; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center;'>
                This is an automated system notification dispatched directly to the dynamic recipient address.
            </div>
        </div>
        ";

        return $this->sendHtml($validatedRecipient, 'ORDO Email Notification Service Verification', $html, [
            'from_address' => $from,
            'from_name' => $fromName,
        ]);
    }

    /**
     * Sanitize error message to avoid exposing SMTP passwords or secrets in error outputs.
     */
    protected function sanitizeErrorMessage(string $message): string
    {
        // Remove password=... or auth secrets
        $sanitized = preg_replace('/password=[^\s&]+/i', 'password=******', $message);
        $sanitized = preg_replace('/api[-_]?key=[^\s&]+/i', 'api_key=******', $sanitized);

        return $sanitized ?: 'Unknown email transmission error.';
    }
}
