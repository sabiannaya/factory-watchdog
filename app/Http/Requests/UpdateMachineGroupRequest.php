<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMachineGroupRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'input_config' => ['nullable', 'array'],
            'input_config.type' => ['nullable', 'string', 'in:qty_only,normal_reject,grades,grade_qty,qty_ukuran,custom'],
            'input_config.fields' => ['required_with:input_config', 'array'],
            'input_config.fields.*' => ['string', 'in:qty,qty_normal,qty_reject,grades,grade,ukuran,keterangan'],
            'input_config.grade_types' => ['nullable', 'array'],
            'input_config.grade_types.*' => ['string', 'max:255'],
        ];
    }
}

