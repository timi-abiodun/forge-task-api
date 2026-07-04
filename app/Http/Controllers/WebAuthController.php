<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Services\RegisterService;
use App\Services\LoginService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebAuthController extends Controller
{
    public function __construct(
        protected RegisterService $registerService,
        protected LoginService $loginService
    ) {}

    public function showLogin()
    {
        return view('auth.login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        // Reuses the same service the API uses. Note: this creates a
        // Sanctum token that this session-based flow never uses — an
        // accepted tradeoff for demo scope, not a bug. Revisit if this
        // becomes more than a portfolio demo.
        $result = $this->loginService->login($data);

        Auth::login($result['user']);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function register(RegisterRequest $request)
    {
        $data = $request->validated();

        $result = $this->registerService->register($data);

        Auth::login($result['user']);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}