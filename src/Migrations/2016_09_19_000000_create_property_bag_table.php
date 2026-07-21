<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('property_bag', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('resource_type')->index();
            $table->integer('resource_id')->unsigned()->index();
            $table->string('key')->index();
            $table->text('value');
            $table->timestamps();

            $table->unique(['resource_type', 'resource_id', 'key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('property_bag');
    }
};
