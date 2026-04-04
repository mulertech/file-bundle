<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Model;

interface FileInterface
{
    public function getId(): ?int;

    public function getFilename(): string;

    public function setFilename(string $filename): static;

    public function getStoredFilename(): string;

    public function setStoredFilename(string $storedFilename): static;

    public function getFilepath(): string;

    public function setFilepath(string $filepath): static;

    public function getMimeType(): string;

    public function setMimeType(string $mimeType): static;

    public function getSize(): string;

    public function setSize(string $size): static;

    public function getExtension(): string;

    public function setExtension(string $extension): static;

    public function isPublic(): bool;

    public function setIsPublic(bool $isPublic): static;
}
