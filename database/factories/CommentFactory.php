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
            'integration_id' => random_int(1,5), //default -> needs to be set to actual integration
            'review_slug' => $this->faker->unique()->slug(3), //default -> needs to be set to actual review
            'body' => $this->faker->realText(40),
            'author' => $this->faker->name,
            'author_email' => $this->faker->unique()->safeEmail,
            'author_slug' => $this->faker->randomElement($author_slugs),
            'datetime' => $this->faker->dateTime()
        ];
    }
}
