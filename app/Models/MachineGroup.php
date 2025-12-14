<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MachineGroup extends Model
{
    use HasFactory;
    protected $table = 'machine_groups';
    protected $primaryKey = 'machine_group_id';
    public $timestamps = true;

    protected $fillable = [
        'name',
        'description',
        'input_config',
    ];

    protected $casts = [
        'input_config' => 'array',
    ];

    /**
     * Input Config Structure Examples:
     *
     * Simple Qty Only (DB, SP, PJ, etc.):
     * ['type' => 'qty_only', 'fields' => ['qty']]
     *
     * Normal/Reject (SJ):
     * ['type' => 'normal_reject', 'fields' => ['qty_normal', 'qty_reject']]
     *
     * Multiple Grades (PD):
     * ['type' => 'grades', 'fields' => ['grades'], 'grade_types' => ['faceback', 'opc', 'ppc']]
     *
     * Grade + Qty (Film):
     * ['type' => 'grade_qty', 'fields' => ['grade', 'qty']]
     *
     * Qty + Ukuran (CNC, DS2):
     * ['type' => 'qty_ukuran', 'fields' => ['qty', 'ukuran']]
     *
     * All types can include 'keterangan' (description) field.
     */

    /* ACCESSORS & MUTATORS */
    protected function getNameAttribute($value)
    {
        return ucfirst(strtolower($value));
    }

    protected function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }

    /* RELATIONSHIPS */
    public function productionMachineGroups()
    {
        return $this->hasMany(ProductionMachineGroup::class, 'machine_group_id', 'machine_group_id');
    }

    /* HELPER METHODS */
    /**
     * Get the input fields required for this machine group.
     *
     * @return array<string>
     */
    public function getInputFields(): array
    {
        $config = $this->input_config ?? [];
        return $config['fields'] ?? ['qty'];
    }

    /**
     * Check if this machine group uses a specific input field.
     */
    public function usesInputField(string $field): bool
    {
        return in_array($field, $this->getInputFields(), true);
    }

    /**
     * Get grade types if this machine group uses grades.
     *
     * @return array<string>|null
     */
    public function getGradeTypes(): ?array
    {
        $config = $this->input_config ?? [];
        return $config['grade_types'] ?? null;
    }

    /**
     * Get the input type (e.g., 'qty_only', 'normal_reject', 'grades', etc.).
     */
    public function getInputType(): string
    {
        $config = $this->input_config ?? [];
        return $config['type'] ?? 'qty_only';
    }
}
