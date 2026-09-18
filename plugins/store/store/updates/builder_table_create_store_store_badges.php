<?php namespace Store\Store\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateStoreStoreBadges extends Migration
{
    public function up()
    {
        Schema::create('store_store_badges', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->string('name');
            $table->string('icon');
            $table->string('slug')->unique();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->boolean('status');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('store_store_badges');
    }
}
