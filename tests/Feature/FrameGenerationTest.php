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

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('frame-generator.background_removal.enabled', false);
    }

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
            'preview_width' => 400,
            'preview_height' => 400,
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

    public function test_it_maps_preview_coordinates_to_background_size(): void
    {
        Storage::fake('local');

        $backgroundPath = 'backgrounds/test-background.png';
        Storage::disk('local')->put($backgroundPath, $this->createPng(800, 800, [255, 255, 255]));
        AppSetting::setValue('background_path', $backgroundPath);

        $userPath = sys_get_temp_dir().'/fg-red-'.uniqid().'.png';
        file_put_contents($userPath, $this->createPng(100, 100, [220, 20, 20]));

        $uploaded = new UploadedFile($userPath, 'red.png', 'image/png', null, true);

        $response = $this->post(route('frame.generate'), [
            'photo' => $uploaded,
            'x' => 100,
            'y' => 100,
            'scale' => 1,
            'preview_width' => 400,
            'preview_height' => 400,
        ]);

        $response->assertOk();

        $downloaded = $response->baseResponse->getFile()->getPathname();
        $img = imagecreatefromjpeg($downloaded);
        $pixel = imagecolorat($img, 250, 250);
        $red = ($pixel >> 16) & 0xFF;
        $green = ($pixel >> 8) & 0xFF;
        $blue = $pixel & 0xFF;
        imagedestroy($img);

        $this->assertTrue($red > $green + 40 && $red > $blue + 40);
    }

    private function createPng(int $width, int $height, array $rgb = [24, 24, 24]): string
    {
        $image = imagecreatetruecolor($width, $height);
        $color = imagecolorallocate($image, $rgb[0], $rgb[1], $rgb[2]);
        imagefilledrectangle($image, 0, 0, $width, $height, $color);

        ob_start();
        imagepng($image);
        $binary = ob_get_clean();
        imagedestroy($image);

        return $binary === false ? '' : $binary;
    }
}
