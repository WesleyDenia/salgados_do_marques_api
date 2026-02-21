<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameKeys([
            'welcome_bonus_points' => 'WELCOME_BONUS_POINTS',
            'order_start_time' => 'ORDER_START_TIME',
            'order_end_time' => 'ORDER_END_TIME',
            'order_minimum_minutes' => 'ORDER_MINIMUM_MINUTES',
            'order_cancel_minutes' => 'ORDER_CANCEL_MINUTES',
            'order_timezone' => 'ORDER_TIMEZONE',
            'LOYLATY_EXPIRATION' => 'LOYALTY_EXPIRATION',
        ]);
    }

    public function down(): void
    {
        $this->renameKeys([
            'WELCOME_BONUS_POINTS' => 'welcome_bonus_points',
            'ORDER_START_TIME' => 'order_start_time',
            'ORDER_END_TIME' => 'order_end_time',
            'ORDER_MINIMUM_MINUTES' => 'order_minimum_minutes',
            'ORDER_CANCEL_MINUTES' => 'order_cancel_minutes',
            'ORDER_TIMEZONE' => 'order_timezone',
            'LOYALTY_EXPIRATION' => 'LOYLATY_EXPIRATION',
        ]);
    }

    private function renameKeys(array $map): void
    {
        foreach ($map as $from => $to) {
            $source = DB::table('settings')->where('key', $from)->first();

            if (!$source) {
                continue;
            }

            $targetExists = DB::table('settings')->where('key', $to)->exists();

            if ($targetExists) {
                DB::table('settings')->where('key', $from)->delete();
                continue;
            }

            DB::table('settings')
                ->where('key', $from)
                ->update([
                    'key' => $to,
                    'updated_at' => now(),
                ]);
        }
    }
};
