<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class IncidentNotification extends Mailable
{
    use Queueable, SerializesModels;

    public Notification $notification;
    public array $variables;

    /**
     * Create a new message instance.
     */
    public function __construct(Notification $notification, array $variables)
    {
        $this->notification = $notification;
        $this->variables = $variables;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->notification->type) {
            'incident_created' => "[{$this->variables['priority']}] New Incident #{$this->variables['incident_number']}: {$this->variables['title']}",
            'incident_updated' => "Incident #{$this->variables['incident_number']} Updated: {$this->variables['title']}",
            'incident_resolved' => "Incident #{$this->variables['incident_number']} Resolved: {$this->variables['title']}",
            default => 'Incident Notification',
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = match ($this->notification->type) {
            'incident_created' => 'emails.incidents.created',
            'incident_updated' => 'emails.incidents.updated',
            'incident_resolved' => 'emails.incidents.resolved',
            default => 'emails.incidents.generic',
        };

        return new Content(
            view: $view,
            with: [
                'notification' => $this->notification,
                'variables' => $this->variables,
                'priority_color' => $this->getPriorityColor(),
                'incident_url' => $this->variables['app_url'] . '/incidents/' . ($this->variables['incident_number'] ?? ''),
            ],
        );
    }

    /**
     * Get priority color for email template.
     */
    protected function getPriorityColor(): string
    {
        return match ($this->variables['priority'] ?? 'medium') {
            'critical' => '#721c24',
            'high' => '#dc3545',
            'medium' => '#ffc107',
            'low' => '#007bff',
            default => '#6c757d',
        };
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}