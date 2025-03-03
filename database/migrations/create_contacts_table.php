<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(config('contacts.table'), function (Blueprint $table) {
            if (config('contacts.uuid')) {
                $table->uuid('uuid')->primary();
            } else {
                $table->id();
            }

            if (config('contacts.uuidMorph')) {
                $table->uuidMorphs('contactable');
            } else {
                $table->morphs('contactable');
            }

            // Contact fields
            $table->enum('type', array_keys(config('contacts.verifiers')));
            $table->string('value');
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_verified')->default(false);
            $table->string('verification_token')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Example for MySQL generated columns (if your DB supports it)
            // Using raw statements for demonstration; adjust as needed.

            // 1) A generated column for uniqueness
            // 2) A generated column for a single primary contact

            $table->string('value_for_unique')->storedAs("IF(`deleted_at` IS NULL, `value`, NULL)");
            $table->string('contactable_id_for_primary')->storedAs("IF(`deleted_at` IS NULL AND `is_primary` = 1, CONCAT(`contactable_type`, '-', `contactable_id`, '-', `type`), NULL)");

            $table->unique('value_for_unique', 'unique_contact_value');
            $table->unique('contactable_id_for_primary', 'unique_primary_contact');
        });
    }

    public function down()
    {
        Schema::dropIfExists(config('contacts.table'));
    }
};
