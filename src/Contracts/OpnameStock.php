<?php

namespace Hanafalah\ModuleOpnameStock\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Hanafalah\LaravelSupport\Contracts\Supports\DataManagement;

interface OpnameStock extends DataManagement
{
    public function getOpnameStock(): mixed;
    public function prepareRemoveOpnameStock(?array $attributes = null): bool;
    public function removeOpnameStock(): bool;
    public function preapreStoreOpnameStock(?array $attributes = null): Model;
    public function storeOpnameStock(): array;
    public function prepareShowOpnameStock(?Model $model = null): Model;
    public function showOpnameStock(?Model $model = null): array;
    public function prepareViewOpnameStockPaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null): LengthAwarePaginator;
    public function viewOpnameStockPaginate(int $perPage = 50, array $columns = ['*'], string $pageName = 'page', ?int $page = null, ?int $total = null): array;
    public function opnameStock(mixed $conditionals = null): Builder;
}
