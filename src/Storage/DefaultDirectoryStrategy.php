<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Storage;

use MulerTech\FileBundle\Model\FileUploaderInterface;

final class DefaultDirectoryStrategy implements DirectoryStrategyInterface
{
    public function getRelativeDirectory(FileUploaderInterface $uploader, ?object $context = null): string
    {
        return sprintf('uploader_%d', $uploader->getId());
    }
}
