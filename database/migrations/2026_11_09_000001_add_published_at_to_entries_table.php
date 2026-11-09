<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::table($this->prefix('entries'), function (Blueprint $table) {
            $table->timestamp('published_at')->nullable()->after('updated_at');
        });
    }

    public function down()
    {
        Schema::table($this->prefix('entries'), function (Blueprint $table) {
            $table->dropColumn('published_at');
        });
    }
};
