<?php

declare(strict_types=1);

namespace MulerTech\FileBundle\Storage;

/**
 * An upload context that decides itself where its file is stored and how its name starts,
 * for a bundle whose files must be laid out the same way on every site, whatever the
 * site's directory strategy. The strategy is not consulted for such a context.
 */
interface StorageTargetInterface
{
    /**
     * Relative to the storage directory, segments of letters, digits, `_` and `-` joined
     * by `/`: `blog/images`.
     */
    public function getRelativeDirectory(): string;

    /**
     * Start of the stored name, before the unique part and the extension, in letters,
     * digits, `_` and `-`: `image-12-` gives `image-12-6650c1f2a3b4e1.23456789.webp`.
     */
    public function getFilenamePrefix(): string;
}
