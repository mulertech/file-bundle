# Release notes for file-bundle

## v1.0.0 - 2026-04-04

Symfony bundle for file upload management with metadata tracking, configurable validation, and an extensible entity.

Features

- AbstractFile MappedSuperclass — extensible base entity with filename, stored filename, filepath, MIME type, size, extension, and public flag
- FileManagerInterface — upload, delete, path resolution, disk existence check, file size formatting
- Configurable validation — MIME type whitelist (15 types by default) and max file size (50 MB by default)
- DirectoryStrategyInterface — pluggable directory naming strategy (ships with DefaultDirectoryStrategy: uploader_{id}/)
- Twig functions — file_exists_on_disk() and format_file_size() (auto-registered when Twig is available)
- Full configuration tree via mulertech_file extension alias
