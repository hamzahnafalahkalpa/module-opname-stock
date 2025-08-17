<?php

namespace Hanafalah\ModuleOpnameStock\Schemas;

use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Illuminate\Database\Eloquent\Model;

use Hanafalah\ModuleOpnameStock\{
    Enums\OpnameStock\Activity,
    Enums\OpnameStock\ActivityStatus,
    Enums\OpnameStock\Status
};
use Hanafalah\ModuleOpnameStock\Contracts\Data\OpnameStockData;
use Hanafalah\ModuleOpnameStock\Contracts\Schemas\OpnameStock as ContractsOpnameStock;

class OpnameStock extends PackageManagement implements ContractsOpnameStock
{
    protected string $__entity = 'OpnameStock';
    protected mixed $__order_by_created_at = 'desc'; //asc, desc, false
    public $opname_stock_model;

    public function prepareStoreOpnameStock(OpnameStockData $opname_stock_dto): Model{
        $opname_stock = $this->usingEntity()->updateOrCreate([
            'id' => $opname_stock_dto->id ?? null
        ], [
            'author_type'    => $opname_stock_dto->author_type,
            'author_id'      => $opname_stock_dto->author_id,
            'warehouse_type' => $opname_stock_dto->warehouse_type,
            'warehouse_id'   => $opname_stock_dto->warehouse_id,
            'status'         => $opname_stock_dto->status ?? Status::DRAFT->value
        ]);

        $opname_stock->pushActivity(Activity::OPNAME_STOCK->value, ActivityStatus::OPNAME_STOCK_CREATED->value);
        $transaction = $opname_stock->transaction;
        $opname_stock_dto->props['prop_transaction'] = $transaction->toViewApiOnlies('id','reference_type','reference_id','transaction_code');

        if (isset($opname_stock_dto->card_stocks) && count($opname_stock_dto->card_stocks) > 0) {
            $card_stocks = &$opname_stock_dto->card_stocks;
            foreach ($card_stocks as $card_stock) {
                $card_stock->transaction_id = $transaction->getKey();
                $card_stock->reference_type = $opname_stock->getMorphClass();
                $card_stock->reference_id   = $opname_stock->getKey();
                $this->schemaContract('card_stock')->prepareStoreCardStock($card_stock);
            }
        }
        $this->fillingProps($opname_stock, $opname_stock_dto->props);
        $opname_stock->save();
        return $this->opname_stock_model = $opname_stock;
    }

    // public function prepareUpdateOpnameStock(PurchasingUpdateData $purchasing_dto): Model{
    //     $model    = $this->usingEntity()->with('purchaseOrders')->findOrFail($purchasing_dto->id);
    //     $approver = &$purchasing_dto->props->approval->props['approver'];
    //     $approver = array_merge($model->approval['approver'],$approver);
    //     $procurement = $model->procurement;
    //     if (isset($purchasing_dto->props->props['status'])){
    //         $procurement->status = $purchasing_dto->props->props['status'];
    //     }
    //     if (isset($purchasing_dto->reported_at)){
    //         $procurement->reported_at  = $purchasing_dto->reported_at;
    //         foreach ($model->purchaseOrders as $purchaseOrder) {
    //             $po_procurement              = $purchaseOrder->procurement;
    //             $po_procurement->reported_at = $purchasing_dto->reported_at;
    //             $transaction = $purchaseOrder->procurement->transaction;
    //             $transaction->journal_reported_at = $purchasing_dto->reported_at;
    //             $transaction->save();
    //         }
    //     }
    //     $procurement->save();
    //     $this->fillingProps($model,$purchasing_dto->props);
    //     $model->save();
    //     return $this->opname_stock_model = $model;
    // }

    // public function prepareMainReportOpname(Model $opname): Model{
    //     if (isset($opname->reported_at)) throw new \Exception('Opname already reported', 422);
    //     $opname->reported_at = now();
    //     $opname->status = Status::REPORTED->value;
    //     $opname->save();

    //     $card_stocks = $opname->cardStocks;

    //     //UPDATING STOCK
    //     if (isset($card_stocks) && count($card_stocks) > 0) {
    //         foreach ($card_stocks as $card_stock) {
    //             $card_stock->reported_at = now();
    //             $card_stock->save();
    //         }
    //     }

    //     return $opname;
    // }

    // public function prepareReportOpname(?array $attributes = null): Model{
    //     $attributes ??= request()->all();
    //     if (!isset($attributes['id'])) throw new \Exception('No id provided', 422);
    //     $opname = $this->OpnameStockModel()->findOrFail($attributes['id']);
    //     return $this->opname_stock_model = $this->prepareMainReportOpname($opname);
    // }
}
