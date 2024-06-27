<?php

namespace App\Message;

class EmailNotification
{
    public function __construct(
        private readonly string $file,
        private readonly string $email,
        private readonly string $format
    )
    {}

    public function getFile(): string
    {
        return $this->file;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getFormat(): string
    {
        return $this->format;
    }
}