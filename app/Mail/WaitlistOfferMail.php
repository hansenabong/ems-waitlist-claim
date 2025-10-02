<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\Event;

/**
 * Class WaitlistOfferMail
 *
 * Purpose:
 * - Email the next waitlisted attendee to notify them a seat is available,
 *   including a signed claim URL that expires at the same time as the DB hold.
 *
 * Notes:
 * - Uses both HTML and plain-text views so the message renders correctly in
 *   diverse mail clients and remains readable in the mail log driver.
 * - If you expect high volume, implement ShouldQueue to send asynchronously
 *   (swap to implements ShouldQueue and set QUEUE_CONNECTION).
 */

class WaitlistOfferMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Event $event, public string $claimUrl)
    {
        //
    }

    /**
     * Build the envelope (from, subject).
     *
     * Why:
     * - Centralizes sender identity and contextual subject so logs and inbox
     *   threading are meaningful for recipients and for debugging.
     *
     * @return Envelope
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('EMS@gmail.com', 'Hansen'),
            subject: 'A spot just opened: ' . $this->event->title
        );
    }

    /**
     * Define message content (HTML + plain text) and data bindings.
     *
     * Why:
     * - Provide both rich and fallback formats; pass only the data the view
     *   needs to render (event details and the signed claim URL).
     *
     * @return Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.waitlist.offer', //HTML
            text: 'emails.waitlist.offer_plain',  // Plain text
            with: [
                'event' => $this->event,
                'claimUrl' => $this->claimUrl
            ]
        );
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
