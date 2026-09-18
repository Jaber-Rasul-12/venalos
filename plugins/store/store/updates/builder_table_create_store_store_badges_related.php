<?php namespace Store\Store\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class BuilderTableCreateStoreStoreBadgesRelated extends Migration
{
    public function up()
    {
        Schema::create('store_store_badges_related', function($table)
        {
            $table->engine = 'InnoDB';
            $table->integer('badge_id')->unsigned();
            $table->integer('badge_related_id')->unsigned();
            $table->primary(['badge_id','badge_related_id']);
                                    $table->foreign('badge_id')
                        ->references('id')
                        ->on('store_store_badges')
                        ->onDelete('cascade')->onUpdate('cascade');
                    $table->foreign('badge_related_id')
                        ->references('id')
                        ->on('store_store_products')
                        ->onDelete('cascade')->onUpdate('cascade');
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('store_store_badges_related');
    }
}
