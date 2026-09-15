<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanySettingRequest;
use App\Models\CompanySetting;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CompanySettingController extends Controller
{
    public function __construct(private AuditService $audit) {}

    public function edit(): View
    {
        return view('settings.company', ['settings' => CompanySetting::firstOrCreate([], ['name' => 'Safar Point'])]);
    }

    public function update(CompanySettingRequest $request): RedirectResponse
    {
        $settings = CompanySetting::firstOrCreate([], ['name' => 'Safar Point']);
        $before = $settings->toArray();
        $data = $request->validated();
        $oldLogo = $settings->logo_path;
        if ($request->boolean('remove_logo') && $oldLogo) {
            Storage::disk('public')->delete($oldLogo);
            $data['logo_path'] = null;
        }
        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
        }
        unset($data['logo'], $data['remove_logo']);
        $settings->update($data);
        $this->audit->record($request, 'settings_updated', 'settings', 'Profil perusahaan diperbarui', $before, $settings->fresh()->toArray());

        return back()->with('status', 'Profil perusahaan berhasil disimpan.');
    }
}
