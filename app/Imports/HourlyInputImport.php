<?php

namespace App\Imports;

use App\Models\HourlyLog;
use App\Models\MachineGroup;
use App\Models\Production;
use App\Models\ProductionMachineGroup;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class HourlyInputImport implements ToCollection, WithHeadingRow
{
    protected array $validatedRows = [];

    protected array $errors = [];

    protected int $rowNumber = 1; // Start after header

    protected array $productionCache = [];

    protected array $machineGroupCache = [];

    protected array $pmgCache = [];

    public function __construct()
    {
        // Pre-cache productions (case-sensitive lookup)
        Production::where('status', 'active')->get()->each(function ($p) {
            $this->productionCache[$p->production_name] = $p;
        });

        // Pre-cache machine groups (case-sensitive lookup using raw name)
        MachineGroup::all()->each(function ($m) {
            // Use raw value from attributes to avoid accessor transformation
            $rawName = $m->getAttributes()['name'];
            $this->machineGroupCache[$rawName] = $m;
        });

        // Pre-cache production machine groups
        ProductionMachineGroup::with(['production', 'machineGroup'])->get()->each(function ($pmg) {
            $prodName = $pmg->production->production_name ?? '';
            $mgRawName = $pmg->machineGroup->getAttributes()['name'] ?? '';
            $key = "{$prodName}|{$mgRawName}";
            $this->pmgCache[$key] = $pmg;
        });
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $this->rowNumber++;
            $this->validateRow($row->toArray());
        }
    }

    protected function validateRow(array $row): void
    {
        $errors = [];

        // Parse DateTime - handle various Excel formats
        $dateTimeRaw = $row['datetime'] ?? $row['date_time'] ?? $row['recorded_at'] ?? null;
        $recordedAt = null;

        if (empty($dateTimeRaw)) {
            $errors[] = 'DateTime is required';
        } else {
            $recordedAt = $this->parseDateTime($dateTimeRaw);
            if (! $recordedAt) {
                $errors[] = 'Invalid DateTime format. Use YYYY-MM-DD HH:00:00';
            }
        }

        // Production (case-sensitive)
        $productionName = trim((string) ($row['production'] ?? ''));
        $production = null;

        if (empty($productionName)) {
            $errors[] = 'Production is required';
        } else {
            $production = $this->productionCache[$productionName] ?? null;
            if (! $production) {
                $errors[] = "Production '{$productionName}' not found (case-sensitive)";
            }
        }

        // Machine Group (case-sensitive)
        $machineGroupName = trim((string) ($row['machine_group'] ?? $row['machinegroup'] ?? ''));
        $machineGroup = null;

        if (empty($machineGroupName)) {
            $errors[] = 'Machine Group is required';
        } else {
            $machineGroup = $this->machineGroupCache[$machineGroupName] ?? null;
            if (! $machineGroup) {
                $errors[] = "Machine Group '{$machineGroupName}' not found (case-sensitive)";
            }
        }

        // Validate Production + Machine Group combination exists
        $pmg = null;
        if ($production && $machineGroup) {
            $key = "{$productionName}|{$machineGroupName}";
            $pmg = $this->pmgCache[$key] ?? null;
            if (! $pmg) {
                $errors[] = "Machine Group '{$machineGroupName}' is not assigned to Production '{$productionName}'";
            }
        }

        // Qty Normal
        $qtyNormal = $row['qty_normal'] ?? $row['qtynormal'] ?? $row['normal'] ?? null;
        if ($qtyNormal !== null && $qtyNormal !== '') {
            if (! is_numeric($qtyNormal) || (int) $qtyNormal < 0) {
                $errors[] = 'Qty Normal must be a non-negative integer';
            }
        }

        // Qty Reject
        $qtyReject = $row['qty_reject'] ?? $row['qtyreject'] ?? $row['reject'] ?? null;
        if ($qtyReject !== null && $qtyReject !== '') {
            if (! is_numeric($qtyReject) || (int) $qtyReject < 0) {
                $errors[] = 'Qty Reject must be a non-negative integer';
            }
        }

        // Notes (optional)
        $notes = trim((string) ($row['notes_keterangan'] ?? $row['notes'] ?? $row['keterangan'] ?? ''));

        // Check for duplicate in existing database
        if ($pmg && $recordedAt) {
            $existing = HourlyLog::where('production_machine_group_id', $pmg->production_machine_group_id)
                ->where('recorded_at', $recordedAt)
                ->first();
            if ($existing) {
                $errors[] = "Duplicate entry: record already exists for this Production/Machine Group at {$recordedAt->format('Y-m-d H:00')}";
            }
        }

        // Build validated row data
        $validatedRow = [
            'row_number' => $this->rowNumber,
            'datetime_raw' => $dateTimeRaw,
            'datetime_parsed' => $recordedAt?->format('Y-m-d H:00'),
            'recorded_at' => $recordedAt,
            'production_name' => $productionName,
            'machine_group_name' => $machineGroupName,
            'qty_normal' => $qtyNormal !== null && $qtyNormal !== '' ? (int) $qtyNormal : null,
            'qty_reject' => $qtyReject !== null && $qtyReject !== '' ? (int) $qtyReject : null,
            'keterangan' => $notes ?: null,
            'production_machine_group_id' => $pmg?->production_machine_group_id,
            'errors' => $errors,
            'is_valid' => empty($errors),
        ];

        $this->validatedRows[] = $validatedRow;

        if (! empty($errors)) {
            $this->errors[$this->rowNumber] = $errors;
        }
    }

    /**
     * Parse various datetime formats from Excel.
     */
    protected function parseDateTime($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        // Handle Excel serial date number
        if (is_numeric($value)) {
            try {
                // Excel serial date (days since 1899-12-30 or 1904-01-01)
                $unixTimestamp = ($value - 25569) * 86400;

                return Carbon::createFromTimestamp($unixTimestamp, 'Asia/Jakarta')
                    ->setMinute(0)
                    ->setSecond(0);
            } catch (\Exception $e) {
                return null;
            }
        }

        // Try various string formats
        $formats = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d H',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y H',
            'm/d/Y H:i:s',
            'm/d/Y H:i',
            'Y/m/d H:i:s',
            'Y/m/d H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
        ];

        foreach ($formats as $format) {
            try {
                $dt = Carbon::createFromFormat($format, $value, 'Asia/Jakarta');
                if ($dt) {
                    return $dt->setMinute(0)->setSecond(0);
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try generic parse as last resort
        try {
            $dt = Carbon::parse($value, 'Asia/Jakarta');

            return $dt->setMinute(0)->setSecond(0);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get all validated rows.
     */
    public function getValidatedRows(): array
    {
        return $this->validatedRows;
    }

    /**
     * Get only valid rows (no errors).
     */
    public function getValidRows(): array
    {
        return array_filter($this->validatedRows, fn ($row) => $row['is_valid']);
    }

    /**
     * Get only invalid rows (with errors).
     */
    public function getInvalidRows(): array
    {
        return array_filter($this->validatedRows, fn ($row) => ! $row['is_valid']);
    }

    /**
     * Get all errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Check if all rows are valid.
     */
    public function hasErrors(): bool
    {
        return ! empty($this->errors);
    }

    /**
     * Get statistics summary.
     */
    public function getSummary(): array
    {
        $total = count($this->validatedRows);
        $valid = count($this->getValidRows());
        $invalid = count($this->getInvalidRows());

        return [
            'total_rows' => $total,
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'can_import' => $invalid === 0 && $valid > 0,
        ];
    }
}
