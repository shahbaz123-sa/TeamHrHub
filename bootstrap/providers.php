<?php

return [
    App\Providers\AgentServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\CRM\Providers\CRMServiceProvider::class,
    Modules\Chat\Providers\ChatServiceProvider::class,
    Modules\HRM\Providers\HRMServiceProvider::class,
    Modules\HRM\Providers\HrAdminSetupServiceProvider::class,
];
