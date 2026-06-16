<?php

declare(strict_types=1);

namespace App\Service;

use App\Node\DirectoryNode;
use App\Node\FileNode;

/**
 * Walks a directory recursively and builds an in-memory tree of
 * {@see DirectoryNode}/{@see FileNode}, computing the MD5 of every file.
 *
 * It relies on the native SPL recursive iterators rather than a third-party
 * crawler: this keeps the runtime dependency-free, lowers memory usage and
 * speeds up traversal of large PrestaShop installations (tens of thousands of
 * files).
 */
class DirectoryTreeBuilder
{
    /**
     * Directory names skipped during traversal. Excluded folders are pruned
     * before descending into them, so their content is never hashed.
     *
     * @var list<string>
     */
    public const array DEFAULT_EXCLUDED = [
        '.git',
        '.idea',
        'var',
        'vendor',
    ];

    /** @var list<string> */
    private array $excluded;

    /**
     * @param list<string>|null $excluded Directory names to skip. When null,
     *                                    {@see self::DEFAULT_EXCLUDED} is used.
     */
    public function __construct(?array $excluded = null)
    {
        $this->excluded = $excluded ?? self::DEFAULT_EXCLUDED;
    }

    public function build(string $rootPath): DirectoryNode
    {
        $realRoot = realpath($rootPath);

        if ($realRoot === false || !is_dir($realRoot)) {
            throw new \InvalidArgumentException(
                sprintf('Path "%s" is not a readable directory.', $rootPath),
            );
        }

        $root = new DirectoryNode('root');
        $prefixLength = strlen($realRoot) + 1;
        $excluded = $this->excluded;

        $directories = new \RecursiveDirectoryIterator(
            $realRoot,
            \FilesystemIterator::SKIP_DOTS
            | \FilesystemIterator::UNIX_PATHS
            | \FilesystemIterator::FOLLOW_SYMLINKS,
        );

        $filter = new \RecursiveCallbackFilterIterator(
            $directories,
            static function (\SplFileInfo $current) use ($excluded): bool {
                if ($current->isDir()) {
                    return !in_array($current->getFilename(), $excluded, true);
                }

                return true;
            },
        );

        $iterator = new \RecursiveIteratorIterator($filter, \RecursiveIteratorIterator::LEAVES_ONLY);

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile()) {
                continue;
            }

            $pathname = $file->getPathname();
            $relativePath = substr($pathname, $prefixLength);

            $checksum = hash_file('md5', $pathname);

            if ($checksum === false) {
                throw new \RuntimeException(
                    sprintf('Unable to read file "%s" while computing its checksum.', $pathname),
                );
            }

            $directory = $this->resolveDirectory($root, $relativePath);
            $directory->addFile(new FileNode($file->getFilename(), $checksum));
        }

        return $root;
    }

    /**
     * Descends (creating intermediate nodes on the way) to the directory node
     * that should hold the file located at the given root-relative path.
     */
    private function resolveDirectory(DirectoryNode $root, string $relativePath): DirectoryNode
    {
        $separator = strrpos($relativePath, '/');

        if ($separator === false) {
            return $root;
        }

        $current = $root;

        foreach (explode('/', substr($relativePath, 0, $separator)) as $part) {
            if (!$current->hasDirectory($part)) {
                $current->addDirectory(new DirectoryNode($part));
            }

            /** @var DirectoryNode $current */
            $current = $current->getDirectory($part);
        }

        return $current;
    }
}
