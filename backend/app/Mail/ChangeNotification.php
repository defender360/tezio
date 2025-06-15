<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ChangeNotification extends Mailable
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
            'change_requested' => "New Change Request #{$this->variables['change_number']}: {$this->variables['title']}",
            'change_approved' => "Change Request #{$this->variables['change_number']} Approved: {$this->variables['title']}",
            'change_rejected' => "Change Request #{$this->variables['change_number']} Rejected: {$this->variables['title']}",
            'change_completed' => "Change #{$this->variables['change_number']} Completed: {$this->variables['title']}",
            'change_cancelled' => "Change #{$this->variables['change_number']} Cancelled: {$this->variables['title']}",
            default => 'Change Notification',
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
            'change_requested' => 'emails.changes.requested',
            'change_approved' => 'emails.changes.approved',
            'change_rejected' => 'emails.changes.rejected',
            'change_completed' => 'emails.changes.completed',
            'change_cancelled' => 'emails.changes.cancelled',
            default => 'emails.changes.generic',
        };

        return new Content(
            view: $view,
            with: [
                'notification' => $this->notification,
                'variables' => $this->variables,
                'risk_color' => $this->getRiskColor(),
                'status_color' => $this->getStatusColor(),
                'change_url' => $this->variables['app_url'] . '/changes/' . ($this->variables['change_number'] ?? ''),
            ],
        );
    }

    /**
     * Get risk level color for email template.
     */
    protected function getRiskColor(): string
    {
        return match ($this->variables['risk_level'] ?? 'medium') {
            'critical' => '#721c24',
            'high' => '#dc3545',
            'medium' => '#ffc107',
            'low' => '#28a745',
            default => '#6c757d',
        };
    }

    /**
     * Get status color for email template.
     */
    protected function getStatusColor(): string
    {
        return match ($this->notification->type) {
            'change_approved' => '#28a745',
            'change_rejected' => '#dc3545',
            'change_completed' => '#20c997',
            'change_cancelled' => '#6c757d',
            default => '#17a2b8',
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