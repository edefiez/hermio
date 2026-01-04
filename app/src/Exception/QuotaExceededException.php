<?php

namespace App\Exception;

class QuotaExceededException extends \RuntimeException
{
    private array $messageData = [];

    public function __construct(string $message = "Quota limit exceeded", int $code = 0, ?\Throwable $previous = null, array $messageData = [])
    {
        parent::__construct($message, $code, $previous);
        $this->messageData = $messageData;
    }

    public function getMessageData(): array
    {
        return $this->messageData;
    }
}

