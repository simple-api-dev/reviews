<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('integration_id');
            $table->string('review_slug');
            $table->index(['integration_id', 'review_slug'], 'comments_integration_id_review_slug_index');

            $table->text('body');
            $table->string('author')->nullable();
            $table->string('author_email')->nullable();
            $table->string('author_slug')->nullable();

            $table->json("meta")->nullable();

            $table->timestamp('datetime')->useCurrent();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('comments', function (Blueprint $table)
        {
            $table->dropIndex(['integration_id_review_slug']);
        });
        Schema::dropIfExists('comments');
    }
}
