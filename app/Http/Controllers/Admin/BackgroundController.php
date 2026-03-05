<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateBackgroundRequest;
use App\Models\AppSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BackgroundController extends Controller
{
    public function edit(): View
    {
        $backgroundPath = AppSetting::getValue('background_path');

        return view('admin.background.edit', [
            'hasBackground' => $backgroundPath !== null && Storage::disk('local')->exists($backgroundPath),
        ]);
    }

    public function update(UpdateBackgroundRequest $request): RedirectResponse
    {
        $oldPath = AppSetting::getValue('background_path');
        $newPath = $request->file('background')->store('backgrounds', 'local');

        AppSetting::setValue('background_path', $newPath);

        if ($oldPath !== null && $oldPath !== $newPath) {
            Storage::disk('local')->delete($oldPath);
        }

        return to_route('admin.background.edit')
            ->with('status', 'Background updated successfully.');
    }
}
