<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

class EnterpriseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    #[ArrayShape([
        'id'            => "mixed",
        'name'          => "mixed",
        'settings'      => "mixed",
        'created_at'    => "mixed",
        'float_account' => "\App\Http\Resources\FloatAccountResource"
    ])]
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            'id'            => $this->id,
            'name'          => $this->name,
            'settings'      => $this->settings,
            'created_at'    => $this->created_at,
            'float_account' => FloatAccountResource::make($this->whenLoaded('floatAccount')),
        ];
    }
}
