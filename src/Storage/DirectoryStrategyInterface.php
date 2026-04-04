<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Storage;

use MulerTech\FileBundle\Model\FileUploaderInterface;

interface DirectoryStrategyInterface
{
    public function getRelativeDirectory(FileUploaderInterface $uploader, ?object $context = null): string;
}
