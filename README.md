# Laravel Temporary File Manager

A Laravel package for managing temporary files with automatic cleanup functionality. This package helps you manage temporary files in your Laravel application with features like automatic cleanup, file registration, and scheduled deletion of old files.

[![Latest Version on Packagist](https://img.shields.io/packagist/v/vldmir/laravel-temp-file-manager.svg?style=flat-square)](https://packagist.org/packages/vldmir/laravel-temp-file-manager)
[![Total Downloads](https://img.shields.io/packagist/dt/vldmir/laravel-temp-file-manager.svg?style=flat-square)](https://packagist.org/packages/vldmir/laravel-temp-file-manager)

## Features

- 🚀 Easy temporary file management
- 💾 Multiple methods for saving temporary files
- 🧹 Automatic cleanup of old files
- 🗑️ Auto-deletion of registered files after process completion
- ⚙️ Configurable storage location and retention period
- 📦 Laravel integration with Facade support
- 🔄 Scheduled cleanup command
- 💪 Strong typing and modern PHP 8.0+ features

## Requirements

- PHP 8.0 or higher
- Laravel 8.0 or higher

## Installation

You can install the package via composer:

```bash
composer require vldmir/laravel-temp-file-manager
```

## Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Vldmir\TempFileManager\TempFileManagerServiceProvider" --tag="config"
```

This will create a `temp-file-manager.php` config file in your config directory. You can modify these settings:

```php
return [
    /*
    |--------------------------------------------------------------------------
    | Temporary Files Directory
    |--------------------------------------------------------------------------
    |
    | This value determines the directory where temporary files will be stored.
    | The path is relative to the storage disk specified below.
    |
    */
    'directory' => 'temp',

    /*
    |--------------------------------------------------------------------------
    | Maximum File Age (Hours)
    |--------------------------------------------------------------------------
    |
    | Files older than this value (in hours) will be automatically cleaned up
    | when running the cleanup command.
    |
    */
    'max_age_hours' => 10,

    /*
    |--------------------------------------------------------------------------
    | Storage Disk
    |--------------------------------------------------------------------------
    |
    | The storage disk where temporary files will be stored. This should be
    | configured in your filesystem.php config file.
    |
    */
    'disk' => 'local',
];
```

## Usage

### Basic Usage with Facade

```php
use Vldmir\TempFileManager\Facades\TempManager;

// Get a temporary file path
$tempPath = TempManager::getTempPath('myfile.txt');

// Register a file for auto-cleanup
TempManager::register($tempPath);
// After registering, the file will be automatically deleted when the PHP process ends

// Clean up specific file manually if needed
TempManager::cleanup($tempPath);
```

### Saving Files

The package provides several methods to save files to temporary storage:

#### 1. Save String Content

```php
// Save string content
$content = "Hello, World!";
$tempPath = TempManager::save($content, 'hello.txt');

// Save with auto-generated filename
$tempPath = TempManager::save($content);
```

#### 2. Save Uploaded Files

```php
// In your controller
public function upload(Request $request)
{
    // Save uploaded file with original name
    $tempPath = TempManager::saveUploadedFile($request->file('document'));
    
    // Save with custom filename
    $tempPath = TempManager::saveUploadedFile(
        $request->file('document'), 
        'custom-name.pdf'
    );
}
```

#### 3. Save From URL

```php
// Download and save file from URL
try {
    $tempPath = TempManager::saveFromUrl('https://example.com/file.pdf');
    
    // With custom filename
    $tempPath = TempManager::saveFromUrl(
        'https://example.com/file.pdf', 
        'local-copy.pdf'
    );
} catch (\Exception $e) {
    // Handle download error
}
```

#### 4. Save Stream/Resource

```php
// Save from resource
$handle = fopen('path/to/file', 'r');
$tempPath = TempManager::save($handle, 'output.txt');
fclose($handle);
```

### File Cleanup Behavior

There are three ways files can be cleaned up:

1. **Automatic Cleanup After Process** (Using register)
```php
// The file will be automatically deleted when the PHP process ends
$tempPath = TempManager::getTempPath('upload.txt');
TempManager::register($tempPath);

// Do your work with the file
// ...
// File will be deleted automatically after process completion
```

2. **Manual Cleanup** (Using cleanup method)
```php
// Manually delete when you're done
TempManager::cleanup($tempPath);
```

3. **Scheduled Cleanup** (For old files)
```php
// All files older than max_age_hours will be removed
TempManager::cleanupOldFiles();
```

### Scheduled Cleanup Command

To automatically clean up old temporary files, register the cleanup command in your `App\Console\Kernel`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('temp-files:cleanup')->hourly();
}
```

### Dependency Injection Usage

You can also use dependency injection to access the TempFileManager:

```php
use Vldmir\TempFileManager\TempFileManager;

class MyController extends Controller
{
    public function __construct(private TempFileManager $tempManager)
    {
        $this->tempManager = $tempManager;
    }

    public function store(Request $request)
    {
        $tempPath = $this->tempManager->getTempPath('uploaded-file.txt');
        $this->tempManager->register($tempPath);
        // File will be auto-cleaned after process ends
    }
}
```

### Complete Example in Controller

```php
class DocumentController extends Controller
{
    public function store(Request $request)
    {
        try {
            // Save uploaded file
            $tempPath = TempManager::saveUploadedFile($request->file('document'));
            
            // Process the file
            // ...
            
            // Optionally clean up early if you're done
            TempManager::cleanup($tempPath);
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            // File will be auto-cleaned up when process ends
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    
    public function download()
    {
        try {
            // Save remote file temporarily
            $tempPath = TempManager::saveFromUrl('https://example.com/document.pdf');
            
            // Process or serve the file
            return response()->download($tempPath);
            // File will be cleaned up after response is sent
            
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

## Testing

```bash
composer test
```

Tests are written using PHPUnit and Orchestra Testbench. They cover all major functionality including:
- File operations
- Auto-cleanup
- URL downloads
- Uploaded file handling
- Command execution

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security-related issues, please email your.email@example.com instead of using the issue tracker.

## Credits

- [Vladimir](https://github.com/vldmir)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
