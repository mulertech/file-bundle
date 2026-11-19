<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Exception;

use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * The file breaks a rule of the upload, which the person sending it can fix by sending another
 * one: nothing failed on the server. Every other FileException thrown by an upload is a fault.
 */
final class FileRejected extends FileException
{
    /**
     * @param int|null    $maxSize  in bytes, set when the file is too large
     * @param string|null $mimeType the detected type, set when the type is not allowed
     */
    private function __construct(
        string $message,
        public readonly FileRejectionReason $reason,
        public readonly string $filename,
        public readonly ?int $maxSize = null,
        public readonly ?string $mimeType = null,
    ) {
        parent::__construct($message);
    }

    public static function tooLarge(string $filename, int $maxSize, string $message): self
    {
        return new self($message, FileRejectionReason::TooLarge, $filename, maxSize: $maxSize);
    }

    public static function typeNotAllowed(string $filename, ?string $mimeType, string $message): self
    {
        return new self($message, FileRejectionReason::TypeNotAllowed, $filename, mimeType: $mimeType);
    }
}
