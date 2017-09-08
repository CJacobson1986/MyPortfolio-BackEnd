<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Fchannels extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('fchannels', function(Blueprint $table)
      {
        $table->increments('id');
        $table->string('channelTitle');
        $table->text('channelDesc');
        $table->string('channelSlug');
        $table->boolean('channelArchived');
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
      Schema::drop('fchannels');
    }
}
