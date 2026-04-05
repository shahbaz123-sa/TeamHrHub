<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'make:module-file',
    description: 'Create modular files inside app/Modules/{Module}/...'
)]
class MakeModuleFile extends Command
{
    protected $signature = 'make:module-file
        {type : Type of file (controller, model, request, resource, repo, contract, factory, seeder, migration)}
        {name : Class name (e.g. Employee)}
        {module : Module name (e.g. HRM)}';

    public function handle()
    {
        $type = strtolower($this->argument('type'));
        $name = $this->argument('name');
        $module = $this->argument('module');
        $basePath = app_path("Modules/{$module}");

        $paths = [
            'controller' => "{$basePath}/Http/Controllers/{$name}Controller.php",
            'request'    => "{$basePath}/Http/Requests/{$name}Request.php",
            'resource'   => "{$basePath}/Http/Resources/{$name}Resource.php",
            'model'      => "{$basePath}/Models/{$name}.php",
            'repo'       => "{$basePath}/Repositories/{$name}Repository.php",
            'contract'   => "{$basePath}/Contracts/{$name}Interface.php",
            'factory'    => "{$basePath}/Database/Factories/{$name}Factory.php",
            'seeder'     => "{$basePath}/Database/Seeders/{$name}Seeder.php",
            'migration'  => "{$basePath}/Database/Migrations/" . now()->format('Y_m_d_His') . "_create_" . Str::snake(Str::pluralStudly($name)) . "_table.php",
        ];

        if (!isset($paths[$type])) {
            $this->error("Invalid file type: {$type}");
            return;
        }

        $path = $paths[$type];

        if (File::exists($path)) {
            $this->error("File already exists at: {$path}");
            return;
        }

        File::ensureDirectoryExists(dirname($path));

        $stub = $this->getStub($type, $name, $module);
        File::put($path, $stub);

        $this->info(ucfirst($type) . " created: {$path}");
    }

    protected function getStub($type, $name, $module)
    {
        $namespaceBase = "App\\Modules\\{$module}";

        return match ($type) {
            'controller' => "<?php

namespace {$namespaceBase}\\Http\\Controllers;

use App\\Http\\Controllers\\Controller;

class {$name}Controller extends Controller
{
    //
}
",
            'request' => "<?php

namespace {$namespaceBase}\\Http\\Requests;

use Illuminate\\Foundation\\Http\\FormRequest;

class {$name}Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
",
            'resource' => "<?php

namespace {$namespaceBase}\\Http\\Resources;

use Illuminate\\Http\\Resources\\Json\\JsonResource;

class {$name}Resource extends JsonResource
{
    public function toArray(\$request): array
    {
        return parent::toArray(\$request);
    }
}
",
            'model' => "<?php

namespace {$namespaceBase}\\Models;

use Illuminate\\Database\\Eloquent\\Model;

class {$name} extends Model
{
    protected \$guarded = [];
}
",
            'repo' => "<?php

namespace {$namespaceBase}\\Repositories;

class {$name}Repository
{
    //
}
",
            'contract' => "<?php

namespace {$namespaceBase}\\Contracts;

interface {$name}Interface
{
    //
}
",
            'factory' => "<?php

namespace {$namespaceBase}\\Database\\Factories;

use Illuminate\\Database\\Eloquent\\Factories\\Factory;

class {$name}Factory extends Factory
{
    public function definition(): array
    {
        return [];
    }
}
",
            'seeder' => "<?php

namespace {$namespaceBase}\\Database\\Seeders;

use Illuminate\\Database\\Seeder;

class {$name}Seeder extends Seeder
{
    public function run(): void
    {
        //
    }
}
",
            'migration' => "<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('" . Str::snake(Str::pluralStudly($name)) . "', function (Blueprint \$table) {
            \$table->id();
            \$table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('" . Str::snake(Str::pluralStudly($name)) . "');
    }
};
",
        };
    }
}