<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Services\AdminService;
use App\Models\AdminModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OTPHP\TOTP;

class LoginController extends Controller
{
    private $adminService;

    public function __construct(AdminService $adminService)
    {
        $this->adminService = $adminService;
    }

    public function redirectLoginView()
    {
        return view('admin.auth.login');
    }

    public function login(Request $request)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'otp' => 'required|numeric|digits:6',
        ];
        $request->validate($rules);

        $admin = AdminModel::query()->where(['email' => $request->input('email')])->first();
        if (empty($admin)) {
            throw ValidationException::withMessages(['email' => '账号不存在']);
        }
        if (!Hash::check($request->input('password'), $admin->password)) {
            throw ValidationException::withMessages(['password' => '密码错误']);
        }
        $totp = TOTP::create($admin->totp_secret);
        if (!$totp->verify($request->input('otp'), null, 1)) {
            throw ValidationException::withMessages(['otp' => '二次校验失败']);
        }
        Auth::guard('admin')->login($admin, true);

        return redirect()->route('admin.dashboard');
    }

    public function logout()
    {
        Auth::guard('admin')->logout();

        return redirect()->route('admin.login');
    }
}
