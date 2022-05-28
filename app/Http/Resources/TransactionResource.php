<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
    #[ArrayShape([
        'id'          => "mixed",
        'initiator'   => "mixed",
        'type'        => "mixed",
        'amount'      => "mixed",
        'status'      => "mixed",
        'destination' => "mixed",
        'description' => "mixed",
        'account_id'  => "mixed"
    ])]
    public function toArray($request): array|JsonSerializable|Arrayable
    {
        return [
            'id'          => $this->id,
            'initiator'   => $this->initiator,
            'type'        => $this->type,
            'amount'      => $this->amount,
            'status'      => $this->status,
            'destination' => $this->destination,
            'description' => $this->description,
            'account_id'  => $this->account_id,
        ];
    }
}
