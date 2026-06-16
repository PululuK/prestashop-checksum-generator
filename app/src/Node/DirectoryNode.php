<?php

declare(strict_types=1);

namespace App\Node;

/**
 * A node of the checksum tree representing a directory. It holds its child
 * directories and the files directly contained in it, both keyed by name.
 */
class DirectoryNode
{
    /** @var array<string, DirectoryNode> */
    protected array $directories = [];

    /** @var array<string, FileNode> */
    protected array $files = [];

    public function __construct(protected string $name)
    {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addDirectory(DirectoryNode $directoryNode): self
    {
        $this->directories[$directoryNode->getName()] = $directoryNode;

        return $this;
    }

    public function addFile(FileNode $fileNode): self
    {
        $this->files[$fileNode->getName()] = $fileNode;

        return $this;
    }

    public function hasDirectory(string $name): bool
    {
        return isset($this->directories[$name]);
    }

    public function getDirectory(string $name): ?DirectoryNode
    {
        return $this->directories[$name] ?? null;
    }

    /**
     * @return array<string, DirectoryNode>
     */
    public function getDirectories(): array
    {
        return $this->directories;
    }

    /**
     * @return array<string, FileNode>
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
