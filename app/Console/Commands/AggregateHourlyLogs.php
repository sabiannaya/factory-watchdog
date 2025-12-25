<?php

namespace App\Console\Commands;

use App\Models\DailyTarget;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateHourlyLogs extends Command
{
    protected $signature = 'logs:aggregate-daily';

    protected $description = 'Aggregate hourly logs into daily targets';

    public function handle(): int
    {
        // Aggregate for last 7 days in Asia/Jakarta timezone
        $dates = collect(range(-7, 0))->map(fn ($d) => Carbon::now('Asia/Jakarta')->addDays($d)->toDateString());

        foreach ($dates as $date) {
            // Get or create daily target for this date
            $daily = DailyTarget::firstOrCreate(
                ['date' => $date],
                ['target_value' => 0, 'actual_value' => 0]
            );

            // Aggregate from hourly logs for this date (stored in UTC)
            $startUtc = Carbon::parse($date, 'Asia/Jakarta')->setTimezone('UTC')->startOfDay();
            $endUtc = Carbon::parse($date, 'Asia/Jakarta')->setTimezone('UTC')->endOfDay();

            // Sum target and actual from hourly logs for this date
            $agg = DB::table('hourly_logs')
                ->whereBetween('recorded_at', [$startUtc, $endUtc])
                ->selectRaw('COALESCE(SUM(target_value), 0) as total_target, COALESCE(SUM(output_value), 0) as total_actual')
                ->first();

            $daily->update([
                'target_value' => (int) $agg->total_target,
                'actual_value' => (int) $agg->total_actual,
            ]);

            $this->info("Aggregated {$date}: target={$agg->total_target}, actual={$agg->total_actual}");
        }

        return 0;
    }
}
