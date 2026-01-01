<?php

namespace App\Exports;

use App\Models\MachineGroup;
use App\Models\Production;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class HourlyInputImportTemplate implements WithMultipleSheets
{
    public function sheets(): array
    {
        return [
            new HourlyInputTemplateSheet,
            new ProductionsReferenceSheet,
            new MachineGroupsReferenceSheet,
        ];
    }
}

/**
 * Main template sheet for hourly input data.
 */
class HourlyInputTemplateSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        // Provide example rows for guidance
        return [
            ['2026-01-15 08:00:00', 'Plywood', 'Db', 100, 5, 'Example row 1'],
            ['2026-01-15 09:00:00', 'Plywood', 'Db', 150, 10, 'Example row 2'],
            ['', '', '', '', '', ''],
        ];
    }

    public function headings(): array
    {
        return [
            'DateTime',
            'Production',
            'Machine Group',
            'Qty Normal',
            'Qty Reject',
            'Notes (Keterangan)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        // Set column widths
        $sheet->getColumnDimension('A')->setWidth(22);
        $sheet->getColumnDimension('B')->setWidth(20);
        $sheet->getColumnDimension('C')->setWidth(18);
        $sheet->getColumnDimension('D')->setWidth(12);
        $sheet->getColumnDimension('E')->setWidth(12);
        $sheet->getColumnDimension('F')->setWidth(30);

        // Add a note to the first cell about datetime format
        $sheet->getComment('A1')->getText()->createTextRun(
            "Format: YYYY-MM-DD HH:00:00\n".
            "Example: 2026-01-15 08:00:00\n\n".
            "Excel may auto-format dates.\n".
            "The import will handle common\n".
            'Excel date formats automatically.'
        );

        // Add notes for Production and Machine Group
        $sheet->getComment('B1')->getText()->createTextRun(
            "Must match exactly (case-sensitive).\n".
            "See 'Productions' sheet for valid values."
        );

        $sheet->getComment('C1')->getText()->createTextRun(
            "Must match exactly (case-sensitive).\n".
            "See 'Machine Groups' sheet for valid values."
        );

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E2E8F0'],
                ],
            ],
            // Style example rows with light background
            '2:4' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'FEF3C7'],
                ],
                'font' => ['italic' => true, 'color' => ['rgb' => '92400E']],
            ],
        ];
    }

    public function title(): string
    {
        return 'Hourly Input';
    }
}

/**
 * Reference sheet listing all active productions.
 */
class ProductionsReferenceSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        return Production::where('status', 'active')
            ->orderBy('production_name')
            ->get(['production_name'])
            ->map(fn ($p) => [$p->production_name])
            ->toArray();
    }

    public function headings(): array
    {
        return ['Production Name (case-sensitive)'];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(35);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'DBEAFE'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Productions';
    }
}

/**
 * Reference sheet listing all machine groups.
 */
class MachineGroupsReferenceSheet implements FromArray, WithHeadings, WithStyles, WithTitle
{
    public function array(): array
    {
        return MachineGroup::orderBy('name')
            ->get(['name'])
            ->map(fn ($m) => [$m->getAttributes()['name']]) // Get raw value without accessor
            ->toArray();
    }

    public function headings(): array
    {
        return ['Machine Group Name (case-sensitive)'];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(35);

        return [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'D1FAE5'],
                ],
            ],
        ];
    }

    public function title(): string
    {
        return 'Machine Groups';
    }
}
