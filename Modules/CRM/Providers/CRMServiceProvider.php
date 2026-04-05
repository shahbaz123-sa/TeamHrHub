<?php

namespace Modules\CRM\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class CRMServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerConfigs();
        $this->bindProductContracts();
        $this->bindReportContracts();
        $this->bindNoticeContracts();
        $this->bindPostContracts();

        $this->app->bind(
            \Modules\CRM\Contracts\CustomerRepositoryInterface::class,
            \Modules\CRM\Repositories\CustomerRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\RfqRepositoryInterface::class,
            \Modules\CRM\Repositories\RfqRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Rfq\ItemRepositoryInterface::class,
            \Modules\CRM\Repositories\Rfq\ItemRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\OrderRepositoryInterface::class,
            \Modules\CRM\Repositories\OrderRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\EmailSettingRepositoryInterface::class,
            \Modules\CRM\Repositories\EmailSettingRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Wordpress\AuthRepositoryInterface::class,
            \Modules\CRM\Repositories\Wordpress\AuthRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\CreditApplicationRepositoryInterface::class,
            \Modules\CRM\Repositories\CreditApplicationRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\FormSubmissionRepositoryInterface::class,
            \Modules\CRM\Repositories\FormSubmissionRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\SupplierRepositoryInterface::class,
            \Modules\CRM\Repositories\SupplierRepository::class
        );
    }

    public function boot(): void
    {
        $this->registerApiRoutes();
        $this->registerCommands();
        $this->loadMigrations();
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

    protected function bindNoticeContracts()
    {
        $this->app->bind(
            \Modules\CRM\Contracts\Notice\TypeRepositoryInterface::class,
            \Modules\CRM\Repositories\Notice\TypeRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\NoticeRepositoryInterface::class,
            \Modules\CRM\Repositories\NoticeRepository::class
        );
    }

    protected function bindPostContracts()
    {
        $this->app->bind(
            \Modules\CRM\Contracts\PostRepositoryInterface::class,
            \Modules\CRM\Repositories\PostRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Post\TagRepositoryInterface::class,
            \Modules\CRM\Repositories\Post\TagRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Post\CategoryRepositoryInterface::class,
            \Modules\CRM\Repositories\Post\CategoryRepository::class
        );
    }

    protected function bindReportContracts()
    {
        $this->app->bind(
            \Modules\CRM\Contracts\Report\LatestReportRepositoryInterface::class,
            \Modules\CRM\Repositories\Report\LatestReportRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Report\FinancialReportRepositoryInterface::class,
            \Modules\CRM\Repositories\Report\FinancialReportRepository::class
        );
    }

    protected function bindProductContracts()
    {
        $this->app->bind(
            \Modules\CRM\Contracts\Product\TagRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\TagRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\CategoryRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\CategoryRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\AttributeRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\AttributeRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\Attribute\ValueRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\Attribute\ValueRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\UnitOfMeasurementRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\UnitOfMeasurementRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\CityRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\CityRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\BrandRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\BrandRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\ProductRepositoryInterface::class,
            \Modules\CRM\Repositories\ProductRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\GraphPriceRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\GraphPriceRepository::class
        );

        $this->app->bind(
            \Modules\CRM\Contracts\Product\DailyPriceRepositoryInterface::class,
            \Modules\CRM\Repositories\Product\DailyPriceRepository::class
        );
    }

    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Modules\CRM\Console\Commands\RegenerateProductSkuCommand::class,
                \Modules\CRM\Console\Commands\WooProductImportCommand::class,
                \Modules\CRM\Console\Commands\WooProductImportPostCommand::class,
            ]);
        }
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
        $this->loadViewsFrom($this->modulePath('Resources/views'), 'crm');
    }
}
