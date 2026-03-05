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
use Symfony\Component\Process\Process;
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
        $preparedUserImagePath = null;
        $backgroundImage = null;
        $userImage = null;

        try {
            $backgroundImage = $this->createImageResource(Storage::disk('local')->path($backgroundPath));

            $preparedUserImagePath = $this->removeBackgroundIfConfigured($uploadPath);
            $userImage = $this->createImageResource(Storage::disk('local')->path($preparedUserImagePath ?? $uploadPath));

            $backgroundWidth = imagesx($backgroundImage);
            $backgroundHeight = imagesy($backgroundImage);

            $previewWidth = (float) ($request->input('preview_width') ?: $backgroundWidth);
            $previewHeight = (float) ($request->input('preview_height') ?: $backgroundHeight);

            $ratioX = $backgroundWidth / max(1, $previewWidth);
            $ratioY = $backgroundHeight / max(1, $previewHeight);

            $x = (int) round($request->integer('x') * $ratioX);
            $y = (int) round($request->integer('y') * $ratioY);
            $scale = (float) $request->input('scale');

            $userWidth = imagesx($userImage);
            $userHeight = imagesy($userImage);
            $targetWidth = max(1, (int) round($userWidth * $scale * $ratioX));
            $targetHeight = max(1, (int) round($userHeight * $scale * $ratioY));

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

            if ($preparedUserImagePath !== null) {
                Storage::disk('local')->delete($preparedUserImagePath);
            }
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

    private function removeBackgroundIfConfigured(string $uploadPath): ?string
    {
        if (! config('frame-generator.background_removal.enabled')) {
            return null;
        }

        $pythonBin = (string) config('frame-generator.background_removal.python_bin');
        $scriptPath = (string) config('frame-generator.background_removal.script_path');
        $timeout = (int) config('frame-generator.background_removal.timeout_seconds', 60);

        if (! is_file($scriptPath)) {
            return null;
        }

        $inputAbsolutePath = Storage::disk('local')->path($uploadPath);
        $outputRelativePath = 'tmp/processed/'.Str::uuid().'.png';
        $outputAbsolutePath = Storage::disk('local')->path($outputRelativePath);
        Storage::disk('local')->makeDirectory(dirname($outputRelativePath));

        $process = new Process([
            $pythonBin,
            $scriptPath,
            $inputAbsolutePath,
            $outputAbsolutePath,
        ]);
        $process->setTimeout($timeout);

        try {
            $process->mustRun();

            if (Storage::disk('local')->exists($outputRelativePath)) {
                return $outputRelativePath;
            }
        } catch (Throwable $throwable) {
            Log::warning('Background removal failed, falling back to original photo', [
                'error' => $throwable->getMessage(),
            ]);
        }

        Storage::disk('local')->delete($outputRelativePath);

        return null;
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
