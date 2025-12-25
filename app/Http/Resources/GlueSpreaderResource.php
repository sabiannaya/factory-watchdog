<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GlueSpreaderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string,mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->glue_spreader_id,
            'name' => $this->name,
            'model' => $this->model,
            'glue_kg' => (float) $this->glue_kg,
            'hardener_kg' => (float) $this->hardener_kg,
            'powder_kg' => (float) $this->powder_kg,
            'colorant_kg' => (float) $this->colorant_kg,
            'anti_termite_kg' => (float) $this->anti_termite_kg,
            'viscosity' => $this->viscosity,
            'washes_per_day' => (int) $this->washes_per_day,
            'glue_loss_kg' => (float) $this->glue_loss_kg,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}
