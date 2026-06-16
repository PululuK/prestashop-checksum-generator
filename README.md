# prestashop-checksum-generator

Generate a **PrestaShop-compatible checksum file** (`checksum_list` XML) for a
directory tree. It walks a PrestaShop installation, computes the MD5 of every
file and emits a deterministic XML document — the same format PrestaShop uses to
verify the integrity of a release.

It ships as a small, dependency-free Docker image meant to be dropped into a
CI/CD pipeline (GitLab CI, GitHub Actions, …).

## Output format

```xml
<?xml version="1.0" encoding="UTF-8"?>
<checksum_list>
    <ps_root_dir version="8.1.0">
        <md5file name="index.php">5351d6e675c082df2736d4472b72c2d1</md5file>
        <dir name="classes">
            <md5file name="Product.php">a78abe30aa96d641bcd91855fd3ae98a</md5file>
        </dir>
    </ps_root_dir>
</checksum_list>
```

Within each directory, files are listed first, then sub-directories, both in
alphabetical order. The traversal order of the filesystem does **not** affect
the result, so the same source tree always produces a byte-for-byte identical
document — which is what makes it safe to compare in a pipeline.

## Usage with Docker

Mount the directory to scan and pass `--path` / `--version`:

```bash
docker run --rm -v "$PWD:/data:ro" \
    pululuk/prestashop-checksum-generator \
    --path=/data --version=8.1.0 > checksum.xml
```

Or write the file from inside the container:

```bash
docker run --rm -v "$PWD:/data" \
    pululuk/prestashop-checksum-generator \
    --path=/data --version=8.1.0 --output=/data/checksum.xml
```

### Options

| Option              | Description                                                                                   |
|---------------------|-----------------------------------------------------------------------------------------------|
| `--path=<dir>`      | **Required.** Root directory to scan.                                                         |
| `--version=<ver>`   | **Required.** Version written in the `<ps_root_dir>` tag.                                      |
| `--output=<file>`   | Write the XML to a file instead of standard output.                                           |
| `--exclude=<name>`  | Directory name to skip. Repeatable. When given, it **replaces** the default exclude list.     |
| `-h`, `--help`      | Show help.                                                                                     |

Default excluded directories: `.git`, `.idea`, `var`, `vendor`.
To keep them (e.g. to checksum a full PrestaShop release that ships `vendor/`),
pass an explicit list, for example `--exclude=.git`.

Exit codes: `0` success, `1` runtime error (unreadable path/file, write
failure), `2` invalid arguments.

## Usage in CI/CD

### GitLab CI

```yaml
generate-checksum:
  image: pululuk/prestashop-checksum-generator
  script:
    - generate-checksum --path=. --version=$CI_COMMIT_TAG --output=checksum.xml
  artifacts:
    paths:
      - checksum.xml
```

> The image `ENTRYPOINT` is the tool itself; GitLab overrides it, so call
> `generate-checksum` explicitly in `script`.

### GitHub Actions

```yaml
- name: Generate checksum
  run: |
    docker run --rm -v "$PWD:/data:ro" \
      pululuk/prestashop-checksum-generator \
      --path=/data --version=${{ github.ref_name }} > checksum.xml
```

## Local development

Requires PHP ≥ 8.3 and Composer.

```bash
cd app
composer install
composer test            # run the PHPUnit suite
composer phpstan         # static analysis (PHPStan, level 9)
composer cs:check        # coding standards, report only (PHP CS Fixer)
composer cs:fix          # coding standards, apply fixes
composer lint            # cs:check + phpstan + test

# Run the CLI directly:
php bin/generate-checksum --path=tests/fixtures/sample --version=8.1.0
```

All three checks (PHP CS Fixer, PHPStan, PHPUnit) run in CI on every push and
merge/pull request.

### Building the image locally

```bash
docker build -t prestashop-checksum-generator .
docker run --rm prestashop-checksum-generator --help
```

## How it works

- `App\Service\DirectoryTreeBuilder` walks the tree with native SPL recursive
  iterators (no third-party crawler), pruning excluded directories before
  descending and hashing each file with `hash_file('md5', …)`.
- `App\Service\XmlChecksumGenerator` renders the tree to XML with `XMLWriter`,
  sorting entries for deterministic output.

The runtime has **no Composer dependencies**, so the published image carries
only PHP and the application code.

## License

MIT — see [LICENSE](LICENSE).
