<?php

// Docs: https://symfony.com/doc/current/mailer.html

namespace App\Core\Mailer;

use App\Core\Support\Config;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email as SymfonyEmail;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\File;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Exception;

/**
 * Mailer transport
 */
class Email
{

    protected $transport;
    protected $mailer;
    protected SymfonyEmail $email;

    public function __construct()
    {
        $this->transport = Transport::fromDsn($this->__getSettings());
        $this->mailer = new Mailer($this->transport);
    }

    /**  
     * Handles to sending email.  
     *  
     */
    public function send()
    {
        try {
            
            $this->mailer->send($this->email);

        } catch (TransportExceptionInterface $e) {
            // some error prevented the email sending; display an
            // error message or try to resend the message
            if (config('app.debug')) {
                \App\Core\Support\Log::error([
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    // 'trace' => $e->getTraceAsString(),
                ], 'Mailer.Email.send');
            }

            throw new Exception('Error send email: ' . $e->getMessage());
        }
    }

    /**  
     * Handles to build email message.  
     *  
     * @param string $from Set sender of email.
     * @param string $to Set email rescepients.
     * @param string $subject Set email subject.
     * @param string $bodyText Set message text.
     * @param string $bodyHtml Set message html.
     * @param array $attachment Set message attachments.
     * @param array $image Set message images.
     * 
     */
    public function prepareData(string $from = '', string $to, string $subject, $bodyText = '', $bodyHtml = '', array $attachment = [], array $image = [])
    {
        $email = new SymfonyEmail();

        $email->priority(SymfonyEmail::PRIORITY_HIGHEST);
        $email->subject($subject);
        $email->text($bodyText);
        $email->html($bodyHtml);

        if(empty($from)) {
            $email->from(new Address(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME')));
        } else {
            if(is_string($from)) {
                $from = explode(",", $from);

                if(! isset($from[0]) || ! isset($from[1]))
                    throw new Exception('Invalid sender email address.!');

                $email->from(new Address($from[0], $from[1]));
            } else {
                throw new Exception('Invalid sender email address.!');
            }
        }

        if(is_string($to)) {
            $to = explode(",", $to);

            if(! isset($to[0]) || ! isset($to[1]))
                    throw new Exception('Invalid recepient email address.!');

            $email->to(new Address($to[0], $to[1]));
        }  else {
            throw new Exception('Invalid recepient email address.!');
        }                

        // Attachments
        if(count($attachment)) {
            foreach($attachment as $file)
                $email->addPart(new DataPart(new File($file)));
        }
        
        // get the image contents from an existing file
        if(count($image)) {
            foreach($image as $file)
                $email->addPart((new DataPart(new File('/path/to/images/signature.gif'), 'footer-signature', 'image/gif'))->asInline());
        }

        $this->email = $email;
    }

    /**  
     * Handles to build dsn transport.  
     *  
     */
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
