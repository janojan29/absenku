<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SchoolSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SchoolSettingController extends Controller
{
    public function edit(): View
    {
        $setting = SchoolSetting::singleton();

        return view('admin.settings.edit', [
            'setting' => $setting,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $setting = SchoolSetting::singleton();

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'check_in_start_time' => ['required', 'date_format:H:i'],
            'check_in_end_time' => ['required', 'date_format:H:i'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'check_out_start_time' => ['required', 'date_format:H:i'],
            'check_out_end_time' => ['required', 'date_format:H:i'],
        ]);

        $validator->after(function ($validator) use ($request) {
            $start = $request->input('check_out_start_time');
            $end = $request->input('check_out_end_time');

            if (is_string($start) && is_string($end) && $end <= $start) {
                $validator->errors()->add('check_out_end_time', 'Batas check-out harus setelah jam buka check-out.');
            }
        });

        $data = $validator->validate();

        $data['check_in_start_time'] = $data['check_in_start_time'] . ':00';
        $data['check_in_end_time'] = $data['check_in_end_time'] . ':00';
        $data['check_out_start_time'] = $data['check_out_start_time'] . ':00';
        $data['check_out_end_time'] = $data['check_out_end_time'] . ':00';

        $setting->update($data);

        return back()->with('status', 'Pengaturan sekolah tersimpan.');
    }
}
