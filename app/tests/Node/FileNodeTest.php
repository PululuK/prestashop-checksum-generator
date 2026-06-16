<?php

declare(strict_types=1);

namespace App\Tests\Node;

use App\Node\FileNode;
use PHPUnit\Framework\TestCase;

final class FileNodeTest extends TestCase
{
    public function testItExposesNameAndChecksum(): void
    {
        $node = new FileNode('index.php', 'd41d8cd98f00b204e9800998ecf8427e');

        self::assertSame('index.php', $node->getName());
        self::assertSame('d41d8cd98f00b204e9800998ecf8427e', $node->getChecksum());
    }
}
