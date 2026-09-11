namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StatusBroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $serviceName,
        public readonly string $oldStatus,
        public readonly string $newStatus,
        public readonly ?string $workspaceUrl = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Status Update: {$this->serviceName} is now {$this->newStatus}",
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <div style='font-family: Arial, sans-serif; padding: 20px;'>
                    <h2>Service Workspace Status Updated</h2>
                    <p>The status for <strong>{$this->serviceName}</strong> has been updated.</p>
                    <p><strong>Previous Status:</strong> {$this->oldStatus}</p>
                    <p><strong>New Status:</strong> <span style='color: #2563eb;'>{$this->newStatus}</span></p>
                    <p><a href='{$this->workspaceUrl}' style='padding: 10px 15px; background: #2563eb; color: #fff; text-decoration: none; border-radius: 5px;'>View Workspace</a></p>
                </div>
            "
        );
    }
}