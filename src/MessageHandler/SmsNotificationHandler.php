<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\HttpClient\HttpClientSmsUser;
use App\Message\SmsNotification;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class SmsNotificationHandler implements MessageHandlerInterface
{
    private HttpClientSmsUser $httpClientSmsUser;

    public function __construct(HttpClientSmsUser $httpClientSmsUser)
    {
        $this->httpClientSmsUser = $httpClientSmsUser;
    }

    /**
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function __invoke(SmsNotification $message): void
    {
        $this->httpClientSmsUser->sendSms($message->getPhone(), $message->getContent());
    }
}
