<?php

namespace Hanafalah\ModuleOpnameStock\Providers;

use Illuminate\Support\ServiceProvider;
use Hanafalah\ModuleOpnameStock\Commands;

class CommandServiceProvider extends ServiceProvider
{
    protected $__commands = [];

    public function register()
    {
        $this->commands(config('module-opname-stock.commands', $this->__commands));
    }

    public function provides()
    {
        return $this->__commands;
    }
}
