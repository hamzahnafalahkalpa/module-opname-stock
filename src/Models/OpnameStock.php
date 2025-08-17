<?php

namespace Hanafalah\ModuleOpnameStock\Models;

use Hanafalah\ModuleOpnameStock\Enums\OpnameStock\Activity;
use Hanafalah\ModuleOpnameStock\Enums\OpnameStock\ActivityStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\SoftDeletes;
use Hanafalah\LaravelHasProps\Concerns\HasProps;
use Hanafalah\LaravelSupport\Concerns\Support\HasActivity;
use Hanafalah\LaravelSupport\Models\BaseModel;
use Hanafalah\ModuleOpnameStock\Resources\OpnameStock\{ViewOpnameStock,ShowOpnameStock};
use Hanafalah\ModuleTransaction\Concerns\HasTransaction;

class OpnameStock extends BaseModel
{
  use HasUlids, HasTransaction, SoftDeletes, HasProps, HasActivity;

  public $incrementing  = false;
  protected $keyType    = 'string';
  protected $primaryKey = 'id';
  protected $list       = [
    'id',
    'author_type',
    'author_id',
    'warehouse_type',
    'warehouse_id',
    'status',
    'reported_at'
  ];

  protected static function booted(): void{
    parent::booted();
    static::creating(function ($query) {
      if (!isset($query->opname_code)) {
        $query->opname_code = static::hasEncoding('OPNAME_STOCK');
      }
    });
  }

  public function showUsingRelation(){
      return [
          'cardStocks' => function ($query) {
              $query->with([
                  'stockMovements' => function ($query) {
                      $query->with([
                          'reference',
                          'itemStock',
                          'childs.batchMovements',
                          'batchMovements'
                      ]);
                  }
              ]);
          }
      ];
  }

  public function getViewResource(){return ViewOpnameStock::class;}
  public function getShowResource(){return ShowOpnameStock::class;}

  public function author(){return $this->morphTo();}
  public function warehouse(){return $this->morphTo();}
  public function cardStock(){return $this->morphOneModel('CardStock','reference');}
  public function cardStocks(){return $this->morphManyModel('CardStock','reference');}

  public array $activityList = [
    Activity::OPNAME_STOCK->value . '_' . ActivityStatus::OPNAME_STOCK_CREATED->value    => ['flag' => 'OPNAME_STOCK_CREATED', 'message' => 'Opname stock created'],
    Activity::OPNAME_STOCK->value . '_' . ActivityStatus::OPNAME_STOCK_REPORTED->value   => ['flag' => 'OPNAME_STOCK_REPORTED', 'message' => 'Opname stock reported'],
    Activity::OPNAME_STOCK->value . '_' . ActivityStatus::OPNAME_STOCK_CANCELLED->value  => ['flag' => 'OPNAME_STOCK_CANCELLED', 'message' => 'Opname stock cancelled'],
  ];
}
