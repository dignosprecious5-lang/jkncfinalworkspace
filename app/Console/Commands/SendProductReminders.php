<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use App\Models\User;
use App\Notifications\WorkspaceUpdatedNotification;

class SendProductReminders extends Command
{
    protected $signature = 'products:send-reminders';
    protected $description = 'Automatically sends follow-up reminders for products based on their cadence';

    public function handle()
    {
        $products = Product::whereNotNull('client_followup_cadence')
            ->where('notification_channel', 'app_only')
            ->get();

        foreach ($products as $product) {
            $cadence = strtolower($product->client_followup_cadence);
            $lastUpdated = $product->updated_at;

            $shouldSend = false;

            if ($cadence === 'daily') {
                $shouldSend = $lastUpdated->diffInDays(now()) >= 1;
            } elseif ($cadence === 'every_3_days' || $cadence === 'every 3 days') {
                $shouldSend = $lastUpdated->diffInDays(now()) >= 3;
            } elseif ($cadence === 'weekly') {
                $shouldSend = $lastUpdated->diffInDays(now()) >= 7;
            }

            if ($shouldSend) {
                // Kunin ang mga admin at manager nang walang hardcoded na email
                $admins = User::whereIn('role', ['admin', 'manager'])->get();
                
                foreach ($admins as $admin) {
                    $admin->notify(
                        new WorkspaceUpdatedNotification(
                            'Product Reminder',
                            $product->id,
                            $product->name ?? $product->short_name ?? 'Product #' . $product->id,
                            'System Automation',
                            route('dashboard')
                        )
                    );
                }
            }
        }

        $this->info('Product reminders dispatch completed successfully.');
    }
}