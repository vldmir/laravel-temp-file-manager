<?php

namespace Vldmir\TempFileManager\Facades;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Facade;


/**
 * @method static string getTempPath(string $filename, ?string $extension = null) Get the full path for a temporary file
 * @method static string save(string|UploadedFile|resource $content, ?string $filename = null, ?string $extension = null) Save content to a temporary file
 * @method static string saveUploadedFile(UploadedFile $file, ?string $filename = null) Save an uploaded file to temporary storage
 * @method static string saveFromUrl(string $url, ?string $filename = null) Save content from a URL to temporary storage
 * @method static void register(string $filePath) Register a file for automatic cleanup
 * @method static void cleanup(?string $filePath = null) Clean up registered temporary files
 * @method static void cleanupOldFiles() Clean up old temporary files based on max age setting
 *
 * @see \Vldmir\TempFileManager\TempFileManager
 *
 * @description Laravel Temporary File Manager Facade
 *
 * This facade provides a static interface to the TempFileManager functionality.
 * It allows for easy management of temporary files with features like automatic cleanup,
 * file registration, and scheduled deletion of old files.
 *
 * Example usage:
 *
 * ```php
 * // Save a temporary file
 * $path = TempManager::save('content', 'filename.txt');
 *
 * // Save an uploaded file
 * $path = TempManager::saveUploadedFile($request->file('document'));
 *
 * // Save from URL
 * $path = TempManager::saveFromUrl('https://example.com/file.pdf');
 *
 * // Register for auto-cleanup
 * TempManager::register($path);
 *
 * // Manual cleanup
 * TempManager::cleanup($path);
 * ```
 */
class TempManager extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'temp-manager';
    }
}
