<?php

namespace App\Services;

class ValidationResult
{
    private bool $isValid;
    private ?string $message;

    private function __construct(bool $isValid, ?string $message = null)
    {
        $this->isValid = $isValid;
        $this->message = $message;
    }

    public static function valid(): self
    {
        return new self(true);
    }

    public static function invalid(string $message): self
    {
        return new self(false, $message);
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function toArray(): array
    {
        return [
            'valid' => $this->isValid,
            'message' => $this->message
        ];
    }
}