<?php

namespace Hanafalah\ModuleOpnameStock\Resources\OpnameStock;

class ShowOpnameStock extends ViewOpnameStock
{
    public function toArray(\Illuminate\Http\Request $request): array
    {
        $arr = [
            'form' => $this->form,
            'author' => $this->relationValidation('author', function () {
                return $this->author->toViewApi()->resolve();
            }),
            'card_stocks' => $this->relationValidation('cardStocks', function () {
                return $this->cardStocks->transform(function ($cardStock) {
                    return $cardStock->toShowApi();
                });
            })
        ];
        $arr = $this->mergeArray(parent::toArray($request), $arr);

        return $arr;
    }
}
