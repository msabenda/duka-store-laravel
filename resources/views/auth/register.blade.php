@extends('layouts.app')

@section('title', 'Register')

@section('content')
    <div class="min-h-[70vh] flex items-center justify-center px-4 py-12">
        <div class="w-full max-w-md">
            <div class="bg-white rounded-2xl p-8 border border-green-100 shadow-sm">
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-green-50 rounded-xl flex items-center justify-center mx-auto mb-4">
                        <svg class="w-6 h-6 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-1">Create Account</h1>
                    <p class="text-gray-500 text-sm">Join us and start shopping.</p>
                </div>

                <form method="POST" action="{{ route('auth.register') }}">
                    @csrf

                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="firstname" class="block text-sm font-medium text-gray-900 mb-1.5">First Name</label>
                            <input type="text" name="firstname" id="firstname" value="{{ old('firstname') }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                                   placeholder="John" required>
                            @error('firstname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="lastname" class="block text-sm font-medium text-gray-900 mb-1.5">Last Name</label>
                            <input type="text" name="lastname" id="lastname" value="{{ old('lastname') }}"
                                   class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                                   placeholder="Doe" required>
                            @error('lastname')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-sm font-medium text-gray-900 mb-1.5">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                               placeholder="your@email.com" required>
                        @error('email')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-900 mb-1.5">Password</label>
                        <input type="password" name="password" id="password"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                               placeholder="At least 8 characters" required>
                        @error('password')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-sm font-medium text-gray-900 mb-1.5">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation"
                               class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                               placeholder="Repeat your password" required>
                    </div>

                    <button type="submit"
                            class="w-full bg-gray-900 text-white py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors text-sm shadow-lg shadow-gray-900/10">
                        Create Account
                    </button>
                </form>

                <p class="text-center text-sm text-gray-500 mt-6">
                    Already have an account?
                    <a href="{{ route('auth.login') }}" class="text-green-700 font-medium hover:text-green-800 transition-colors">Sign in</a>
                </p>
            </div>
        </div>
    </div>
@endsection
