<?php

declare(strict_types=1);

namespace App\Node;

/**
 * An immutable leaf of the checksum tree: a single file and its MD5 hash.
 */
readonly class FileNode
{
    public function __construct(
        protected string $name,
        protected string $checksum,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }
}
