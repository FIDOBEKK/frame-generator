<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateFrameRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class FrameController extends Controller
{
    public function index(): View
    {
        $backgroundPath = AppSetting::getValue('background_path');
        $hasBackground = $backgroundPath !== null && Storage::disk('local')->exists($backgroundPath);

        return view('frame.index', [
            'hasBackground' => $hasBackground,
        ]);
    }

    public function generate(GenerateFrameRequest $request): BinaryFileResponse|RedirectResponse
    {
        $backgroundPath = AppSetting::getValue('background_path');

        if ($backgroundPath === null || ! Storage::disk('local')->exists($backgroundPath)) {
            return back()->withErrors([
                'photo' => 'Background image is not configured yet. Please contact the administrator.',
            ]);
        }

        $uploadPath = $request->file('photo')->store('tmp/uploads', 'local');
        $outputPath = 'tmp/generated/'.Str::uuid().'.jpg';
        $backgroundImage = null;
        $userImage = null;

        try {
            $backgroundImage = $this->createImageResource(Storage::disk('local')->path($backgroundPath));
            $userImage = $this->createImageResource(Storage::disk('local')->path($uploadPath));

            $x = (int) $request->integer('x');
            $y = (int) $request->integer('y');
            $scale = (float) $request->input('scale');

            $userWidth = imagesx($userImage);
            $userHeight = imagesy($userImage);
            $targetWidth = max(1, (int) round($userWidth * $scale));
            $targetHeight = max(1, (int) round($userHeight * $scale));

            imagecopyresampled(
                $backgroundImage,
                $userImage,
                $x,
                $y,
                0,
                0,
                $targetWidth,
                $targetHeight,
                $userWidth,
                $userHeight,
            );

            $absoluteOutputPath = Storage::disk('local')->path($outputPath);
            Storage::disk('local')->makeDirectory(dirname($outputPath));
            imagejpeg($backgroundImage, $absoluteOutputPath, 92);

            return response()->download($absoluteOutputPath, 'linkedin-frame.jpg', [
                'Content-Type' => 'image/jpeg',
            ])->deleteFileAfterSend(true);
        } catch (Throwable $throwable) {
            Log::error('Frame generation failed', [
                'error' => $throwable->getMessage(),
            ]);

            Storage::disk('local')->delete($outputPath);

            return back()->withErrors([
                'photo' => 'Could not generate image. Please try again with a different photo.',
            ]);
        } finally {
            if (is_resource($backgroundImage) || $backgroundImage instanceof \GdImage) {
                imagedestroy($backgroundImage);
            }

            if (is_resource($userImage) || $userImage instanceof \GdImage) {
                imagedestroy($userImage);
            }

            Storage::disk('local')->delete($uploadPath);
        }
    }

    public function backgroundPreview(): BinaryFileResponse
    {
        $backgroundPath = AppSetting::getValue('background_path');

        abort_if(
            $backgroundPath === null || ! Storage::disk('local')->exists($backgroundPath),
            404,
        );

        return response()->file(Storage::disk('local')->path($backgroundPath));
    }

    private function createImageResource(string $path): \GdImage
    {
        $file = file_get_contents($path);

        if ($file === false) {
            throw new \RuntimeException('Failed to read image file.');
        }

        $resource = imagecreatefromstring($file);

        if ($resource === false) {
            throw new \RuntimeException('Invalid image content.');
        }

        imagealphablending($resource, true);
        imagesavealpha($resource, true);

        return $resource;
    }
}
