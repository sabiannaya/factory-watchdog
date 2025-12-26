<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardDebug extends Command
{
    protected $signature = 'dashboard:debug';

    protected $description = 'Print dashboard debug info: counts, min/max recorded_at, last logs, and group aggregation';

    public function handle(): int
    {
        $this->info('Hourly logs count: '.DB::table('hourly_logs')->count());

        $minmax = DB::table('hourly_logs')->selectRaw('MIN(recorded_at) as min, MAX(recorded_at) as max')->first();
        $this->info('Recorded_at min: '.($minmax->min ?? 'null').' max: '.($minmax->max ?? 'null'));

        $this->info('\nLast 10 logs (UTC -> Asia/Jakarta):');
        $rows = DB::table('hourly_logs')->orderBy('recorded_at', 'desc')->limit(10)->get();
        foreach ($rows as $r) {
            $jakarta = Carbon::parse($r->recorded_at, 'UTC')->setTimezone('Asia/Jakarta')->format('Y-m-d H:i');
            $totalOutput = ($r->output_qty_normal ?? 0) + ($r->output_qty_reject ?? 0);
            $totalTarget = ($r->target_qty_normal ?? 0) + ($r->target_qty_reject ?? 0);
            $this->line("{$r->recorded_at} UTC -> {$jakarta} WIB | output: {$totalOutput} | target: {$totalTarget}");
        }

        $since24 = Carbon::now('Asia/Jakarta')->subDay()->setTimezone('UTC');
        $group = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('machine_groups as mg', 'pmg.machine_group_id', '=', 'mg.machine_group_id')
            ->where('hl.recorded_at', '>=', $since24)
            ->groupBy('mg.machine_group_id', 'mg.name')
            ->selectRaw('mg.name as machine_group_name, COALESCE(SUM(hl.output_qty_normal) + SUM(hl.output_qty_reject), 0) as total_output')
            ->orderByDesc('total_output')
            ->get();

        $this->info('\nGroup distribution (last 24h):');
        foreach ($group as $g) {
            $this->line("{$g->machine_group_name}: {$g->total_output}");
        }

        $since7 = Carbon::now('Asia/Jakarta')->subDays(7)->setTimezone('UTC');
        $prod = DB::table('hourly_logs as hl')
            ->leftJoin('production_machine_groups as pmg', 'hl.production_machine_group_id', '=', 'pmg.production_machine_group_id')
            ->leftJoin('productions as p', 'pmg.production_id', '=', 'p.production_id')
            ->where('hl.recorded_at', '>=', $since7)
            ->groupBy('p.production_id', 'p.production_name')
            ->selectRaw('p.production_name, COALESCE(SUM(hl.output_qty_normal) + SUM(hl.output_qty_reject), 0) as total_output')
            ->orderByDesc('total_output')
            ->get();

        $this->info('\nProduction weekly totals (7d):');
        foreach ($prod as $p) {
            $this->line("{$p->production_name}: {$p->total_output}");
        }

        return 0;
    }
}
