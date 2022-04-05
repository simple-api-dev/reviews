<?php

namespace Database\Factories;

use App\Models\Review;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Review::class;

    /**
     * Define the model's default state.
     *
     * @return array
     * @throws \Exception
     */
    public function definition()
    {
        //three queryable authors
        $author_slugs = [
            'author-one-slug',
            'author-two-slug',
            'author-three-slug'
        ];

        return [
            'integration_id' => random_int(1,5),
            'slug' => $this->faker->unique()->slug(3),
            'rating' => $this->faker->randomFloat(2, 0, 1),
            'title' => $this->faker->bs,
            'body' => $this->faker->realText(40),
            'bad' => $this->faker->boolean(20),
            'spam' => $this->faker->boolean(20),
            'helpful_counter' => $this->faker->randomNumber(2),
            'unhelpful_counter' => $this->faker->randomNumber(2),
            'related_slug' => $this->faker->slug(3),
            'author' => $this->faker->name,
            'author_email' => $this->faker->unique()->safeEmail,
            'author_slug' => $this->faker->randomElement($author_slugs),
            'datetime' => $this->faker->dateTime()
        ];
    }
}
