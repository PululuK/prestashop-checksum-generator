<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Node\DirectoryNode;
use App\Service\DirectoryTreeBuilder;
use PHPUnit\Framework\TestCase;

final class DirectoryTreeBuilderTest extends TestCase
{
    private const string FIXTURE = __DIR__ . '/../fixtures/sample';

    public function testItBuildsTheTreeAndExcludesVendorByDefault(): void
    {
        $root = (new DirectoryTreeBuilder())->build(self::FIXTURE);

        self::assertSame(['index.php'], array_keys($root->getFiles()));
        self::assertSame(['classes', 'config'], $this->sortedKeys($root->getDirectories()));

        self::assertFalse($root->hasDirectory('vendor'), 'vendor must be excluded by default');

        $classes = $root->getDirectory('classes');
        self::assertInstanceOf(DirectoryNode::class, $classes);
        self::assertSame(['Product.php'], array_keys($classes->getFiles()));
    }

    public function testChecksumsMatchMd5OfTheFiles(): void
    {
        $root = (new DirectoryTreeBuilder())->build(self::FIXTURE);

        $expected = md5_file(self::FIXTURE . '/index.php');
        self::assertSame($expected, $root->getFiles()['index.php']->getChecksum());

        $classes = $root->getDirectory('classes');
        self::assertInstanceOf(DirectoryNode::class, $classes);

        $product = $classes->getFiles()['Product.php'];
        self::assertSame(md5_file(self::FIXTURE . '/classes/Product.php'), $product->getChecksum());
    }

    public function testCustomExcludeListReplacesTheDefaults(): void
    {
        // Excluding "classes" while NOT excluding "vendor" proves the custom
        // list fully replaces the defaults rather than extending them.
        $root = (new DirectoryTreeBuilder(['classes']))->build(self::FIXTURE);

        self::assertFalse($root->hasDirectory('classes'));
        self::assertTrue($root->hasDirectory('vendor'));
    }

    public function testEmptyExcludeListKeepsEverything(): void
    {
        $root = (new DirectoryTreeBuilder([]))->build(self::FIXTURE);

        self::assertSame(['classes', 'config', 'vendor'], $this->sortedKeys($root->getDirectories()));
    }

    public function testItThrowsOnMissingDirectory(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new DirectoryTreeBuilder())->build(self::FIXTURE . '/does-not-exist');
    }

    /**
     * @param array<string, mixed> $nodes
     *
     * @return list<string>
     */
    private function sortedKeys(array $nodes): array
    {
        $keys = array_keys($nodes);
        sort($keys, SORT_STRING);

        return $keys;
    }
}
