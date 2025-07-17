<?php

namespace Hanafalah\ModuleOpnameStock\Enums\OpnameStock;

enum ActivityStatus: int
{
    case OPNAME_STOCK_CREATED   = 1;
    case OPNAME_STOCK_REPORTED  = 2;
    case OPNAME_STOCK_CANCELLED = 0;
}
