<?php

namespace App\Tests\Story;

use App\Tests\Factory\CategoryFactory;
use App\Tests\Factory\DifficultyFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'main')]
final class AppStory extends Story
{
    public function build(): void
    {
        $categories = [
            'General Knowledge',
            'Entertainment: Books',
            'Entertainment: Film',
            'Entertainment: Music',
            'Entertainment: Musicals & Theatres',
            'Entertainment: Television',
            'Entertainment: Video Games',
            'Entertainment: Board Games',
            'Science & Nature',
            'Science: Computers',
            'Science: Mathematics',
            'Mythology',
            'Sports',
            'Geography',
            'History',
            'Politics',
            'Art',
            'Celebrities',
            'Animals',
            'Vehicles',
            'Entertainment: Comics',
            'Science: Gadgets',
            'Entertainment: Japanese Anime & Manga',
            'Entertainment: Cartoon & Animations',
        ];

        foreach ($categories as $name) {
            CategoryFactory::createOne(['name' => $name]);
        }

        foreach (['easy', 'medium', 'hard'] as $name) {
            DifficultyFactory::createOne(['name' => $name]);
        }
    }
}
