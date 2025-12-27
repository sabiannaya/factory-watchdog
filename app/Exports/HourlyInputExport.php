<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class HourlyInputExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $data;

    protected string $title;

    public function __construct(Collection $data, string $title = 'Hourly Input')
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
            'Hour',
            'Production',
            'Machine Group',
            'Total Qty',
            'Qty Normal',
            'Qty Reject',
        ];
    }

    public function map($row): array
    {
        return [
            $row['hour'] ?? '-',
            $row['production_name'] ?? '-',
            $row['machine_group'] ?? '-',
            ($row['output_qty_normal'] ?? 0) + ($row['output_qty_reject'] ?? 0),
            $row['output_qty_normal'] ?? 0,
            $row['output_qty_reject'] ?? 0,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
