<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BroadcastMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $emailSubject;
    public string $emailBody;
    public string $senderName;
    public array $attachmentFiles;

    /**
     * Create a new message instance.
     *
     * @param array<int, array{path: string, name: string, mime: string}> $attachmentFiles
     */
    public function __construct(string $emailSubject, string $emailBody, string $senderName, array $attachmentFiles = [])
    {
        $this->emailSubject = $emailSubject;
        $this->emailBody = $emailBody;
        $this->senderName = $senderName;
        $this->attachmentFiles = $attachmentFiles;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->emailSubject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.broadcast',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        $mailAttachments = [];

        foreach ($this->attachmentFiles as $file) {
            if (isset($file['path']) && file_exists($file['path'])) {
                $mailAttachments[] = Attachment::fromPath($file['path'])
                    ->as($file['name'] ?? basename($file['path']))
                    ->withMime($file['mime'] ?? 'application/octet-stream');
            }
        }

        return $mailAttachments;
    }
}
