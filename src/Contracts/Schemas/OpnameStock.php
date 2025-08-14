<?php

namespace Hanafalah\ModuleOpnameStock\Contracts\Schemas;

use Hanafalah\LaravelSupport\Contracts\Data\PaginateData;
use Hanafalah\LaravelSupport\Contracts\Supports\DataManagement;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Hanafalah\ModuleOpnameStock\Contracts\Data\OpnameStockData;
use Hanafalah\ModulePayment\Contracts\Schemas\FinanceStuff;

/**
 * @see \Hanafalah\ModuleOpnameStock\Schemas\OpnameStock
 * @method self setParamLogic(string $logic, bool $search_value = false, ?array $optionals = [])
 * @method self conditionals(mixed $conditionals)
 * @method mixed export(string $type)
 * @method bool deleteOpnameStock()
 * @method bool prepareDeleteOpnameStock(? array $attributes = null)
 * @method mixed getOpnameStock()
 * @method ?Model prepareShowOpnameStock(?Model $model = null, ?array $attributes = null)
 * @method array showOpnameStock(?Model $model = null)
 * @method Collection prepareViewOpnameStockList()
 * @method array viewOpnameStockList()
 * @method LengthAwarePaginator prepareViewOpnameStockPaginate(PaginateData $paginate_dto)
 * @method array viewOpnameStockPaginate(?PaginateData $paginate_dto = null)
 * @method array storeOpnameStock(?OpnameStockData $opname_stock_dto = null)
 * @method Builder opnameStock(mixed $conditionals = null)
 */
interface OpnameStock extends DataManagement
{
    public function prepareStoreOpnameStock(OpnameStockData $opname_stock_dto): Model;
}
