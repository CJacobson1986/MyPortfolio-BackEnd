<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Freplies extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('freplies', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('topicID');
        $table->integer('userID');
        $table->longText('replyBody');
        $table->boolean('replyflagged')->default(0);
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
      Schema::drop('freplies');
    }
}
