<?php

namespace Hanafalah\ModuleOpnameStock\Data;

use Hanafalah\LaravelSupport\Supports\Data;
use Hanafalah\ModuleItem\Data\CardStockData;
use Hanafalah\ModuleOpnameStock\Contracts\Data\OpnameStockData as DataOpnameStockData;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapName;
use Hanafalah\ModuleOpnameStock\{
    Enums\OpnameStock\Status
};
use Hanafalah\ModuleWarehouse\Enums\MainMovement\Direction;

class OpnameStockData extends Data implements DataOpnameStockData
{
    #[MapName('id')]
    #[MapInputName('id')]
    public mixed $id = null;

    #[MapName('author_type')]
    #[MapInputName('author_type')]
    public ?string $author_type = null;

    #[MapName('author_id')]
    #[MapInputName('author_id')]
    public mixed $author_id = null;

    #[MapName('warehouse_type')]
    #[MapInputName('warehouse_type')]
    public ?string $warehouse_type = null;

    #[MapName('warehouse_id')]
    #[MapInputName('warehouse_id')]
    public mixed $warehouse_id = null;

    #[MapName('card_stocks')]
    #[MapInputName('card_stocks')]
    #[DataCollectionOf(CardStockData::class)]
    public ?array $card_stocks = null;

    #[MapName('status')]
    #[MapInputName('status')]
    public ?string $status = null;

    #[MapName('reported_at')]
    #[MapInputName('reported_at')]
    public ?string $reported_at = null;

    #[MapName('reporting')]
    #[MapInputName('reporting')]
    public ?bool $reporting = null;

    #[MapName('props')]
    #[MapInputName('props')]
    public ?array $props = null;

    public static function before(array &$attributes){
        $new = static::new();
        $attributes['reporting'] ??= false;
        if ($attributes['reporting']) {
            $attributes['reported_at'] ??= now();
            $attributes['is_reported'] = true;
            $attributes['status'] = Status::REPORTED->value;
            if (isset($attributes['id'])){
                $opname_stock_model = $new->OpnameStockModel()->findOrFail($attributes['id']);
                $new->formsResolver($attributes, $opname_stock_model);
            }
        }
        $attributes['warehouse_type'] ??= config('module-opname-stock.warehouse','Warehouse');
        $attributes['author_type'] ??= config('module-opname-stock.author','User');
    }

    public static function after(OpnameStockData $data): OpnameStockData{
        $new = static::new();

        $props = &$data->props;
        $warehouse = $new->{$data->warehouse_type.'Model'}()->findOrFail($data->warehouse_id);
        $props['prop_warehouse'] = $warehouse->toViewApi()->resolve();

        $author = $new->{$data->author_type.'Model'}()->findOrFail($data->author_id);
        $props['prop_author'] = $author->toViewApi()->resolve();
        return $data;
    }

    protected function formsResolver(array &$attributes, Model $opname_stock_model){
        $new = static::new();
        if (isset($attributes['form']['items']) && is_array($attributes['form']['items']) && count($attributes['form']['items']) > 0){
            $attributes['card_stocks'] = [];
            foreach ($attributes['form']['items'] as $item) {
                $card_stock = [
                    'item_id' => $item['id'],
                    'stock_movements' => []
                ];
                $is_using_batch = null;
                if (!isset($item['id'])){
                    $card_stock['item'] = $item;
                }else{
                    $item_model = $new->ItemModel()->findOrFail($item['id']);
                    $is_using_batch = $item_model->is_using_batch;
                }
                $stock_movements = &$card_stock['stock_movements'];
                foreach ($item['item_stocks'] as $item_stock) {
                    $stock_movement = [
                        'funding_id'     => $item_stock['funding_id'],
                        'reference_id'   => $attributes['warehouse_id'],
                        'reference_type' => $attributes['warehouse_type'],
                        'item_stock_id'  => $item_stock['id'] ?? null,
                    ];
                    if (!isset($stock_movement['item_stock_id'])) {
                        $stock_movement['item_stock'] = [
                            'subject_type'     => 'Item',
                            'subject_id'       => $item['id'],
                            'warehouse_type'   => $attributes['warehouse_type'],
                            'warehouse_id'     => $attributes['warehouse_id'],
                            'funding_id'       => $item_stock['funding_id'] ?? null
                        ];
                    }
                    if (isset($is_using_batch) && $is_using_batch){
                        if (!isset($item_stock['stock_batches'])) throw new \Exception('No stock batches provided', 422);
                        $stock_movement['batch_movements'] = [];
                        foreach ($item_stock['stock_batches'] as $stock_batch) {
                            $batch_movement = [
                                'stock_batch_id' => $stock_batch['id'] ?? null,
                                'batch' => $stock_batch['batch'],
                                'qty'   => $stock_batch['qty'] ?? 0,
                            ];
                            if (!isset($batch_movement['stock_batch_id'])) {
                                $batch_movement['stock_batch'] = [
                                    'stock_id' => null,
                                    'batch_id' => null
                                ];
                            }
                            $stock_movement['batch_movements'][] = $batch_movement;
                        }
                    }else{
                        $stock_movement['qty'] ??= 0;
                    }
                    $stock_movement['direction'] = Direction::OPNAME->value;
                    $stock_movements[] = $stock_movement;
                }
                $attributes['card_stocks'][] = $card_stock;
            }
            $attributes['form'] = null;
        }
    }
}
