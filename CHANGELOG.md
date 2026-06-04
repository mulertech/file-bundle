# Release notes for file-bundle

## v1.1.0 - 2026-06-04

### v1.1.0

Adds Symfony 8 support and hardens file operations.

#### Added

- Symfony 8.0 / 8.1 compatibility across all required components (config, dependency-injection, http-foundation, http-kernel, mime, string).
  The bundle still supports Symfony 6.4 and 7.0.

#### Changed

- Improved error handling for file operations: failed `unlink` and write
  operations are now logged with clearer checks.
- CI: upgraded GitHub Actions (`actions/checkout` v6, `ramsey/composer-install` v4,
  `nick-fields/retry` v4) and PHP test matrix on 8.4 / 8.5.

#### Security

- Updated `twig/twig` to 3.27.1 (dev dependency) to address the May 2026
  Twig sandbox advisories (CVE-2026-46639 and related).

**Compatibility:** PHP 8.4+ · Symfony 6.4 / 7.0 / 8.0 · Doctrine ORM 2.19+ / 3.0+

## v1.0.0 - 2026-04-04

Symfony bundle for file upload management with metadata tracking, configurable validation, and an extensible entity.

Features

- AbstractFile MappedSuperclass — extensible base entity with filename, stored filename, filepath, MIME type, size, extension, and public flag
- FileManagerInterface — upload, delete, path resolution, disk existence check, file size formatting
- Configurable validation — MIME type whitelist (15 types by default) and max file size (50 MB by default)
- DirectoryStrategyInterface — pluggable directory naming strategy (ships with DefaultDirectoryStrategy: uploader_{id}/)
- Twig functions — file_exists_on_disk() and format_file_size() (auto-registered when Twig is available)
- Full configuration tree via mulertech_file extension alias
