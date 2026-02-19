# CLAUDE.md - Module Opname Stock

This file provides guidance to Claude Code when working with this module.

## Overview

`hanafalah/module-opname-stock` is a Laravel package for managing **stock opname (inventory counting)** operations in the Wellmed healthcare system. Stock opname is the process of physically counting inventory and reconciling it with recorded stock levels.

**Package namespace:** `Hanafalah\ModuleOpnameStock`

## CRITICAL: Memory Warning

The ServiceProvider uses `registers(['*'])` which can cause memory issues. See the "Safe Usage Patterns" section below.

```php
// Current implementation in ModuleOpnameStockServiceProvider.php
public function register()
{
    $this->registerMainClass(ModuleOpnameStock::class)
        ->registerCommandService(Providers\CommandServiceProvider::class)
        ->registers(['*']);  // CAUTION: May cause memory issues if Schema loading is heavy
}
```

After laravel-support v2.0, `registers(['*'])` only registers safe methods by default (Config, Model, Database, Migration, Route, Namespace, Provider), but Schema classes extending `PackageManagement` can still cause issues if they call `config()` during construction.

## Architecture

```
module-opname-stock/
├── assets/
│   ├── config/
│   │   └── config.php           # Module configuration
│   └── database/
│       └── migrations/
│           └── 0000_00_00_000001_create_opname_stocks.php
├── src/
│   ├── Commands/
│   │   ├── EnvironmentCommand.php    # Base command class
│   │   └── InstallMakeCommand.php    # Installation command
│   ├── Contracts/
│   │   ├── Data/
│   │   │   └── OpnameStockData.php   # DTO interface
│   │   ├── Schemas/
│   │   │   └── OpnameStock.php       # Schema contract
│   │   └── ModuleOpnameStock.php     # Main module contract
│   ├── Data/
│   │   └── OpnameStockData.php       # Data Transfer Object
│   ├── Enums/
│   │   └── OpnameStock/
│   │       ├── Activity.php          # Activity types
│   │       ├── ActivityStatus.php    # Activity status codes
│   │       └── Status.php            # Opname stock statuses
│   ├── Models/
│   │   └── OpnameStock.php           # Eloquent model
│   ├── Providers/
│   │   └── CommandServiceProvider.php
│   ├── Resources/
│   │   └── OpnameStock/
│   │       ├── ViewOpnameStock.php   # List/view API resource
│   │       └── ShowOpnameStock.php   # Detail API resource
│   ├── Schemas/
│   │   └── OpnameStock.php           # Business logic schema
│   ├── ModuleOpnameStock.php         # Main module class
│   └── ModuleOpnameStockServiceProvider.php
└── composer.json
```

## Dependencies

This module requires:
- `hanafalah/laravel-support` - Base framework support (CRITICAL)
- `hanafalah/module-warehouse` - Warehouse/location management
- `hanafalah/module-transaction` - Transaction tracking
- `hanafalah/module-item` - Item/inventory management

## Key Classes

### ModuleOpnameStockServiceProvider

The main service provider that registers the module.

**Location:** `src/ModuleOpnameStockServiceProvider.php`

### OpnameStock Model

**Location:** `src/Models/OpnameStock.php`

Eloquent model with the following traits:
- `HasUlids` - Uses ULIDs as primary keys
- `HasTransaction` - Links to transaction module
- `SoftDeletes` - Soft delete support
- `HasProps` - JSON props storage
- `HasActivity` - Activity logging

**Key relationships:**
```php
author()      // Polymorphic - who performed the opname
warehouse()   // Polymorphic - where the opname occurred
cardStock()   // morphOne - single card stock record
cardStocks()  // morphMany - multiple card stock records
```

**Auto-generated code:**
```php
// On creating, generates opname_code using encoding
$query->opname_code = static::hasEncoding('OPNAME_STOCK');
```

### OpnameStock Schema

**Location:** `src/Schemas/OpnameStock.php`

Business logic class extending `PackageManagement`. Handles:
- Creating/updating opname stock records
- Managing card stock entries
- Activity logging

**Key method:**
```php
public function prepareStoreOpnameStock(OpnameStockData $opname_stock_dto): Model
```

### OpnameStockData (DTO)

**Location:** `src/Data/OpnameStockData.php`

Data Transfer Object using `spatie/laravel-data`. Handles:
- Input validation and transformation
- Form data resolution for stock movements
- Batch movement handling

**Key properties:**
```php
$id             // Opname stock ID
$author_type    // Polymorphic type for author
$author_id      // Author ID
$warehouse_type // Polymorphic type for warehouse (default from config)
$warehouse_id   // Warehouse ID
$card_stocks    // Array of CardStockData
$status         // Status enum value
$reported_at    // Reporting timestamp
$props          // Additional properties
```

## Enums

### Status
```php
case DRAFT     = 'DRAFT';     // Initial state
case REPORTED  = 'REPORTED';  // After counting completed
case CANCELLED = 'CANCELLED'; // If cancelled
```

### Activity
```php
case OPNAME_STOCK = 'OPNAME_STOCK';
```

### ActivityStatus
```php
case OPNAME_STOCK_CREATED   = 1;  // When created
case OPNAME_STOCK_REPORTED  = 2;  // When reported
case OPNAME_STOCK_CANCELLED = 0;  // When cancelled
```

## Configuration

**Config file:** `assets/config/config.php`

**Config key:** `module-opname-stock`

```php
return [
    'namespace' => 'Hanafalah\\ModuleOpnameStock',
    'commands' => [
        InstallMakeCommand::class
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts'
    ],
    'database' => [
        'models' => []  // Override model classes here
    ],
    'author'    => 'User',      // Default author type
    'warehouse' => 'Room'       // Default warehouse type
];
```

### Configurable Options

| Key | Default | Description |
|-----|---------|-------------|
| `author` | `'User'` | Model type for opname author |
| `warehouse` | `'Room'` | Model type for warehouse location |
| `commands` | `[InstallMakeCommand::class]` | Artisan commands to register |

## Database Schema

**Table:** Determined by `OpnameStock` model's `$table` property

```sql
CREATE TABLE opname_stocks (
    id          ULID PRIMARY KEY,
    author_id   VARCHAR(36) NULL,
    author_type VARCHAR(100) NULL,
    warehouse_id   VARCHAR(36) NOT NULL,
    warehouse_type VARCHAR(100) NOT NULL,
    status      VARCHAR NOT NULL,
    reported_at TIMESTAMP NULL,
    props       JSON NULL,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    deleted_at  TIMESTAMP NULL,

    INDEX idx_author (author_id, author_type),
    INDEX idx_warehouse (warehouse_id, warehouse_type)
);
```

## API Resources

### ViewOpnameStock

For list views. Returns:
```php
[
    'id'          => $this->id,
    'author'      => $author->toViewApi(),
    'warehouse'   => $warehouse->toViewApi(),
    'opname_code' => $this->procurement_code,
    'transaction' => $transaction->toViewApi(),
    'reported_at' => $this->reported_at,
    'created_at'  => $this->created_at,
    'updated_at'  => $this->updated_at,
]
```

### ShowOpnameStock

For detail views. Extends `ViewOpnameStock` and adds:
```php
[
    'form'        => $this->form,
    'author'      => $author->toViewApi(),
    'card_stocks' => $cardStocks->transform(fn($cs) => $cs->toShowApi()),
]
```

## Safe Usage Patterns

### Using the Schema Contract

```php
// Get the schema via contract binding
$schema = app(\Hanafalah\ModuleOpnameStock\Contracts\Schemas\OpnameStock::class);

// Or via schemaContract helper (from other schemas)
$this->schemaContract('opname_stock')->prepareStoreOpnameStock($dto);
```

### Creating an Opname Stock

```php
use Hanafalah\ModuleOpnameStock\Data\OpnameStockData;

$dto = OpnameStockData::from([
    'author_id'      => $user->id,
    'author_type'    => 'User',
    'warehouse_id'   => $warehouse->id,
    'warehouse_type' => 'Room',
    'card_stocks'    => [
        [
            'item_id' => $item->id,
            'stock_movements' => [
                [
                    'funding_id'     => $funding->id,
                    'reference_id'   => $warehouse->id,
                    'reference_type' => 'Room',
                    'qty'            => 100,
                    'direction'      => 'OPNAME'
                ]
            ]
        ]
    ]
]);

$opnameStock = $schema->prepareStoreOpnameStock($dto);
```

### Reporting an Opname Stock

```php
$dto = OpnameStockData::from([
    'id'        => $opnameStock->id,
    'reporting' => true,  // Triggers report flow
    'form'      => [
        'items' => [
            [
                'id' => $item->id,
                'item_stocks' => [
                    [
                        'id'         => $itemStock->id,
                        'funding_id' => $funding->id,
                        'qty'        => 95  // Counted quantity
                    ]
                ]
            ]
        ]
    ]
]);
```

## Common Patterns

### Activity Tracking

The model automatically tracks activities:
```php
$opname_stock->pushActivity(
    Activity::OPNAME_STOCK->value,
    ActivityStatus::OPNAME_STOCK_CREATED->value
);
```

Activity list defined in model:
```php
public array $activityList = [
    'OPNAME_STOCK_1' => ['flag' => 'OPNAME_STOCK_CREATED', 'message' => 'Opname stock created'],
    'OPNAME_STOCK_2' => ['flag' => 'OPNAME_STOCK_REPORTED', 'message' => 'Opname stock reported'],
    'OPNAME_STOCK_0' => ['flag' => 'OPNAME_STOCK_CANCELLED', 'message' => 'Opname stock cancelled'],
];
```

### Transaction Integration

Each opname stock automatically creates a transaction record via `HasTransaction` trait:
```php
$transaction = $opname_stock->transaction;
```

### Card Stock Integration

Opname stocks link to card stocks (from `module-item`) for detailed item tracking:
```php
// Single card stock
$opname_stock->cardStock;

// Multiple card stocks
$opname_stock->cardStocks;
```

## Known Issues

### Command Signature Mismatch

The `InstallMakeCommand` has inconsistent naming:
- Signature: `module-patient:install` (should be `module-opname-stock:install`)
- References `ModulePatientServiceProvider` (should be `ModuleOpnameStockServiceProvider`)

This appears to be copy-paste from another module and needs fixing.

### EnvironmentCommand Config

The `EnvironmentCommand` sets config to `module-patient` instead of `module-opname-stock`:
```php
$this->setLocalConfig('module-patient');  // Should be 'module-opname-stock'
```

### ViewOpnameStock Resource

References `$this->procurement_code` but the model field is `opname_code`:
```php
'opname_code' => $this->procurement_code,  // Potential bug
```

## Testing

After modifying this module:

```bash
# Clear caches
docker exec -it wellmed-backbone php artisan config:clear
docker exec -it wellmed-backbone php artisan cache:clear

# Reload Octane
docker exec -it wellmed-backbone php artisan octane:reload

# Test the module loads
docker exec -it wellmed-backbone php artisan tinker
>>> app(\Hanafalah\ModuleOpnameStock\Contracts\Schemas\OpnameStock::class);

# Monitor for memory issues
docker logs wellmed-backbone 2>&1 | grep -i "memory\|fatal"
```

## Integration Points

This module integrates with:

| Module | Integration |
|--------|-------------|
| `module-warehouse` | Room/warehouse location references |
| `module-transaction` | Transaction tracking via `HasTransaction` |
| `module-item` | CardStock and StockMovement for inventory |
| `laravel-support` | Base classes (PackageManagement, BaseModel) |
| `laravel-has-props` | JSON props storage |

## Workflow

```
1. Create Opname Stock (DRAFT)
   └── Author initiates stock count
   └── Warehouse location specified
   └── Transaction record created
   └── Activity: OPNAME_STOCK_CREATED

2. Add Card Stocks
   └── Items to be counted
   └── Stock movements recorded
   └── Batch movements if applicable

3. Report Opname Stock (REPORTED)
   └── Physical count completed
   └── Discrepancies recorded
   └── Activity: OPNAME_STOCK_REPORTED

4. (Optional) Cancel (CANCELLED)
   └── Activity: OPNAME_STOCK_CANCELLED
```
