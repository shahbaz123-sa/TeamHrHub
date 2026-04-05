<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CRM\Models\EmailSetting;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            $table->string('slug')->nullable();
        });

        EmailSetting::whereNull('slug')->get()->each(function ($setting) {
            $setting->slug = \Str::slug($setting->notify_on);
            $originalSlug = $setting->slug;
            $counter = 1;

            while (EmailSetting::where('slug', $setting->slug)->where('id', '!=', $setting->id)->exists()) {
                $setting->slug = $originalSlug . '-' . $counter++;
            }

            $setting->save();
        });

        Schema::table('email_settings', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('email_settings', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
