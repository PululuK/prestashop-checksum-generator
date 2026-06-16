<?php

declare(strict_types=1);

namespace App\Tests\Node;

use App\Node\DirectoryNode;
use App\Node\FileNode;
use PHPUnit\Framework\TestCase;

final class DirectoryNodeTest extends TestCase
{
    public function testItStoresFilesAndDirectoriesKeyedByName(): void
    {
        $root = new DirectoryNode('root');
        $child = new DirectoryNode('classes');
        $file = new FileNode('index.php', 'abc');

        $root->addDirectory($child);
        $root->addFile($file);

        self::assertSame('root', $root->getName());
        self::assertSame(['classes' => $child], $root->getDirectories());
        self::assertSame(['index.php' => $file], $root->getFiles());
    }

    public function testHasAndGetDirectory(): void
    {
        $root = new DirectoryNode('root');
        $child = new DirectoryNode('config');
        $root->addDirectory($child);

        self::assertTrue($root->hasDirectory('config'));
        self::assertSame($child, $root->getDirectory('config'));
        self::assertFalse($root->hasDirectory('missing'));
        self::assertNull($root->getDirectory('missing'));
    }

    public function testAddingSameNameOverwritesPreviousNode(): void
    {
        $root = new DirectoryNode('root');
        $first = new DirectoryNode('classes');
        $second = new DirectoryNode('classes');

        $root->addDirectory($first);
        $root->addDirectory($second);

        self::assertCount(1, $root->getDirectories());
        self::assertSame($second, $root->getDirectory('classes'));
    }
}
