<?php

namespace Hanafalah\ModuleOpnameStock\Enums\OpnameStock;

enum Status: string
{
    case DRAFT      = 'DRAFT';
    case REPORTED   = 'REPORTED';
    case CANCELLED  = 'CANCELLED';
}
