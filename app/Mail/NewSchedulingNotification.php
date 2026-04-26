<?php

namespace App\Mail;

use App\Models\Scheduling;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewSchedulingNotification extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Scheduling $scheduling)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Novo Agendamento Criado');
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-scheduling');
    }
}
