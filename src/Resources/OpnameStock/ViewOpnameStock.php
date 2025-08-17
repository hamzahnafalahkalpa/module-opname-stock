<?php

namespace Hanafalah\ModuleOpnameStock\Resources\OpnameStock;

use Hanafalah\LaravelSupport\Resources\ApiResource;

class ViewOpnameStock extends ApiResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray(\Illuminate\Http\Request $request): array
    {
        $arr = [
            'id' => $this->id,
            'author' => $this->relationValidation('author', function () {
                return $this->author->toViewApi()->resolve();
            }),
            'warehouse' => $this->relationValidation('warehouse', function () {
                return $this->warehouse->toViewApi()->resolve();
            }),
            'opname_code' => $this->procurement_code,
            'transaction' => $this->relationValidation('transaction', function () {
                return $this->transaction->toViewApi()->resolve();
            },$this->prop_transaction),
            'reported_at' => $this->reported_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];


        return $arr;
    }
}
