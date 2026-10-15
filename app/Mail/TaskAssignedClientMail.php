<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TaskAssignedClientMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a task-assignment email for a client.
     */
    public function __construct(
        public readonly string $clientName,
        public readonly string $taskTitle,
        public readonly ?string $message = null,
        public readonly ?string $workspaceUrl = null,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New task assigned: {$this->taskTitle}",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            htmlString: $this->renderHtml(),
        );
    }

    private function renderHtml(): string
    {
        $clientName = e($this->clientName);
        $taskTitle = e($this->taskTitle);
        $message = $this->message === null || trim($this->message) === ''
            ? ''
            : '<p style="margin: 0 0 24px; color: #374151; line-height: 1.6;">'.nl2br(e($this->message)).'</p>';
        $workspaceLink = $this->workspaceUrl === null || trim($this->workspaceUrl) === ''
            ? ''
            : '<p style="margin: 0;"><a href="'.e($this->workspaceUrl).'" style="display: inline-block; padding: 12px 18px; background: #2563eb; color: #ffffff; text-decoration: none; border-radius: 6px;">View task</a></p>';

        return <<<HTML
            <!doctype html>
            <html lang="en">
            <body style="margin: 0; padding: 24px; background: #f3f4f6; font-family: Arial, sans-serif;">
                <main style="max-width: 600px; margin: 0 auto; padding: 32px; background: #ffffff; border-radius: 8px;">
                    <h1 style="margin: 0 0 20px; color: #111827; font-size: 24px;">A new task has been assigned</h1>
                    <p style="margin: 0 0 16px; color: #374151; line-height: 1.6;">Hello {$clientName},</p>
                    <p style="margin: 0 0 16px; color: #374151; line-height: 1.6;">You have been assigned the following task:</p>
                    <p style="margin: 0 0 24px; padding: 16px; background: #eff6ff; border-left: 4px solid #2563eb; color: #1e3a8a;"><strong>{$taskTitle}</strong></p>
                    {$message}
                    {$workspaceLink}
                </main>
            </body>
            </html>
            HTML;
    }
}
