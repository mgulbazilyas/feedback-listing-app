<?php
namespace App\Console\Commands;

use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Feedback;
use App\Models\Comment;
use App\Models\Vote;

use Illuminate\Console\Command;
class TestDataSeeder extends Command
{
    protected $signature = 'app:add-fake-data';

    protected $description = 'Seed test data for users, feedbacks, comments, and votes';

    public function handle()
    {
        $faker = Faker::create();

        // Create Users
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'name' => $faker->name,
                'email' => $faker->unique()->email,
                'password' => bcrypt('test@123'), // Set a default password
            ]);
        }

        // Create Feedbacks, Comments, and Votes
        User::all()->each(function ($user) use ($faker) {
            for ($i = 0; $i < 5; $i++) {
                $feedback = Feedback::create([
                    'title' => $faker->sentence,
                    'description' => $faker->paragraph,
                    'category' => $faker->randomElement(['bug report', 'feature request', 'improvement']),
                    'user_id' => $user->id,
                ]);

                for ($j = 0; $j < 3; $j++) {
                    Comment::create([
                        'comment' => $faker->paragraph,
                        'feedback_id' => $feedback->id,
                        'user_id' => User::inRandomOrder()->first()->id,
                    ]);

                    Vote::create([
                        'type' => $faker->randomElement([true, false]),
                        'feedback_id' => $feedback->id,
                        'user_id' => User::inRandomOrder()->first()->id,
                    ]);
                }
            }
        });

        $this->info('Test data seeded successfully.');
    }
}
