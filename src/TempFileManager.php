<?php

namespace Vldmir\TempFileManager;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TempFileManager
{
    private array $registeredFiles = [];
    private string $tempDirectory;
    private int $maxFileAgeHours;
    private string $disk;

    public function __construct(array $config)
    {
        $this->tempDirectory = $config['directory'] ?? 'temp';
        $this->maxFileAgeHours = $config['max_age_hours'] ?? 10;
        $this->disk = $config['disk'] ?? 'local';

        $this->ensureTempDirectoryExists();
    }

    private function ensureTempDirectoryExists(): void
    {
        if (!Storage::disk($this->disk)->exists($this->tempDirectory)) {
            Storage::disk($this->disk)->makeDirectory($this->tempDirectory);
        }
    }

    public function getTempPath(string $filename, ?string $extension = null): string
    {
        $basename = pathinfo($filename, PATHINFO_FILENAME);
        $originalExtension = $extension ?? pathinfo($filename, PATHINFO_EXTENSION);

        $finalExtension = $originalExtension ? '.' . $originalExtension : '';
        $path = $this->tempDirectory . '/' . $basename . $finalExtension;

        return Storage::disk($this->disk)->path($path);
    }
    public function save($content, ?string $filename = null, ?string $extension = null): string
    {
        try {
        // Generate filename if not provided
        if (!$filename) {
            $filename = Str::random(40);
        }

        // Get temp path with proper extension handling
        $tempPath = $this->getTempPath($filename, $extension);
            $relativePath = $this->getRelativePath($tempPath);

        // Save content based on its type
        if ($content instanceof UploadedFile) {
            Storage::disk($this->disk)->putFileAs(
                $this->tempDirectory,
                $content,
                basename($tempPath)
            );
        } elseif (is_resource($content)) {
                Storage::disk($this->disk)->put($relativePath, stream_get_contents($content));
                if (is_resource($content)) {
                    fclose($content);
                }
        } else {
                Storage::disk($this->disk)->put($relativePath, $content);
        }

        // Register file for auto-cleanup
        $this->register($tempPath);

        return $tempPath;
        } catch (\Exception $e) {
            Log::error('Error saving temporary file', [
                'error' => $e->getMessage(),
                'filename' => $filename
            ]);
            throw $e;
        }
    }

    public function saveUploadedFile(UploadedFile $file, ?string $filename = null): string
    {
        $filename = $filename ?? $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();

        return $this->save($file, $filename, $extension);
    }

    public function saveFromUrl(string $url, ?string $filename = null): string
    {
        try {
        $content = @file_get_contents($url);

        if ($content === false) {
            throw new \Exception("Failed to download file from URL: $url");
        }

        // Extract filename from URL if not provided
        if (!$filename) {
            $filename = basename(parse_url($url, PHP_URL_PATH));
            if (empty($filename)) {
                $filename = Str::random(40);
            }
        }

        return $this->save($content, $filename);
        } catch (\Exception $e) {
            Log::error('Error downloading file from URL', [
                'url' => $url,
                'error' => $e->getMessage()
            ]);
            throw $e;
    }

    }

    public function register(string $filePath): void
    {
        if ($filePath) {
            $this->registeredFiles[] = $filePath;
        }
    }

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
        } catch (\Exception $e) {
            Log::error('Error during cleanup', [
                'error' => $e->getMessage(),
                'file' => $filePath ?? 'all files'
            ]);
        }
    }

    public function cleanupOldFiles(): void
    {
        try {
        $files = Storage::disk($this->disk)->files($this->tempDirectory);
        $threshold = now()->subHours($this->maxFileAgeHours)->timestamp;

        foreach ($files as $file) {
            $fullPath = Storage::disk($this->disk)->path($file);
            if (is_file($fullPath) && filemtime($fullPath) < $threshold) {
                $this->removeFile($fullPath);
            }
        }
        } catch (\Exception $e) {
            Log::error('Error during old files cleanup', [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function removeFile(string $filePath): void
    {
        try {
            $relativePath = $this->getRelativePath($filePath);
            if (Storage::disk($this->disk)->exists($relativePath)) {
                Storage::disk($this->disk)->delete($relativePath);
            }
        } catch (\Exception $e) {
            Log::error('Failed to remove temporary file', [
                'path' => $filePath,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getRelativePath(string $fullPath): string
    {
        $diskPath = Storage::disk($this->disk)->path('');
        return str_replace($diskPath, '', $fullPath);
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
