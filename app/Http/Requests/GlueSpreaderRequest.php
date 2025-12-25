<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GlueSpreaderRequest extends FormRequest
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
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:100'],
            'glue_kg' => ['required', 'numeric', 'min:0'],
            'hardener_kg' => ['required', 'numeric', 'min:0'],
            'powder_kg' => ['required', 'numeric', 'min:0'],
            'colorant_kg' => ['required', 'numeric', 'min:0'],
            'anti_termite_kg' => ['required', 'numeric', 'min:0'],
            'viscosity' => ['required', 'string', 'max:50'],
            'washes_per_day' => ['required', 'integer', 'min:0'],
            'glue_loss_kg' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
