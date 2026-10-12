<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Service;
use App\Models\ServiceVersion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class SendServiceReminders extends Command
{
    protected $signature = 'services:send-reminders';
    protected $description = 'Automatically sends client follow-up reminders based on the configured cadence';

    public function handle()
    {
        // Get all active service versions with app_only notification channel
        $versions = ServiceVersion::with('service')
            ->where('notification_channel', 'app_only')
            ->whereNotNull('client_reminder_cadence')
            ->get();

        foreach ($versions as $version) {
            $cadence = strtolower($version->client_reminder_cadence);
            $service = $version->service;

            if (!$service) {
                continue;
            }

            $shouldSend = false;
            $lastNotified = $version->updated_at;

            if ($cadence === 'daily') {
                $shouldSend = $lastNotified->diffInDays(now()) >= 1;
            } elseif ($cadence === 'every_3_days' || $cadence === 'every 3 days') {
                $shouldSend = $lastNotified->diffInDays(now()) >= 3;
            } elseif ($cadence === 'weekly') {
                $shouldSend = $lastNotified->diffInDays(now()) >= 7;
            }

            if ($shouldSend) {
                $cadenceLabel = ucwords(str_replace(['_', '-'], ' ', $cadence));
                
                if (Schema::hasTable('notifications')) {
                    DB::table('notifications')->insert([
                        'id'              => (string) Str::uuid(),
                        'type'            => 'App\Notifications\SystemNotification',
                        'notifiable_type' => 'App\Models\User',
                        'notifiable_id'   => 1, 
                        'data'            => json_encode([
                            'title'   => 'Task Reminder (' . $cadenceLabel . ')',
                            'message' => 'Automatic reminder for service "' . ($version->short_name ?? $service->name) . '". Cadence: ' . $cadenceLabel . '.',
                            'url'     => route('dashboard'),
                        ]),
                        'read_at'         => null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }
        }

        $this->info('Service reminders dispatch completed successfully.');
    }
}