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
        Schema::create('rankings', function (Blueprint $table) {
        $table->id();
        $table->string('rank_type', 20);      // daily / monthly / total
        $table->unsignedInteger('modeid')->nullable(); // モード別ランキング用
        $table->json('ranking_json');         // 上位50件をJSON化して保存
        $table->timestamps();

        $table->unique(['rank_type', 'modeid']); // 同じ粒度で一意
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rankings');
    }
};
