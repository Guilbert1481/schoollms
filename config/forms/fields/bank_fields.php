<?php

use App\Models\Bank;

return [

    'bank_name' => [
        'label'    => 'Bank Name',
        'type'     => 'text',
        'model'    => Bank::class,
        'multiple' => true,
        'roles'    => ['admin'],
    ],

    'account_name' => [
        'label'    => 'Account Name',
        'type'     => 'text',
        'model'    => Bank::class,
        'multiple' => true,
        'roles'    => ['admin'],
    ],

    'account_number' => [
        'label'    => 'Account Number',
        'type'     => 'text',
        'model'    => Bank::class,
        'multiple' => true,
        'roles'    => ['admin'],
    ],

];