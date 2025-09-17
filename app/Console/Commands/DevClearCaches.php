<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class DevClearCaches extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all caches and refresh application for development';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Refreshing Laravel application for development...');
        
        // Clear all caches
        $this->call('optimize:clear');
        
        // Clear specific caches
        $this->call('route:clear');
        $this->call('config:clear');
        $this->call('view:clear');
        $this->call('cache:clear');
        
        // Refresh config
        $this->call('config:cache');
        
        $this->info('✅ Application refreshed successfully!');
        $this->info('💡 Your routes and functions should work without server reload now.');
        
        return 0;
    }
}
