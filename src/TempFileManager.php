<?php

namespace Vldmir\TempFileManager;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TempFileManager
{
    private array $registeredFiles = [];
    private string $tempDirectory;
    private int $maxFileAgeHours;
    private string $disk;

    /**
     * TempFileManager constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->tempDirectory = $config['directory'] ?? 'temp';
        $this->maxFileAgeHours = $config['max_age_hours'] ?? 10;
        $this->disk = $config['disk'] ?? 'local';

        $this->ensureTempDirectoryExists();
    }

    /**
     * Ensure the temporary directory exists.
     */
    private function ensureTempDirectoryExists(): void
    {
        if (!Storage::disk($this->disk)->exists($this->tempDirectory)) {
            Storage::disk($this->disk)->makeDirectory($this->tempDirectory);
        }
    }

    /**
     * Sanitize filename to be safe for Unix systems.
     * Removes/replaces potentially dangerous characters.
     *
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        // Get the parts of the filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $basename = pathinfo($filename, PATHINFO_FILENAME);

        // Remove any character that isn't alphanumeric, dot, dash, or underscore
        $basename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $basename);

        // Replace multiple consecutive dots/underscores with a single one
        $basename = preg_replace('/[._-]{2,}/', '_', $basename);

        // Remove dots and dashes from the start and end
        $basename = trim($basename, '._-');

        // Ensure the filename isn't empty after sanitization
        if (empty($basename)) {
            $basename = 'file';
        }

        // Reconstruct filename with extension
        return empty($extension) ? $basename : "$basename.$extension";
    }

    /**
     * Get the temporary path for a filename.
     *
     * @param string $filename
     * @param string|null $extension
     * @return string
     */
    public function getTempPath(string $filename, ?string $extension = null): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $originalExtension = $extension ?? pathinfo($filename, PATHINFO_EXTENSION);

        $finalExtension = $originalExtension ? '.' . $originalExtension : '';
        $relativePath = $this->tempDirectory . '/' . $basename . $finalExtension;

        return Storage::disk($this->disk)->path($relativePath);
    }

    /**
     * Generate a unique filename if the desired one already exists.
     *
     * @param string $desiredFilename
     * @return string
     */
    private function ensureUniqueFilename(string $desiredFilename): string
    {
        $extension = pathinfo($desiredFilename, PATHINFO_EXTENSION);
        $basename = pathinfo($desiredFilename, PATHINFO_FILENAME);
        $filename = $desiredFilename;
        $counter = 1;

        while (Storage::disk($this->disk)->exists("{$this->tempDirectory}/{$filename}")) {
            $filename = empty($extension)
                ? "{$basename}_{$counter}"
                : "{$basename}_{$counter}.{$extension}";
            $counter++;
        }

        return $filename;
    }

    /**
     * Generate a safe filename.
     *
     * @param string|null $originalFilename
     * @return string
     */
    private function generateSafeFilename(?string $originalFilename = null): string
    {
        $extension = $originalFilename ? pathinfo($originalFilename, PATHINFO_EXTENSION) : '';
        $safeName = Str::random(32);

        return $extension ? "{$safeName}.{$extension}" : $safeName;
    }

    /**
     * Generate a unique filename in the temp directory.
     *
     * @param string $desiredFilename
     * @return string
     */
    private function generateUniqueFilename(string $desiredFilename): string
    {
        $extension = pathinfo($desiredFilename, PATHINFO_EXTENSION);
        $basename = pathinfo($desiredFilename, PATHINFO_FILENAME);

        $filename = $desiredFilename;
        $counter = 1;

        while (Storage::disk($this->disk)->exists("{$this->tempDirectory}/{$filename}")) {
            $filename = empty($extension)
                ? "{$basename}_{$counter}"
                : "{$basename}_{$counter}.{$extension}";
            $counter++;
        }

        return $filename;
    }

    /**
     * Save content to a temporary file.
     *
     * @param string|UploadedFile|resource $content
     * @param string|null $filename
     * @param string|null $extension
     * @return string
     * @throws Exception
     */
    public function save($content, ?string $filename = null, ?string $extension = null): string
    {
        try {
            // Generate random filename if none provided
            if (!$filename) {
                $filename = Str::random(32);
                if ($extension) {
                    $filename .= ".$extension";
                }
            }

            // Sanitize the filename
            $safeFilename = $this->sanitizeFilename($filename);

            // Ensure the filename is unique in the temp directory
            $uniqueFilename = $this->ensureUniqueFilename($safeFilename);

            $relativePath = "{$this->tempDirectory}/{$uniqueFilename}";

            if ($content instanceof UploadedFile) {
                Storage::disk($this->disk)->putFileAs($this->tempDirectory, $content, $uniqueFilename);
            } elseif (is_resource($content)) {
                Storage::disk($this->disk)->put($relativePath, stream_get_contents($content));
            } else {
                Storage::disk($this->disk)->put($relativePath, $content);
            }

            $fullPath = Storage::disk($this->disk)->path($relativePath);
            $this->register($fullPath);

            return $fullPath;
        } catch (Exception $e) {
            Log::error('Error saving temporary file', [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            throw $e;
        }
    }

    /**
     * Save an uploaded file to temporary storage.
     *
     * @param UploadedFile $file
     * @param string|null $filename
     * @return string
     * @throws Exception
     */
    public function saveUploadedFile(UploadedFile $file, ?string $filename = null): string
    {
        $originalName = $filename ?? $file->getClientOriginalName();
        return $this->save($file, $originalName);
    }

    /**
     * Save content from a URL to a temporary file.
     *
     * @param string $url
     * @param string|null $filename
     * @return string
     * @throws Exception
     */
    public function saveFromUrl(string $url, ?string $filename = null): string
    {
        try {
            $content = @file_get_contents($url);

            if ($content === false) {
                throw new Exception("Failed to download file from URL: $url");
            }

            $filename = $filename
                ?? basename(parse_url($url, PHP_URL_PATH))
                ?: $this->generateSafeFilename();

            return $this->save($content, $filename);
        } catch (Exception $e) {
            Log::error('Error downloading file from URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

    }

    /**
     * Register a file for cleanup.
     *
     * @param string $filePath
     */
    public function register(string $filePath): void
    {
        if ($filePath) {
            $this->registeredFiles[] = $filePath;
        }
    }

    /**
     * Cleanup temporary files.
     *
     * @param string|null $filePath
     */
    public function cleanup(?string $filePath = null): void
    {
        try {
            if ($filePath) {
                $this->removeFile($filePath);
                $this->registeredFiles = array_filter($this->registeredFiles, fn($f) => $f !== $filePath);
            } else {
                foreach ($this->registeredFiles as $file) {
                    $this->removeFile($file);
                }
                $this->registeredFiles = [];
            }
        } catch (Exception $e) {
            Log::error('Error during cleanup', [
                'error' => $e->getMessage(),
                'file' => $filePath ?? 'all files'
            ]);
        }
    }

    /**
     * Cleanup old temporary files.
     */
    public function cleanupOldFiles(): void
    {
        try {
            $files = Storage::disk($this->disk)->files($this->tempDirectory);
            $threshold = Carbon::now()->subHours($this->maxFileAgeHours)->timestamp;

            foreach ($files as $file) {
                $lastModified = Storage::disk($this->disk)->lastModified($file);
                if ($lastModified < $threshold) {
                    Storage::disk($this->disk)->delete($file);
                }
            }
        } catch (Exception $e) {
            Log::error('Error during old files cleanup', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove a file from storage.
     *
     * @param string $filePath
     */
    private function removeFile(string $filePath): void
    {
        try {
            $relativePath = $this->getRelativePath($filePath);
            if (Storage::disk($this->disk)->exists($relativePath)) {
                Storage::disk($this->disk)->delete($relativePath);
            }
        } catch (Exception $e) {
            Log::error('Failed to remove temporary file', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get the relative path of a file on the disk.
     *
     * @param string $fullPath
     * @return string
     */
    private function getRelativePath(string $fullPath): string
    {
        $diskPath = Storage::disk($this->disk)->path('');
        return ltrim(str_replace($diskPath, '', $fullPath), '/\\');
    }

    public function __destruct()
    {
        try {
            $this->cleanup();
        } catch (\Exception $e) {
            // Silently handle destructor errors
            // We can't reliably log here since the application might be shutting down
        }
    }
}
