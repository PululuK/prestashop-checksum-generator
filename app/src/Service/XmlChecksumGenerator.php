<?php

declare(strict_types=1);

namespace App\Service;

use App\Node\DirectoryNode;
use XMLWriter;

/**
 * Produces a PrestaShop-compatible checksum XML document for a directory.
 *
 * The output is deterministic: directories and files are emitted in a stable,
 * alphabetical order regardless of the underlying filesystem traversal order,
 * so the same source tree always yields a byte-for-byte identical document —
 * a requirement when the checksum is compared in a CI/CD pipeline.
 */
class XmlChecksumGenerator
{
    public function __construct(
        private readonly DirectoryTreeBuilder $treeBuilder = new DirectoryTreeBuilder(),
    ) {
    }

    public function generate(string $rootPath, string $version): string
    {
        $tree = $this->treeBuilder->build($rootPath);

        $xml = new XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('    ');
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('checksum_list');
        $xml->startElement('ps_root_dir');
        $xml->writeAttribute('version', $version);

        $this->writeDirectory($xml, $tree);

        $xml->endElement();
        $xml->endElement();
        $xml->endDocument();

        return $xml->outputMemory();
    }

    private function writeDirectory(XMLWriter $xml, DirectoryNode $directory): void
    {
        $files = $directory->getFiles();
        ksort($files, SORT_STRING);

        foreach ($files as $file) {
            $xml->startElement('md5file');
            $xml->writeAttribute('name', $file->getName());
            $xml->text($file->getChecksum());
            $xml->endElement();
        }

        $directories = $directory->getDirectories();
        ksort($directories, SORT_STRING);

        foreach ($directories as $subDirectory) {
            $xml->startElement('dir');
            $xml->writeAttribute('name', $subDirectory->getName());
            $this->writeDirectory($xml, $subDirectory);
            $xml->endElement();
        }
    }
}
