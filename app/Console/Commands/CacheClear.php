<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CacheClear extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fullstack:cache-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all cache-related data for the full stack application';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->info('Clearing all caches for the full stack application...');

        $cacheCommands = config('fullstack.cache_clear_commands', []);

        if (empty($cacheCommands)) {
            $this->info('No cache clearing commands found.');
            return;
        }

        $availableCommands = Artisan::all();

        foreach ($cacheCommands as $command) {
            if(array_key_exists($command,$availableCommands)) {
                $this->line("- $command");
            }
            
        }

        $clearCache = (new \Laravel\Prompts\ConfirmPrompt(
            'Do you wish to continue and clear the above caches?',
            true
        ))->prompt();

        if ($clearCache) {

            foreach ($cacheCommands as $command) {
                if(array_key_exists($command,$availableCommands)) {
                    $this->info("Running: $command...");
                    $this->call($command);
                    $this->info("$command has been executed.");
                }
            }

            $this->info('All predefined caches have been cleared for the full stack application!');
        } else {
            $this->info('Cache clearing was cancelled.');
        }
        

        
    }
}