@extends('layouts.app')

@section('title', 'Error')

@section('content')
    <div class="min-h-[60vh] flex items-center justify-center px-4 py-12">
        <div class="bg-white rounded-2xl p-8 border border-green-100 shadow-sm text-center max-w-md w-full">
            <div class="w-14 h-14 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Something went wrong</h1>
            <p class="text-gray-500 mb-6">{{ $message ?? 'We couldn\'t process your request. Please try again.' }}</p>
            <a href="{{ route('home') }}"
               class="inline-block bg-gray-900 text-white px-6 py-3 rounded-xl font-medium hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10">
                Go Home
            </a>
        </div>
    </div>
@endsection
