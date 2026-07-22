@extends('layouts.app')

@section('title', 'Order Confirmed')

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="bg-white rounded-2xl p-8 border border-green-100 shadow-sm text-center">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 mb-2">Order Confirmed</h1>
            <p class="text-gray-500 mb-8">Thanks for your purchase. Your order has been placed.</p>

            <div class="bg-green-50 rounded-2xl p-6 mb-8 text-left border border-green-100">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-gray-500">Order Reference</span>
                    <span class="font-mono font-semibold text-gray-900 text-sm">{{ $order['reference'] }}</span>
                </div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-sm text-gray-500">Total</span>
                    <span class="font-bold text-gray-900">TZS {{ number_format($order['amount']) }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Status</span>
                    <span class="text-sm font-semibold capitalize text-yellow-700 bg-yellow-100 px-3 py-1 rounded-full">{{ $order['status'] }}</span>
                </div>

                {{-- Show first order item image --}}
                @if(isset($order['items'][0]))
                <div class="border-t border-green-100 mt-4 pt-4 flex items-center gap-3">
                    <div class="w-12 h-12 rounded-lg overflow-hidden flex-shrink-0 border border-green-100">
                        <img src="{{ $order['items'][0]['image'] ?? '/images/tee.svg' }}"
                             alt="{{ $order['items'][0]['product_name'] }}"
                             class="w-full h-full object-cover">
                    </div>
                    <div class="text-sm">
                        <p class="text-gray-900 font-medium">{{ $order['items'][0]['product_name'] }}</p>
                        <p class="text-gray-500">{{ count($order['items']) }} {{ count($order['items']) === 1 ? 'item' : 'items' }}</p>
                    </div>
                </div>
                @endif
            </div>

            @if(session('info'))
                <p class="text-sm text-green-800 bg-green-50 rounded-xl px-5 py-3 mb-6 border border-green-100">{{ session('info') }}</p>
            @endif

            <a href="{{ route('dashboard.index') }}"
               class="inline-block bg-gray-900 text-white px-8 py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10">
                View Dashboard
            </a>
        </div>
    </div>
@endsection
