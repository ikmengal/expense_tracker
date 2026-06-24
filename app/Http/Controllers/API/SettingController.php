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

    // public function updateSettings(Request $request)
    // {
    //     $request->validate([
    //         'site_name' => 'required|string|max:255',
    //         'site_email' => 'required|email|max:255',
    //         'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048'
    //     ]);

    //     $settings = Setting::first() ?? new Setting();
    //     $settings->site_name = $request->site_name;
    //     $settings->site_email = $request->site_email;

    //     if ($request->hasFile('site_logo')) {
    //         if ($settings->site_logo) {
    //             Storage::disk('public')->delete($settings->site_logo);
    //         }
    //         $path = $request->file('site_logo')->store('site', 'public');
    //         $settings->site_logo = $path;
    //     }

    //     $settings->save();
    //     return response()->json(['message' => 'Website settings updated successfully', 'settings' => $settings], 200);
    // }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_email' => 'required|email|max:255',
            'site_url' => 'required|url|max:255',
            'currency_symbol' => 'required|string|max:10',
            'site_address' => 'required|string|max:500', // <-- Added
            'site_about' => 'required|string|max:1000',   // <-- Added
            'site_logo' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:2048',
            'site_banner' => 'nullable|image|mimes:jpeg,png,jpg,svg|max:3072',
            'site_favicon' => 'nullable|image|mimes:jpeg,png,jpg,ico,svg|max:1024'
        ]);

        $settings = Setting::first() ?? new Setting();
        $settings->site_name = $request->site_name;
        $settings->site_email = $request->site_email;
        $settings->site_url = $request->site_url;
        $settings->currency_symbol = $request->currency_symbol;
        $settings->site_address = $request->site_address; // <-- Saved to DB
        $settings->site_about = $request->site_about;

        // Handle Site Logo
        if ($request->hasFile('site_logo')) {
            if ($settings->site_logo) {
                Storage::disk('public')->delete($settings->site_logo);
            }
            $settings->site_logo = $request->file('site_logo')->store('site', 'public');
        }

        // Handle Site Banner
        if ($request->hasFile('site_banner')) {
            if ($settings->site_banner) {
                Storage::disk('public')->delete($settings->site_banner);
            }
            $settings->site_banner = $request->file('site_banner')->store('site', 'public');
        }

        // Handle Site Favicon
        if ($request->hasFile('site_favicon')) {
            if ($settings->site_favicon) {
                Storage::disk('public')->delete($settings->site_favicon);
            }
            $settings->site_favicon = $request->file('site_favicon')->store('site', 'public');
        }

        $settings->save();

        return response()->json([
            'message' => 'Website settings updated successfully',
            'settings' => $settings
        ], 200);
    }
}
