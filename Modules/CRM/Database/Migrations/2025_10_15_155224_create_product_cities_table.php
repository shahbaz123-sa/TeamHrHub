<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CRM\Models\Product\Attribute\Value;
use Modules\CRM\Models\Product\City;

return new class extends Migration
{
    protected $connection = 'crm';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_cities', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $values = Value::where('attribute_id', 2)
            ->selectRaw("
                LOWER(
                    REGEXP_REPLACE(name, '[^a-zA-Z0-9]+', '-', 'g')
                ) AS slug,
                name,
                NOW() AT TIME ZONE 'Asia/Karachi' AS created_at,
                NOW() AT TIME ZONE 'Asia/Karachi' AS updated_at
            ")
            ->get()
            ->toArray();

        City::insertOrIgnore($values);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_cities');
    }
};
