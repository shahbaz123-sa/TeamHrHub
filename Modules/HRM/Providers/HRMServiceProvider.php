<?php

namespace Modules\HRM\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HRM\Contracts\NotificationRepositoryInterface;
use Modules\HRM\Contracts\PayrollDeductionRepositoryInterface;
use Modules\HRM\Repositories\AssetRepository;
use Modules\HRM\Repositories\LeaveRepository;
use Modules\HRM\Repositories\NotificationRepository;
use Modules\HRM\Repositories\PayrollDeductionRepository;
use Modules\HRM\Repositories\TicketRepository;
use Modules\HRM\Repositories\EmployeeRepository;
use Modules\HRM\Repositories\SalaryRepository;
use Modules\HRM\Repositories\DependentRepository;
use Modules\HRM\Repositories\AttendanceRepository;
use Modules\HRM\Repositories\EmployeeAllowanceRepository;
use Modules\HRM\Repositories\EmployeeDeductionRepository;
use Modules\HRM\Repositories\EmployeeLoanRepository;
use Modules\HRM\Repositories\AssetAttributeRepository;
use Modules\HRM\Contracts\LeaveRepositoryInterface;
use Modules\HRM\Contracts\TicketRepositoryInterface;
use Modules\HRM\Contracts\SalaryRepositoryInterface;
use Modules\HRM\Repositories\CompanyPolicyRepository;
use Modules\HRM\Contracts\EmployeeRepositoryInterface;
use Modules\HRM\Contracts\DependentRepositoryInterface;
use Modules\HRM\Contracts\AttendanceRepositoryInterface;
use Modules\HRM\Contracts\CompanyPolicyRepositoryInterface;
use Modules\HRM\Contracts\EmployeeAllowanceRepositoryInterface;
use Modules\HRM\Contracts\EmployeeDeductionRepositoryInterface;
use Modules\HRM\Contracts\EmployeeLoanRepositoryInterface;
use Modules\HRM\Contracts\AssetRepositoryInterface;
use Modules\HRM\Contracts\AssetAttributeRepositoryInterface;

class HRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfigs();

        $this->app->bind(
            PayrollDeductionRepositoryInterface::class,
            PayrollDeductionRepository::class
        );

        $this->app->bind(
            CompanyPolicyRepositoryInterface::class,
            CompanyPolicyRepository::class
        );

        $this->app->bind(
            DependentRepositoryInterface::class,
            DependentRepository::class
        );

        $this->app->bind(
            EmployeeRepositoryInterface::class,
            EmployeeRepository::class
        );

        $this->app->bind(
            AttendanceRepositoryInterface::class,
            AttendanceRepository::class
        );

        $this->app->bind(
            LeaveRepositoryInterface::class,
            LeaveRepository::class
        );

        $this->app->bind(
            NotificationRepositoryInterface::class,
            NotificationRepository::class
        );

        $this->app->bind(
            TicketRepositoryInterface::class,
            TicketRepository::class
        );

        $this->app->bind(
            AssetRepositoryInterface::class,
            AssetRepository::class
        );

        $this->app->bind(
            SalaryRepositoryInterface::class,
            SalaryRepository::class
        );

        $this->app->bind(
            EmployeeAllowanceRepositoryInterface::class,
            EmployeeAllowanceRepository::class
        );

        $this->app->bind(
            EmployeeDeductionRepositoryInterface::class,
            EmployeeDeductionRepository::class
        );

        $this->app->bind(
            EmployeeLoanRepositoryInterface::class,
            EmployeeLoanRepository::class
        );

        $this->app->bind(
            AssetAttributeRepositoryInterface::class,
            AssetAttributeRepository::class
        );
    }
    public function boot(): void
    {
        $this->loadMigrations();
        $this->registerApiRoutes();
        $this->loadViews();
    }

    protected function modulePath(string $path = ''): string
    {
        return dirname(__DIR__) . "/{$path}";
    }

    protected function loadMigrations(): void
    {
        $this->loadMigrationsFrom($this->modulePath('Database/Migrations'));
    }

    protected function registerApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api')
            ->group($this->modulePath('Routes/api.php'));
    }

    protected function registerConfigs(): void
    {
        $moduleConfigPath = $this->modulePath('Configs');

        foreach (glob($moduleConfigPath . '/*.php') as $configFile) {
            $configName = basename($configFile, '.php');
            $this->mergeConfigFrom($configFile, $configName);
        }
    }

    protected function loadViews(): void
    {
        $this->loadViewsFrom($this->modulePath('Resources/views'), 'hrm');
    }
}
