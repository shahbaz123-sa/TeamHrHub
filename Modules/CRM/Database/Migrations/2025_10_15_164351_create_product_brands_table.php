<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Modules\CRM\Models\Product\Attribute\Value;
use Modules\CRM\Models\Product\Brand;

return new class extends Migration
{
    protected $connection = 'crm';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_brands', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $values = Value::where('attribute_id', 1)
            ->selectRaw("
                LOWER(
                    REGEXP_REPLACE(TRIM(name), '[^a-zA-Z0-9]+', '-', 'g')
                ) AS slug,
                name,
                NOW() AT TIME ZONE 'Asia/Karachi' AS created_at,
                NOW() AT TIME ZONE 'Asia/Karachi' AS updated_at
            ")
            ->get()
            ->toArray();

        Brand::insertOrIgnore($values);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_brands');
    }
};
