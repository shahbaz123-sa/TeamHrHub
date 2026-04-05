<?php

namespace Modules\HRM\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\HRM\Repositories\BranchRepository;
use Modules\HRM\Repositories\GoalTypeRepository;
use Modules\HRM\Repositories\JobStageRepository;
use Modules\HRM\Repositories\AllowanceRepository;
use Modules\HRM\Repositories\AssetTypeRepository;
use Modules\HRM\Repositories\AwardTypeRepository;
use Modules\HRM\Repositories\DeductionRepository;
use Modules\HRM\Repositories\LeaveTypeRepository;
use Modules\HRM\Repositories\CompetencyRepository;
use Modules\HRM\Repositories\DepartmentRepository;
use Modules\HRM\Repositories\LoanOptionRepository;
use Modules\HRM\Repositories\DesignationRepository;
use Modules\HRM\Repositories\ExpenseTypeRepository;
use Modules\HRM\Repositories\JobCategoryRepository;
use Modules\HRM\Repositories\PayslipTypeRepository;
use Modules\HRM\Contracts\BranchRepositoryInterface;
use Modules\HRM\Repositories\DocumentTypeRepository;
use Modules\HRM\Repositories\TrainingTypeRepository;
use Modules\HRM\Contracts\GoalTypeRepositoryInterface;
use Modules\HRM\Contracts\JobStageRepositoryInterface;
use Modules\HRM\Repositories\EmploymentTypeRepository;
use Modules\HRM\Repositories\TicketCategoryRepository;
use Modules\HRM\Contracts\AllowanceRepositoryInterface;
use Modules\HRM\Contracts\AssetTypeRepositoryInterface;
use Modules\HRM\Contracts\AwardTypeRepositoryInterface;
use Modules\HRM\Contracts\DeductionRepositoryInterface;
use Modules\HRM\Contracts\LeaveTypeRepositoryInterface;
use Modules\HRM\Repositories\PerformanceTypeRepository;
use Modules\HRM\Repositories\TerminationTypeRepository;
use Modules\HRM\Contracts\CompetencyRepositoryInterface;
use Modules\HRM\Contracts\DepartmentRepositoryInterface;
use Modules\HRM\Contracts\LoanOptionRepositoryInterface;
use Modules\HRM\Repositories\EmploymentStatusRepository;
use Modules\HRM\Contracts\DesignationRepositoryInterface;
use Modules\HRM\Contracts\ExpenseTypeRepositoryInterface;
use Modules\HRM\Contracts\JobCategoryRepositoryInterface;
use Modules\HRM\Contracts\PayslipTypeRepositoryInterface;
use Modules\HRM\Contracts\DocumentTypeRepositoryInterface;
use Modules\HRM\Contracts\TrainingTypeRepositoryInterface;
use Modules\HRM\Contracts\EmploymentTypeRepositoryInterface;
use Modules\HRM\Contracts\TicketCategoryRepositoryInterface;
use Modules\HRM\Contracts\PerformanceTypeRepositoryInterface;
use Modules\HRM\Contracts\TerminationTypeRepositoryInterface;
use Modules\HRM\Contracts\EmploymentStatusRepositoryInterface;

class HrAdminSetupServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            BranchRepositoryInterface::class,
            BranchRepository::class
        );

        $this->app->bind(
            DepartmentRepositoryInterface::class,
            DepartmentRepository::class
        );

        $this->app->bind(
            DesignationRepositoryInterface::class,
            DesignationRepository::class
        );

        $this->app->bind(
            LeaveTypeRepositoryInterface::class,
            LeaveTypeRepository::class
        );

        $this->app->bind(
            DocumentTypeRepositoryInterface::class,
            DocumentTypeRepository::class
        );

        $this->app->bind(
            PayslipTypeRepositoryInterface::class,
            PayslipTypeRepository::class
        );

        $this->app->bind(
            AllowanceRepositoryInterface::class,
            AllowanceRepository::class
        );

        $this->app->bind(
            LoanOptionRepositoryInterface::class,
            LoanOptionRepository::class
        );

        $this->app->bind(
            DeductionRepositoryInterface::class,
            DeductionRepository::class
        );

        $this->app->bind(
            GoalTypeRepositoryInterface::class,
            GoalTypeRepository::class
        );

        $this->app->bind(
            TrainingTypeRepositoryInterface::class,
            TrainingTypeRepository::class
        );

        $this->app->bind(
            AwardTypeRepositoryInterface::class,
            AwardTypeRepository::class
        );

        $this->app->bind(
            EmploymentStatusRepositoryInterface::class,
            EmploymentStatusRepository::class
        );

        $this->app->bind(
            EmploymentTypeRepositoryInterface::class,
            EmploymentTypeRepository::class
        );

        $this->app->bind(
            TerminationTypeRepositoryInterface::class,
            TerminationTypeRepository::class
        );

        $this->app->bind(
            JobCategoryRepositoryInterface::class,
            JobCategoryRepository::class
        );

        $this->app->bind(
            JobStageRepositoryInterface::class,
            JobStageRepository::class
        );

        $this->app->bind(
            PerformanceTypeRepositoryInterface::class,
            PerformanceTypeRepository::class
        );

        $this->app->bind(
            CompetencyRepositoryInterface::class,
            CompetencyRepository::class
        );

        $this->app->bind(
            ExpenseTypeRepositoryInterface::class,
            ExpenseTypeRepository::class
        );

        $this->app->bind(
            TicketCategoryRepositoryInterface::class,
            TicketCategoryRepository::class
        );

        $this->app->bind(
            AssetTypeRepositoryInterface::class,
            AssetTypeRepository::class
        );
    }
    public function boot(): void
    {
        $this->loadMigrations();
        $this->registerApiRoutes();
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

    protected function modulePath(string $path = ''): string
    {
        return dirname(__DIR__) . "/{$path}";
    }
}
