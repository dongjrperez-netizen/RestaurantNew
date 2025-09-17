<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckUserData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:check-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check user and restaurant data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = \App\Models\User::first();
        
        if (!$user) {
            $this->error('No users found');
            return 1;
        }
        
        $this->info("User: {$user->email} (ID: {$user->id})");
        
        if ($user->restaurantData) {
            $this->info("Restaurant Data exists: ID {$user->restaurantData->id}");
            $this->info("Restaurant Name: {$user->restaurantData->restaurant_name}");
        } else {
            $this->error("No restaurant data found for this user");
            
            // Create restaurant data if missing
            $restaurantData = \App\Models\Restaurant_Data::create([
                'user_id' => $user->id,
                'restaurant_name' => 'Test Restaurant',
                'restaurant_address' => '123 Test Street',
                'restaurant_phone' => '123-456-7890',
                'restaurant_email' => $user->email,
                'restaurant_status' => 'active',
            ]);
            
            $this->info("Created restaurant data with ID: {$restaurantData->id}");
        }
        
        return 0;
    }
}
