<?php

declare(strict_types=1);

namespace Hanafalah\ModuleOpnameStock;

use Hanafalah\LaravelSupport\Providers\BaseServiceProvider;

class ModuleOpnameStockServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return $this
     */
    public function register()
    {
        $this->registerMainClass(ModuleOpnameStock::class)
            ->registerCommandService(Providers\CommandServiceProvider::class)
            ->registers([
                '*',
                'Services' => function () {
                    $this->binds([
                        Contracts\ModuleOpnameStock::class => ModuleOpnameStock::class,
                        Contracts\OpnameStock::class => Schemas\OpnameStock::class
                    ]);
                }
            ]);
    }

    /**
     * Get the base path of the package.
     *
     * @return string
     */
    protected function dir(): string
    {
        return __DIR__ . '/';
    }

    protected function migrationPath(string $path = ''): string
    {
        return database_path($path);
    }
}
