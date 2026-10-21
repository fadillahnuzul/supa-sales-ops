<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
// use Inertia\Inertia;

class EmployeeSession
{
    public function handle(Request $request, Closure $next)
    {
        $employee = $request->user();
        if ($employee) {
            $changed = $request->hasSession()
                && (! $request->session()->has('employee_password_hash')
                    || ! hash_equals($request->session()->get('employee_password_hash'), hash('sha256', $employee->password)));
            if (! $employee->is_active || $changed) {
                Auth::guard('web')->logout();
                if ($request->hasSession()) {
                    $request->session()->invalidate();
                    $request->session()->regenerateToken();
                }
                if ($request->expectsJson()) abort(401, 'Silakan login kembali.');
                return redirect('/login');
            }
        }
        
        return $next($request);
    }
}
