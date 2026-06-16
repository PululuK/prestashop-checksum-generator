<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\DirectoryTreeBuilder;
use App\Service\XmlChecksumGenerator;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;

final class XmlChecksumGeneratorTest extends TestCase
{
    private const string FIXTURE = __DIR__ . '/../fixtures/sample';

    public function testItGeneratesValidPrestaShopXml(): void
    {
        $xml = (new XmlChecksumGenerator())->generate(self::FIXTURE, '8.1.0');

        $document = $this->load($xml);
        self::assertSame('checksum_list', $document->getName());
        self::assertSame('8.1.0', (string) $document->ps_root_dir['version']);
    }

    public function testItEmitsFilesBeforeDirectoriesInAlphabeticalOrder(): void
    {
        $xml = (new XmlChecksumGenerator())->generate(self::FIXTURE, '8.1.0');
        $root = $this->load($xml)->ps_root_dir;

        $order = [];
        foreach ($root->children() as $child) {
            $order[] = $child->getName() . ':' . (string) $child['name'];
        }

        // Files first (index.php), then directories alphabetically (classes, config).
        self::assertSame(
            ['md5file:index.php', 'dir:classes', 'dir:config'],
            $order,
        );
    }

    public function testItExcludesVendorAndWritesCorrectChecksums(): void
    {
        $xml = (new XmlChecksumGenerator())->generate(self::FIXTURE, '8.1.0');

        self::assertStringNotContainsString('vendor', $xml);

        $root = $this->load($xml)->ps_root_dir;
        self::assertSame(md5_file(self::FIXTURE . '/index.php'), (string) $root->md5file);
    }

    public function testOutputIsDeterministic(): void
    {
        $generator = new XmlChecksumGenerator();

        self::assertSame(
            $generator->generate(self::FIXTURE, '8.1.0'),
            $generator->generate(self::FIXTURE, '8.1.0'),
        );
    }

    public function testGeneratorUsesInjectedBuilder(): void
    {
        $generator = new XmlChecksumGenerator(new DirectoryTreeBuilder([]));
        $xml = $generator->generate(self::FIXTURE, '8.1.0');

        // With an empty exclude list the vendor directory must appear.
        self::assertStringContainsString('<dir name="vendor">', $xml);
    }

    private function load(string $xml): SimpleXMLElement
    {
        $document = simplexml_load_string($xml);
        self::assertInstanceOf(SimpleXMLElement::class, $document, 'output must be well-formed XML');

        return $document;
    }
}
