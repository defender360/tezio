<?php

namespace App\Mail;

use App\Models\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SlaBreachNotification extends Mailable
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
        $prefix = $this->notification->type === 'sla_breach' ? '[URGENT]' : '[WARNING]';
        $subject = "{$prefix} SLA {$this->getAction()}: {$this->variables['sla_name']} for {$this->variables['related_type']} #{$this->variables['related_number']}";

        return new Envelope(
            subject: $subject,
            priority: $this->notification->type === 'sla_breach' ? 1 : 2, // High priority for breach
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = match ($this->notification->type) {
            'sla_breach' => 'emails.sla.breach',
            'sla_warning' => 'emails.sla.warning',
            default => 'emails.sla.generic',
        };

        return new Content(
            view: $view,
            with: [
                'notification' => $this->notification,
                'variables' => $this->variables,
                'severity_color' => $this->getSeverityColor(),
                'action_text' => $this->getActionText(),
                'related_url' => $this->getRelatedUrl(),
                'is_breach' => $this->notification->type === 'sla_breach',
            ],
        );
    }

    /**
     * Get action text based on notification type.
     */
    protected function getAction(): string
    {
        return $this->notification->type === 'sla_breach' ? 'Breach' : 'at Risk';
    }

    /**
     * Get action text for email template.
     */
    protected function getActionText(): string
    {
        return $this->notification->type === 'sla_breach' 
            ? 'has been breached and requires immediate attention'
            : 'is at risk of breaching and needs your attention';
    }

    /**
     * Get severity color for email template.
     */
    protected function getSeverityColor(): string
    {
        return $this->notification->type === 'sla_breach' ? '#dc3545' : '#ffc107';
    }

    /**
     * Get URL for the related entity.
     */
    protected function getRelatedUrl(): string
    {
        $type = $this->variables['related_type'] ?? '';
        $number = $this->variables['related_number'] ?? '';
        
        return $this->variables['app_url'] . '/' . $type . 's/' . $number;
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