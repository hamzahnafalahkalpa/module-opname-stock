<?php

use Hanafalah\ModuleOpnameStock\{
    Models as ModuleOpname,
    Commands as ModuleOpnameCommand
};
use Hanafalah\ModuleWarehouse\Models\Building\Room;

return [
    'namespace' => 'Hanafalah\\ModuleOpnameStock',
    'app' => [
        'contracts' => [
            //ADD YOUR CONTRACTS HERE
        ]
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts',
        'schema' => 'Schemas',
        'database' => 'Database',
        'data' => 'Data',
        'resource' => 'Resources',
        'migration' => '../assets/database/migrations'
    ],
    'commands' => [
        ModuleOpnameCommand\InstallMakeCommand::class
    ],
    'libs' => [
        'model' => 'Models',
        'contract' => 'Contracts'
    ],
    'database' => [
        'models' => [
        ]
    ],
    'author'    => 'User',
    'warehouse' => 'Room'
];
