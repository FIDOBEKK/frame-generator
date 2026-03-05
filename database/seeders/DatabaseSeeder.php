<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $backgroundPath = 'backgrounds/default-background.png';

        if (! Storage::disk('local')->exists($backgroundPath)) {
            $background = imagecreatetruecolor(1200, 1200);

            $base = imagecolorallocate($background, 15, 23, 42);
            $accent = imagecolorallocate($background, 37, 99, 235);
            $white = imagecolorallocate($background, 255, 255, 255);

            imagefilledrectangle($background, 0, 0, 1200, 1200, $base);
            imagefilledellipse($background, 970, 260, 700, 700, $accent);
            imagefilledrectangle($background, 0, 960, 1200, 1200, $white);

            ob_start();
            imagepng($background);
            $png = ob_get_clean();
            imagedestroy($background);

            if ($png !== false) {
                Storage::disk('local')->put($backgroundPath, $png);
            }
        }

        AppSetting::setValue('background_path', $backgroundPath);
    }
}
