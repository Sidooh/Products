<?php

namespace App\Http\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use JsonSerializable;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array|Arrayable|JsonSerializable
     */
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
            "account"     => $this->account,
            "payment"     => $this->payment,
            "product"     => $this->product->name,
            "created_at"  => $this->created_at,
        ];
    }
}
