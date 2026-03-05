<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FrameGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_a_jpg_download_for_valid_input(): void
    {
        Storage::fake('local');

        $backgroundPath = 'backgrounds/test-background.png';
        Storage::disk('local')->put($backgroundPath, $this->createPng(800, 800));
        AppSetting::setValue('background_path', $backgroundPath);

        $response = $this->post(route('frame.generate'), [
            'photo' => UploadedFile::fake()->image('avatar.jpg', 500, 500),
            'x' => 40,
            'y' => 50,
            'scale' => 1.2,
        ]);

        $response->assertOk();
        $response->assertHeader('content-type', 'image/jpeg');
        $response->assertHeader('content-disposition');
    }

    public function test_it_validates_required_fields_and_image_type(): void
    {
        $response = $this->post(route('frame.generate'), [
            'photo' => UploadedFile::fake()->create('file.txt', 10, 'text/plain'),
            'x' => 'not-a-number',
            'y' => 10000,
            'scale' => 99,
        ]);

        $response->assertSessionHasErrors(['photo', 'x', 'y', 'scale']);
    }

    private function createPng(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, 24, 24, 24);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return $binary === false ? '' : $binary;
    }
}
