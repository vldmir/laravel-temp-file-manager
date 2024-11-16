// CHANGELOG.md
# Changelog

All notable changes to `laravel-temp-file-manager` will be documented in this file.

## 1.1.0 - 2024-03-18

### Changed
- Simplified filename handling logic
- Improved Unix filesystem compatibility with better filename sanitization
- Removed forced unique filename generation in favor of simple counter suffix for duplicates
- Added proper sanitization for filenames with unsafe characters

## 1.0.0 - 2024-03-11

- Initial release
- Basic temporary file management functionality
- Auto-cleanup feature
- File registration system
- Scheduled cleanup command
- Multiple file saving methods (string, upload, URL, stream)
