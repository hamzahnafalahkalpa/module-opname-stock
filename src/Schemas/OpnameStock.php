<?php

namespace Hanafalah\ModuleOpnameStock\Schemas;

use Hanafalah\ModuleItem\Contracts\CardStock;
use Hanafalah\ModuleItem\Contracts\Item;
use Hanafalah\ModuleItem\Contracts\ItemStock;
use Hanafalah\LaravelSupport\Supports\PackageManagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

use Hanafalah\ModuleOpnameStock\{
    Enums\OpnameStock\Activity,
    Enums\OpnameStock\ActivityStatus,
    Enums\OpnameStock\Status
};
use Hanafalah\ModuleOpnameStock\Contracts\OpnameStock as ContractsOpnameStock;
use Hanafalah\ModuleOpnameStock\Resources\OpnameStock\{
    ShowOpnameStock,
    ViewOpnameStock
};

class OpnameStock extends PackageManagement implements ContractsOpnameStock
{
    protected array $__guard   = [];
    protected array $__add     = [];
    protected string $__entity = 'OpnameStock';
    public $opname_stock_model;
    public $opname_item_model;

    protected array $__resources = [
        'show' => ShowOpnameStock::class,
        'view' => ViewOpnameStock::class

    ];

    public function getOpnameStock(): mixed
    {
        return $this->opname_stock_model;
    }

    protected function showUsingRelation()
    {
        return [
            'transaction',
            'warehouse',
            'author',
            'cardStocks' => function ($query) {
                $query->with([
                    'item',
                    'stockMovements' => function ($query) {
                        $query->with([
                            'reference',
                            'itemStock.funding',
                            'childs.batchMovements.batch',
                            'batchMovements.batch'
                        ]);
                    }
                ]);
            }
        ];
    }

    private function getWarehouseModel(): mixed
    {
        return app(config('module-opname-stock.warehouse'));
    }

    private function getWarehouseById(mixed $id): Model|null
    {
        $model = $this->getWarehouseModel();
        return $model->find($id);
    }

    public function prepareRemoveOpnameStock(?array $attributes = null): bool
    {
        $attributes ??= request()->all();
        if (!isset($attributes['id'])) throw new \Exception('OpnameStock id not found');

        $opname_stock = $this->OpnameStockModel()->findOrFail($attributes['id']);
        return $opname_stock->delete();
    }

    public function removeOpnameStock(): bool
    {
        return $this->transaction(function () {
            return $this->prepareRemoveOpnameStock();
        });
    }

    public function preapreStoreOpnameStock(?array $attributes = null): Model
    {
        $attributes ??= request()->all();

        $warehouse = $this->getWarehouseById($attributes['warehouse_id']);
        if (!isset($warehouse)) throw new \Exception("Warehouse not found");

        if (isset($attributes['author_id'])) {
            if (!isset($attributes['author_type'])) {
                $attributes['author_type'] = $this->getWrehouseModel()->getMorphClass();
            }
        }

        $opname_stock = $this->OpnameStockModel()->updateOrCreate([
            'id' => $attributes['id'] ?? null
        ], [
            'author_type'    => $attributes['author_type'] ?? null,
            'author_id'      => $attributes['author_id'] ?? null,
            'warehouse_type' => $warehouse->getMorphClass(),
            'warehouse_id'   => $warehouse->getKey(),
            'status'         => Status::DRAFT->value,
        ]);
        $opname_stock->pushActivity(Activity::OPNAME_STOCK->value, ActivityStatus::OPNAME_STOCK_CREATED->value);
        $opname_stock->record_all_item = $attributes['record_all_item'];
        $opname_stock->save();

        if (isset($attributes['card_stocks']) && count($attributes['card_stocks']) > 0) {
            $card_stocks = $attributes['card_stocks'];
            $transaction = $opname_stock->transaction;
            foreach ($card_stocks as $card_stock) {
                $card_stock['transaction_id'] = $transaction->getKey();
                $card_stock['direction']      = $this->StockMovementModel()::OPNAME;
                $card_stock['warehouse_id']   = $attributes['warehouse_id'];
                $this->prepareStoreOpnameItems($card_stock);
            }
        }

        return $this->opname_stock_model = $opname_stock;
    }

    public function prepareStoreOpnameItems(mixed $attributes = null): Model
    {
        $attributes ??= request()->all();

        if (!isset($attributes['transaction_id'])) {
            if (!isset(static::$opname_stock_model)) {
                $opanem_stock = static::$opname_stock_model;
            } else {
                $id = $attributes['opanem_stock_id'] ?? null;
                if (!isset($id)) throw new \Exception('No opname stock id provided', 422);
                $opname_stock = $this->OpnameStockModel()->find($id);
            }
            $attributes['transaction_id'] = $opname_stock->transaction->getKey();
        }
        $opname_item = $this->schemaContract('card_stock')
            ->prepareStoreCardStock($attributes);
        return $this->opname_item_model = $opname_item;
    }

    public function storeOpnameStock(): array
    {
        return $this->transaction(function () {
            return $this->showOpnameStock($this->preapreStoreOpnameStock());
        });
    }

    public function prepareShowOpnameStock(?Model $model = null): Model
    {
        $attributes ??= request()->all();

        $model ??= $this->getOpnameStock();
        if (!isset($model)) {
            $id = $attributes['id'] ?? null;
            if (!isset($id)) throw new \Exception('OpnameStock id not found');

            $model = $this->opnameStock()->with($this->showUsingRelation())->findOrFail($id);
        } else {
            $model->load($this->showUsingRelation());
        }
        return $this->opname_stock_model = $model;
    }

    public function showOpnameStock(?Model $model = null): array
    {
        return $this->transforming($this->__resources['show'], function () use ($model) {
            return $this->prepareShowOpnameStock($model);
        });
    }

    public function prepareViewOpnameStockPaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null): LengthAwarePaginator
    {
        $attributes ??= request()->all();
        return $this->opnameStock()
            ->when(isset($attributes['warehouse_id']), function ($query) use ($attributes) {
                $query->whereHas("warehouse", function ($query) use ($attributes) {
                    $query->where("id", $attributes['warehouse_id']);
                });
            })->paginate($perPage);
    }


    public function viewOpnameStockPaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null): array
    {
        $paginate_options = compact('perPage', 'columns', 'pageName', 'page', 'total');
        return $this->transforming($this->__resources['view'], function () use ($paginate_options) {
            return $this->prepareViewOpnameStockPaginate(...$this->arrayValues($paginate_options));
        });
    }

    public function prepareMainReportOpname(Model $opname): Model
    {
        if (isset($opname->reported_at)) throw new \Exception('Opname already reported', 422);
        $opname->reported_at = now();
        $opname->status = Status::REPORTED->value;
        $opname->save();

        $card_stocks = $opname->cardStocks;

        //UPDATING STOCK
        if (isset($card_stocks) && count($card_stocks) > 0) {
            foreach ($card_stocks as $card_stock) {
                $card_stock->reported_at = now();
                $card_stock->save();
            }
        }

        return $opname;
    }

    public function prepareReportOpname(?array $attributes = null): Model
    {
        $attributes ??= request()->all();
        if (!isset($attributes['id'])) throw new \Exception('No id provided', 422);
        $opname = $this->OpnameStockModel()->findOrFail($attributes['id']);
        return $this->opname_stock_model = $this->prepareMainReportOpname($opname);
    }

    public function reportOpnameStock(): array
    {
        return $this->transaction(function () {
            return $this->showOpnameStock($this->prepareReportOpname());
        });
    }

    public function prepareDeleteOpname(?array $attributes = null): bool
    {
        $attributes ??= request()->all();
        if (!isset($attributes['id'])) throw new \Exception('No id provided', 422);

        $model = $this->opnameStock()->findOrFail($attributes['id']);
        return $model->delete();
    }

    public function deleteOpnameStock(): bool
    {
        return $this->transaction(function () {
            return $this->prepareDeleteOpname();
        });
    }

    public function opnameStock(mixed $conditionals = null): Builder
    {
        $this->booting();
        return $this->OpnameStockModel()->with(['transaction', 'author', 'warehouse'])->conditionals($conditionals)->orderBy('created_at', 'desc');
    }
}
