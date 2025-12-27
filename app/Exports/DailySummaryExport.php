<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class DailySummaryExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $data;

    protected string $title;

    public function __construct(Collection $data, string $title = 'Daily Summary')
    {
        $this->data = $data;
        $this->title = $title;
    }

    public function collection()
    {
        return $this->data;
    }

    public function headings(): array
    {
        return [
            'Production',
            'Machine Group',
            'Machine Count',
            'Target Normal',
            'Target Reject',
            'Target Total',
            'Output Normal',
            'Output Reject',
            'Output Total',
            'Variance',
            'Achievement %',
            'Status',
        ];
    }

    public function map($row): array
    {
        return [
            $row['production_name'] ?? '-',
            $row['machine_group_name'] ?? '-',
            $row['machine_count'] ?? 0,
            $row['target_qty_normal'] ?? 0,
            $row['target_qty_reject'] ?? 0,
            $row['target_total'] ?? 0,
            $row['actual_qty_normal'] ?? 0,
            $row['actual_qty_reject'] ?? 0,
            $row['actual_total'] ?? 0,
            $row['variance'] ?? 0,
            $row['achievement_percentage'] ?? 0,
            $row['status'] ?? '-',
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
