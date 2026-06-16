# PrestaShop Checksum Generator

Generate a **PrestaShop-compatible checksum file** (`checksum_list` XML) for any
directory tree. The image walks a PrestaShop installation, computes the MD5 of
every file and emits a **deterministic** XML document — the same format
PrestaShop uses to verify the integrity of a release.

Small, **non-root**, **dependency-free** PHP CLI image, built to drop into a
CI/CD pipeline (GitLab CI, GitHub Actions, …).

## Quick start

Mount the directory to scan and pass `--path` / `--version`:

```bash
docker run --rm -v "$PWD:/data:ro" \
    pululuk/prestashop-checksum-generator \
    --path=/data --version=8.1.0 > checksum.xml
```

Write the file from inside the container instead of stdout:

```bash
docker run --rm -v "$PWD:/data" \
    pululuk/prestashop-checksum-generator \
    --path=/data --version=8.1.0 --output=/data/checksum.xml
```

## Output

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

Within each directory, files are listed first, then sub-directories, both
alphabetically. Filesystem traversal order does **not** affect the result, so
the same source tree always produces a byte-for-byte identical document — safe
to diff in a pipeline.

## Options

| Option              | Description                                                                                |
|---------------------|--------------------------------------------------------------------------------------------|
| `--path=<dir>`      | **Required.** Root directory to scan.                                                      |
| `--version=<ver>`   | **Required.** Version written in the `<ps_root_dir>` tag.                                   |
| `--output=<file>`   | Write the XML to a file instead of standard output.                                        |
| `--exclude=<name>`  | Directory name to skip. Repeatable. When given, it **replaces** the default exclude list.  |
| `-h`, `--help`      | Show help.                                                                                  |

Default excluded directories: `.git`, `.idea`, `var`, `vendor`. To checksum a
full PrestaShop release that ships `vendor/`, pass an explicit list such as
`--exclude=.git`.

Exit codes: `0` success, `1` runtime error (unreadable path/file, write
failure), `2` invalid arguments.

## Use in CI/CD

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

## Tags

- `latest` — latest build from the default branch.
- `1.0.0`, `X.Y.Z` — immutable, pin this in CI for reproducible builds.

## Source & license

Source code, issues and full docs:
<https://github.com/PululuK/prestashop-checksum-generator> · MIT License.
