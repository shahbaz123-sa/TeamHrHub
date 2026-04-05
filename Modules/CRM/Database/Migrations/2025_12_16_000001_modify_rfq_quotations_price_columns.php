<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up()
    {
        Schema::table('rfq_quotations', function (Blueprint $table) {
            $table->decimal('price_per_unit', 18, 2)->change();
            $table->decimal('total_price', 18, 2)->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('rfq_quotations', function (Blueprint $table) {
            $table->decimal('price_per_unit', 8, 2)->change();
            $table->decimal('total_price', 8, 2)->nullable()->change();
        });
    }
};
