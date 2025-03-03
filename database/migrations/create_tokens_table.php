<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create(config('contacts.token.table'), function (Blueprint $table) {
            if (config('contacts.uuid')) {
                $table->uuid('uuid')->primary();
                $table->uuid('contact_uuid');
                $table->foreign('contact_uuid')->references('uuid')->on('contacts')->onDelete('cascade');
            } else {
                $table->id();
                $table->unsignedBigInteger('contact_id');
                $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            }

            $table->string('token');
            $table->string('method');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists(config('contacts.token.table'));
    }
};
