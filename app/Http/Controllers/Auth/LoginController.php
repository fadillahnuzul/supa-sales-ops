<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Core\EmployeeModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function create()
    {
        return Inertia::render('Login');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:1024'],
        ]);
        $username = trim($data['username']);
        $key = 'employee-login:'.hash('sha256', $username.'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }
        // SoftDeletes otomatis mengecualikan employee yang sudah dihapus.
        $employee = EmployeeModel::where('username', $username)->first();
        $valid = $employee && is_string($employee->password)
            && password_verify($data['password'], $employee->password);
        if (! $valid || ! $employee->is_active) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['username' => 'Username atau password salah, atau akun tidak aktif.']);
        }
        if (! $employee->hasSystemAccess()) {
            RateLimiter::clear($key);
            throw ValidationException::withMessages([
                'username' => 'Oops! Kamu belum punya akses. Hubungi Digicom atau Sales Coordinator ya!',
            ]);
        }
        // Tidak memakai Auth::attempt: hindari rehash password milik sistem employee.
        Auth::guard('web')->login($employee, false);
        $request->session()->regenerate();
        $request->session()->put('employee_password_hash', hash('sha256', $employee->password));
        RateLimiter::clear($key);

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}
