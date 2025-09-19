<?php

// Docs: https://symfony.com/doc/current/mailer.html

namespace App\Core\Mailer;

use App\Core\Support\Config;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Exception;

class Email
{

    protected $transport;
    protected $mailer;

    public function __construct()
    {
        $this->transport = Transport::fromDsn($this->__getSettings());
        $this->mailer = new Mailer($this->transport);
    }

    public function send()
    {
        try {
            $email = (new SymfonyEmail())
                ->from(new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')))
                ->to('your-email@here.test')
                ->priority(SymfonyEmail::PRIORITY_HIGHEST)
                ->subject('My first mail using Symfony Mailer')
                ->text('This is an important message!')
                ->html('<strong>This is an important message!</strong>');

            // // Attachments
            // $email->addPart(new DataPart(new File('/path/to/documents/terms-of-use.pdf')));

            // // get the image contents from an existing file
            // $email->addPart((new DataPart(new File('/path/to/images/signature.gif'), 'footer-signature', 'image/gif'))->asInline());

            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
            throw new Exception('Error send email: ' . $e->getMessage());
        }
    }

    private function __getSettings()
    {
        $mailer = Config::get('default_mailer');
        $dsn = "sendmail://default";

        if ($mailer === "mailpit") {
            // Default mailpit smtp
            $dsn = "smtp://localhost:" . env('MAIL_PORT');
        }

        if ($mailer === "mailtrap") {
            $password = Config::get('mailer.mailtrap.password');

            $dsn = "mailtrap+smtp://$password@default";
        }

        if ($mailer === "smtp") {
            $username = Config::get('mailer.smtp.username');
            $password = Config::get('mailer.smtp.password');
            $host = Config::get('mailer.smtp.host');
            $port = Config::get('mailer.smtp.port');

            $dsn = "smtp://$username:$password@$host:$port";
        }

        return $dsn;
    }
}
