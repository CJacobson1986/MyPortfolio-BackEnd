<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Options extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('options', function (Blueprint $table) {
        $table->increments('id');
        $table->integer('userID');
        $table->string('website');
        $table->string('baseurl');
        $table->longText('siteLogo')->nullable();
        $table->longText('aboutWebsite')->nullable();
        $table->timestamps(3);
      });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      Schema::drop('options');
    }
}
