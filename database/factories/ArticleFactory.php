<?php

declare(strict_types=1);

namespace NyonCode\KnowledgeBase\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use NyonCode\KnowledgeBase\Enums\ArticleKind;
use NyonCode\KnowledgeBase\Enums\ArticleStatus;
use NyonCode\KnowledgeBase\Enums\ContentFormat;
use NyonCode\KnowledgeBase\Enums\Visibility;
use NyonCode\KnowledgeBase\Models\Article;

/** @extends Factory<Article> */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->sentence(),
            'body' => '## '.fake()->sentence(3)."\n\n".fake()->paragraph(),
            'body_html' => '<h2 id="x">'.fake()->sentence(3).'</h2><p>'.fake()->paragraph().'</p>',
            'kind' => ArticleKind::HowTo,
            // Sloupec má výchozí hodnotu v databázi, jenže vyrobený model ji
            // do refreshe nezná — a editor se článku na formát ptá.
            'format' => ContentFormat::Markdown,
            'status' => ArticleStatus::Published,
            'visibility' => Visibility::Public,
            'published_at' => now(),
            'reviewed_at' => now(),
        ];
    }

    public function internal(): static
    {
        return $this->state(['visibility' => Visibility::Internal]);
    }

    public function draft(): static
    {
        return $this->state([
            'status' => ArticleStatus::Draft,
            'published_at' => null,
        ]);
    }

    /** Past its review date — the maintenance queue's raw material. */
    public function stale(): static
    {
        return $this->state([
            'reviewed_at' => now()->subYears(2),
            'published_at' => now()->subYears(2),
        ]);
    }
}
