<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateTestSubscription extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:create-test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test subscription for development purposes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $user = \App\Models\User::first();
        
        if (!$user) {
            $this->error('No users found in the database');
            return 1;
        }

        // Create a subscription plan if it doesn't exist
        $plan = \App\Models\Subscriptionpackage::firstOrCreate([
            'plan_name' => 'Development Plan'
        ], [
            'plan_price' => 0.00,
            'plan_duration' => 365,
            'plan_duration_type' => 'days',
        ]);

        // Create or update user subscription
        \App\Models\Usersubscription::updateOrCreate([
            'user_id' => $user->id
        ], [
            'subscription_startDate' => now(),
            'subscription_endDate' => now()->addYear(),
            'subscription_status' => 'active',
            'remaining_days' => 365,
            'subscriptionpackage_id' => $plan->plan_id
        ]);

        $this->info("Test subscription created for user: {$user->email}");
        $this->info("Subscription valid until: " . now()->addYear()->format('Y-m-d'));
        
        return 0;
    }
}
