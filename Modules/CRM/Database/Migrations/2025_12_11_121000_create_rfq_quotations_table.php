<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up()
    {
        Schema::create('rfq_quotations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rfq_id');
            $table->text('procs');
            $table->date('due_date');
            $table->decimal('price_per_unit');
            $table->decimal('total_price')->nullable();
            $table->string('invoice')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rfq_quotations');
    }
};
