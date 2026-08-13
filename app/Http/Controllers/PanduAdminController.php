<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class PanduAdminController extends Controller
{
    /**
     * Tampilkan halaman login admin
     */
    public function showLogin()
    {
        if (session('pandu_admin_logged_in')) {
            return redirect()->route('pandu-admin.dashboard');
        }

        return view('pandu-admin.login');
    }

    /**
     * Proses login
     */
    public function processLogin(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        $envUsername = env('PANDU_ADMIN_USERNAME');
        $envPassword = env('PANDU_ADMIN_PASSWORD');

        if ($request->username === $envUsername && $request->password === $envPassword) {
            session(['pandu_admin_logged_in' => true]);

            return redirect()->route('pandu-admin.dashboard')->with('success', 'Berhasil login ke Pandu Admin Panel.');
        }

        return back()->with('error', 'Username atau password salah.');
    }

    /**
     * Tampilkan dashboard admin (konfigurasi Google API & Forms)
     */
    public function index()
    {
        $config = $this->readConfig();

        return view('pandu-admin.dashboard', compact('config'));
    }

    /**
     * Simpan konfigurasi
     */
    public function updateConfig(Request $request)
    {
        $request->validate([
            'google_api_base64' => 'nullable|string',
            'forms' => 'nullable|array',
            'forms.*.id' => 'required|string',
            'forms.*.slug' => 'required|string',
        ]);

        // Filter and re-index forms
        $forms = [];
        if ($request->has('forms') && is_array($request->forms)) {
            foreach ($request->forms as $f) {
                if (! empty($f['id']) && ! empty($f['slug'])) {
                    $forms[] = [
                        'id' => $f['id'],
                        'slug' => $f['slug'],
                    ];
                }
            }
        }

        $config = [
            'google_api_base64' => $request->input('google_api_base64', ''),
            'forms' => $forms,
        ];

        try {
            $path = storage_path('app/pandu_config.json');
            File::put($path, json_encode($config, JSON_PRETTY_PRINT));

            return back()->with('success', 'Konfigurasi berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Failed to save pandu config: '.$e->getMessage());

            return back()->with('error', 'Gagal menyimpan konfigurasi.');
        }
    }

    /**
     * Logout
     */
    public function logout()
    {
        session()->forget('pandu_admin_logged_in');

        return redirect()->route('pandu-admin.login');
    }

    /**
     * Helper to read JSON config
     */
    private function readConfig()
    {
        $path = storage_path('app/pandu_config.json');
        if (File::exists($path)) {
            $content = File::get($path);
            $data = json_decode($content, true);
            if (is_array($data)) {
                return $data;
            }
        }

        // Return default empty structure if file not exists or invalid
        return [
            'google_api_base64' => '',
            'forms' => [],
        ];
    }
}
