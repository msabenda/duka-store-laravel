@extends('layouts.app')

@section('title', 'Cart')

@section('content')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Cart</h1>
                <p class="text-gray-500 text-sm mt-1">{{ count($items) }} {{ count($items) === 1 ? 'item' : 'items' }}</p>
            </div>
            <a href="{{ route('home') }}#collection"
               class="text-sm text-green-700 hover:text-green-800 font-medium transition-colors">
                &larr; Continue shopping
            </a>
        </div>

        @if(empty($items))
            <div class="bg-green-50 rounded-2xl p-16 text-center border border-green-100">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                    </svg>
                </div>
                <p class="text-gray-500 mb-1 font-medium">Your cart is empty</p>
                <p class="text-sm text-gray-400 mb-6">You haven't added anything yet.</p>
                <a href="{{ route('home') }}#collection"
                   class="inline-block bg-gray-900 text-white px-6 py-3 rounded-xl font-medium hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10">
                    Browse Products
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                {{-- Cart Items --}}
                <div class="lg:col-span-2 space-y-3">
                    @foreach($items as $index => $item)
                        <div class="bg-white rounded-xl p-4 sm:p-5 border border-green-100 hover:shadow-md hover:shadow-green-100/20 transition-shadow flex items-start gap-4">
                            <div class="w-20 h-20 sm:w-24 sm:h-24 rounded-xl flex-shrink-0 overflow-hidden border border-green-100 bg-green-50">
                                <img src="{{ $item['product']['image'] }}"
                                     alt="{{ $item['product']['name'] }}"
                                     class="w-full h-full object-cover">
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-2">
                                    <div>
                                        <h3 class="font-semibold text-gray-900 text-sm sm:text-base">{{ $item['product']['name'] }}</h3>
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $item['product']['category'] }}</p>
                                    </div>
                                    <span class="font-bold text-gray-900 whitespace-nowrap">TZS {{ number_format($item['subtotal']) }}</span>
                                </div>

                                <div class="flex items-center justify-between mt-4">
                                    <div class="flex items-center border border-gray-200 rounded-lg overflow-hidden">
                                        <span class="px-3 py-1.5 text-sm text-gray-500 bg-gray-50 border-r border-gray-200">
                                            {{ $item['quantity'] }}
                                        </span>
                                        <span class="px-2 text-xs text-gray-400">x TZS {{ number_format($item['product']['price']) }}</span>
                                    </div>
                                    <a href="{{ route('cart.remove', $index) }}"
                                       class="text-xs text-gray-400 hover:text-red-500 transition-colors flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Remove
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Order Summary Sidebar --}}
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl p-6 border border-green-100 shadow-sm sticky top-24">
                        <h3 class="font-semibold text-gray-900 mb-4">Summary</h3>

                        <div class="space-y-3 text-sm mb-4">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Items ({{ count($items) }})</span>
                                <span class="text-gray-900">TZS {{ number_format($total) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Shipping</span>
                                <span class="text-green-700 font-medium">Free</span>
                            </div>
                            <div class="border-t border-green-100 pt-3 flex items-center justify-between font-semibold">
                                <span class="text-gray-900">Total</span>
                                <span class="text-lg font-bold text-gray-900">TZS {{ number_format($total) }}</span>
                            </div>
                        </div>

                        <a href="{{ route('checkout.index') }}"
                           class="block w-full text-center bg-gray-900 text-white py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10 text-sm">
                            Checkout
                        </a>

                        <p class="text-xs text-gray-400 text-center mt-3 flex items-center justify-center gap-1">
                            <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            Secure checkout powered by Snippe
                        </p>

                        <div class="border-t border-green-100 mt-5 pt-5">
                            <label class="text-xs font-medium text-gray-500 uppercase tracking-wider block mb-2">Coupon</label>
                            <div class="flex gap-2">
                                <input type="text" placeholder="Enter code"
                                       class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-xs focus:outline-none focus:border-green-600">
                                <button class="bg-green-50 border border-green-200 text-green-700 text-xs px-3 py-2 rounded-lg font-medium hover:bg-green-100 transition-colors whitespace-nowrap">
                                    Apply
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
