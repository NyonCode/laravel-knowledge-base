<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use NyonCode\KnowledgeBase\Enums\Visibility;
use NyonCode\KnowledgeBase\Models\Category;

/** @extends Factory<Category> */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'visibility' => Visibility::Public,
            'sort_order' => 0,
        ];
    }

    public function internal(): static
    {
        return $this->state(['visibility' => Visibility::Internal]);
    }
}
