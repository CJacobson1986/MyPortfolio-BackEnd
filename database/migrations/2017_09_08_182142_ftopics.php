<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Ftopics extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
      Schema::create('ftopics', function (Blueprint $table) {
        $table->increments('id');
        $table->string('topicSlug');
        $table->string('topicTitle', 80);
        $table->longText('topicBody')->nullable();
        $table->integer('topicChannel')->default(1);
        $table->integer('topicViews')->default(0);
        $table->integer('topicReplies')->default(0);
        $table->boolean('topicArchived')->default(0);
        $table->boolean('topicFeature')->default(0);
        $table->boolean('allowReplies')->default(1);
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
      Schema::drop('ftopics');
    }
}
