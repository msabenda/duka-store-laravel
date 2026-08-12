<?php

namespace App\Http\Controllers;

use App\Services\StorageService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    protected StorageService $storage;

    public function __construct(StorageService $storage)
    {
        $this->storage = $storage;
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = $this->storage->getUserByEmail($validated['email']);

        if (!$user || !password_verify($validated['password'], $user['password'])) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput();
        }

        session()->put('duka_user', [
            'id' => $user['id'],
            'firstname' => $user['firstname'],
            'lastname' => $user['lastname'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? '',
        ]);

        return redirect()->intended('/');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:20',
            'password' => 'required|min:8|confirmed',
        ]);

        // Check if email already exists
        $existing = $this->storage->getUserByEmail($validated['email']);
        if ($existing) {
            return back()->withErrors(['email' => 'An account with this email already exists.'])->withInput();
        }

        $userData = [
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'password' => password_hash($validated['password'], PASSWORD_BCRYPT),
            'created_at' => now()->toIso8601String(),
        ];

        $this->storage->saveUser($userData);

        return redirect()->route('auth.login')->with('success', 'Account created successfully! Please sign in to continue.');
    }

    public function logout()
    {
        session()->forget('duka_user');
        return redirect('/');
    }
}
