<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EngagementCompletionReportMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $serviceName,
        public readonly int $engagementId,
        public readonly int $totalTasks,
        public readonly string $completedAt
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Final Completion Summary Report — {$this->serviceName} (#{$this->engagementId})",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <div style='font-family: Arial, sans-serif; padding: 24px; color: #1f2937;'>
                    <h2 style='color: #166534;'>Engagement Final Report Generated</h2>
                    <p>All main activities and requirements for <strong>{$this->serviceName}</strong> have been marked as <strong>Completed</strong>.</p>
                    
                    <div style='background: #f3f4f6; padding: 16px; border-radius: 8px; margin: 20px 0;'>
                        <p style='margin: 4px 0;'><strong>Engagement ID:</strong> #{$this->engagementId}</p>
                        <p style='margin: 4px 0;'><strong>Total Operational Tasks Completed:</strong> {$this->totalTasks}</p>
                        <p style='margin: 4px 0;'><strong>Completion Timestamp:</strong> {$this->completedAt}</p>
                    </div>

                    <p>The system has compiled and attached this standard completion summary to the workspace record.</p>
                </div>
            "
        );
    }
}