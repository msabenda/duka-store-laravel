@extends('layouts.app')

@section('title', 'Login')

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl p-8 border border-green-100 shadow-sm">
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">Welcome back</h1>
                    <p class="text-gray-500 text-sm">Sign in to your account to continue.</p>
                </div>

                <form method="POST" action="{{ route('auth.login') }}">
                    @csrf

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-900 mb-1.5">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                               placeholder="your@email.com" required autofocus>
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-sm font-medium text-gray-900 mb-1.5">Password</label>
                        <input type="password" name="password" id="password"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                               placeholder="Your password" required>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full bg-gray-900 text-white py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors text-sm shadow-lg shadow-gray-900/10">
                        Sign In
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Don't have an account?
                    <a href="{{ route('auth.register') }}" class="text-green-700 font-medium hover:text-green-800 transition-colors">Create one</a>
                </p>
            </div>
        </div>
    </div>
@endsection
