<?php

namespace Database\Factories;

use App\Models\ReadArticle;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReadArticle>
 */
class ReadArticleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $link = 'https://example.test/articles/'.fake()->unique()->uuid();

        return [
            'user_id' => User::factory(),
            'link' => $link,
            'link_hash' => ReadArticle::hashLink($link),
            'read_at' => now(),
        ];
    }

    public function forLink(string $link): static
    {
        return $this->state(fn (): array => [
            'link' => $link,
            'link_hash' => ReadArticle::hashLink($link),
        ]);
    }
}
