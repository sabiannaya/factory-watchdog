<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductionLogsExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    protected Collection $data;

    protected string $title;

    public function __construct(Collection $data, string $title = 'Production Logs')
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
            'Total Output',
            'Total Target',
            'Variance',
        ];
    }

    public function map($row): array
    {
        return [
            $row['recorded_hour'] ?? '-',
            $row['production_name'] ?? '-',
            $row['total_output'] ?? 0,
            $row['total_target'] ?? 0,
            $row['variance'] ?? 0,
        ];
    }

    public function title(): string
    {
        return $this->title;
    }
}
