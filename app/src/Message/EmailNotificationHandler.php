<?php

namespace App\Message;

use League\Flysystem\FilesystemException;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\Email;

#[AsMessageHandler]
class EmailNotificationHandler
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly FilesystemOperator $filesystem
    )
    {}

    /**
     * @throws FilesystemException
     * @throws TransportExceptionInterface
     */
    public function __invoke(EmailNotification $notification): void
    {
        $fileContent = $this->filesystem->read($notification->getFile());
        $email = (new Email())
            ->from('sender@example.com')
            ->to($notification->getEmail())
            ->subject('Your exported users ' . ucfirst($notification->getFormat()))
            ->attach($fileContent, 'users.' . $notification->getFormat());

        $this->mailer->send($email);
    }
}