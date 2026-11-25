<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Notifications\WorkspaceUpdatedNotification;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class SendProductReminders extends Command
{
    protected $signature = 'products:send-reminders';

    protected $description = 'Automatically sends idempotent follow-up reminders for products based on their cadence';

    public function handle(): int
    {
        $lock = Cache::lock('command:products:send-reminders', 300);
        if (! $lock->get()) {
            $this->warn('Another products:send-reminders process is already running.');
            return self::SUCCESS;
        }

        try {
            if (! Schema::hasTable('products')) {
                return self::SUCCESS;
            }

            $tz = config('app.timezone', 'Asia/Manila');
            $now = now($tz);
            $sent = 0;

            $products = Product::query()
                ->whereNotNull('client_followup_cadence')
                ->whereIn('notification_channel', ['app_only', 'email_and_app'])
                ->get();

            $recipients = User::query()
                ->where(function ($q) {
                    $q->where('is_active', true)->orWhereNull('is_active');
                })
                ->whereNull('disabled_at')
                ->whereNull('archived_at')
                ->get()
                ->filter(fn (User $u) => ! $u->isClient() && ($u->isAdmin() || $u->isSuperAdmin() || $u->hasPermission('access_product')))
                ->values();

            if ($recipients->isEmpty()) {
                $this->warn('No eligible product reminder recipients found.');
                return self::SUCCESS;
            }

            $url = Route::has('products.index') ? route('products.index') : '/products';

            foreach ($products as $product) {
                $cadence = strtolower(trim((string) $product->client_followup_cadence));
                $intervalDays = match ($cadence) {
                    'daily' => 1,
                    'every_3_days', 'every 3 days' => 3,
                    'weekly' => 7,
                    default => null,
                };

                if ($intervalDays === null) {
                    continue;
                }

                $lastUpdated = Carbon::parse($product->updated_at, $tz);
                if ($lastUpdated->diffInDays($now) < $intervalDays) {
                    continue;
                }

                $dedupKey = sprintf('product-reminder:%d:%s', $product->id, $now->toDateString());
                if (! Cache::add($dedupKey, true, now()->addDays($intervalDays))) {
                    continue;
                }

                foreach ($recipients as $recipient) {
                    $recipient->notify(
                        new WorkspaceUpdatedNotification(
                            'Product Reminder',
                            $product->id,
                            $product->name ?? $product->product_name ?? $product->short_name ?? ('Product #' . $product->id),
                            'System Automation',
                            $url
                        )
                    );
                }
                $sent++;
            }

            $this->info("Product reminders dispatch completed successfully. Sent: {$sent}");
            return self::SUCCESS;
        } finally {
            $lock->release();
        }
    }
}
