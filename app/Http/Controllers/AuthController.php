<?php

namespace App\Http\Controllers;

use App\Models\Admin;
use App\Models\Guru;
use Illuminate\Http\Request;
use App\Http\Controllers\KelasController;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function form(Request $request)
    {
        if ($request->session()->has('auth_role')) {
            return $this->arahkan($request->session()->get('auth_role'));
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|string|max:100',
            'password' => 'required|string|max:200',
        ]);

        foreach (['admin' => Admin::class, 'guru' => Guru::class] as $role => $model) {
            $user = $model::where('username', $data['username'])->first();

            if ($user && Hash::check($data['password'], $user->password)) {
                $request->session()->regenerate();
                $request->session()->put([
                    'auth_role' => $role,
                    'auth_id'   => $user->getKey(),
                    'auth_nama' => $user->nama,
                ]);

                return $this->arahkan($role);
            }
        }

        return back()->withInput($request->only('username'))
            ->with('error', 'Username atau password salah.');
    }

    public function logout(Request $request)
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah keluar.');
    }

    private function arahkan(string $role)
    {
        return redirect()->route($role === 'admin' ? 'admin.dashboard' : 'guru.dashboard');
    }
}