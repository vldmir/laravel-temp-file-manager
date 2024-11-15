<?php

namespace Vldmir\TempFileManager\Console\Commands;

use Illuminate\Console\Command;
use Vldmir\TempFileManager\TempFileManager;

class CleanupTempFilesCommand extends Command
{
    protected $signature = 'temp-files:cleanup';
    protected $description = 'Clean up old temporary files';

    public function handle(TempFileManager $manager)
    {
        $this->info('Cleaning up old temporary files...');
        $manager->cleanupOldFiles();
        $this->info('Cleanup completed successfully!');
    }
}
