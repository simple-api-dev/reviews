<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('id');

            $table->unsignedBigInteger('integration_id');
            $table->string('slug')->unique();
            $table->index(['integration_id', 'slug'], 'reviews_integration_id_slug_index');

            $table->decimal('rating', 3, 2)->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->boolean('bad')->default(false);
            $table->boolean('spam')->default(false);
            $table->integer('helpful_counter')->default(0);
            $table->integer('unhelpful_counter')->default(0);
            $table->string('related_slug')->nullable();

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
        Schema::table('reviews', function (Blueprint $table)
        {
            $table->dropIndex(['integration_id_slug']);
        });
        Schema::dropIfExists('reviews');
    }
}
