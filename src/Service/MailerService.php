<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\MailerInterface;

class MailerService
{
    public function __construct(private MailerInterface $mailer)
    {
    }
    public function sendEmail($destinataire, $objet, $html): void
    {
        $email = (new Email())
            ->from('batantoud@gmail.com')
            ->to($destinataire)
            //->cc('cc@example.com')
            //->bcc('bcc@example.com')
            //->replyTo('fabien@example.com')
            //->priority(Email::PRIORITY_HIGH)
            ->subject($objet)
            // ->text('Sending emails is fun again!')
            ->html('<p>' . $html . '</p>');

        $this->mailer->send($email);
    }
}
