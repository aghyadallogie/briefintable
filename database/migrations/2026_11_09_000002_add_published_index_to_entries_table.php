<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Statamic\Eloquent\Database\BaseMigration as Migration;

return new class extends Migration
{
    private const INDEX = 'entries_collection_published_published_at_index';

    /**
     * Every public list is "this collection, published only, newest first": one index that matches
     * the filter and the sort, instead of scanning the collection and sorting it in memory.
     */
    public function up()
    {
        Schema::table($this->prefix('entries'), function (Blueprint $table) {
            $table->index(['collection', 'published', 'published_at'], self::INDEX);
        });
    }

    public function down()
    {
        Schema::table($this->prefix('entries'), function (Blueprint $table) {
            $table->dropIndex(self::INDEX);
        });
    }
};
