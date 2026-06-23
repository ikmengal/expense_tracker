<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function getSettings()
    {
        // Pehli row uthayega, agar nahi hy to default data object bhejega
        $settings = \App\Models\Setting::first() ?? [
            'site_name' => 'CashFlow App',
            'site_email' => 'admin@system.com',
            'site_logo' => null
        ];
        return response()->json($settings, 200);
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_email' => 'required|email|max:255',
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048'
        ]);

        $settings = Setting::first() ?? new Setting();
        $settings->site_name = $request->site_name;
        $settings->site_email = $request->site_email;

        if ($request->hasFile('site_logo')) {
            if ($settings->site_logo) {
                Storage::disk('public')->delete($settings->site_logo);
            }
            $path = $request->file('site_logo')->store('site', 'public');
            $settings->site_logo = $path;
        }

        $settings->save();
        return response()->json(['message' => 'Website settings updated successfully', 'settings' => $settings], 200);
    }
}
