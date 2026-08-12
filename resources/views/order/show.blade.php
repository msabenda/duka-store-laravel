@extends('layouts.app')

@section('title', 'Order ' . $order['reference'])

@section('content')
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-green-100 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-xl font-bold text-gray-900">Order Details</h1>
                <span class="text-sm font-mono text-gray-400 bg-green-50 px-3 py-1 rounded-lg">{{ $order['reference'] }}</span>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-5 mb-6">
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1.5 font-medium">Status</p>
                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold capitalize
                        @if($order['status'] === 'completed') bg-green-100 text-green-700
                        @elseif($order['status'] === 'failed') bg-red-100 text-red-700
                        @elseif($order['status'] === 'cancelled') bg-gray-100 text-gray-700
                        @else bg-yellow-100 text-yellow-700
                        @endif">
                        {{ $order['status'] }}
                    </span>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1.5 font-medium">Method</p>
                    <p class="text-gray-900 font-medium capitalize text-sm">{{ str_replace('_', ' ', $order['payment_method']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1.5 font-medium">Amount</p>
                    <p class="text-gray-900 font-bold text-lg">TZS {{ number_format($order['amount']) }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1.5 font-medium">Date</p>
                    <p class="text-gray-900 font-medium text-sm">{{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y H:i') }}</p>
                </div>
                @if($order['snippe_reference'] ?? null)
                <div>
                    <p class="text-xs text-gray-500 uppercase tracking-wider mb-1.5 font-medium">Snippe Ref</p>
                    <p class="text-gray-900 font-mono text-xs break-all">{{ $order['snippe_reference'] }}</p>
                </div>
                @endif
            </div>

            <div class="border-t border-green-100 pt-5 mb-5">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-3 font-medium">Customer</p>
                <p class="text-gray-900 font-semibold">{{ $order['customer_name'] }}</p>
                <p class="text-sm text-gray-500">{{ $order['customer_email'] }}</p>
                <p class="text-sm text-gray-500">{{ $order['customer_phone'] }}</p>
            </div>

            <div class="border-t border-green-100 pt-5">
                <p class="text-xs text-gray-500 uppercase tracking-wider mb-3 font-medium">Items</p>
                <div class="space-y-3">
                    @foreach($order['items'] as $item)
                        <div class="flex items-center justify-between text-sm bg-green-50 rounded-xl px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 border border-green-100 bg-green-50">
                                    <img src="{{ $item['image'] ?? '/images/img1.jpg' }}"
                                         alt="{{ $item['product_name'] }}"
                                         class="w-full h-full object-cover">
                                </div>
                                <div>
                                    <span class="text-gray-900 font-medium">{{ $item['product_name'] }}</span>
                                    <span class="text-gray-400 ml-1">x{{ $item['quantity'] }}</span>
                                </div>
                            </div>
                            <span class="text-gray-900 font-medium">TZS {{ number_format($item['subtotal']) }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-green-100">
                    <span class="font-semibold text-gray-900">Total</span>
                    <span class="font-bold text-xl text-gray-900">TZS {{ number_format($order['amount']) }}</span>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ route('home') }}#collection" class="text-sm text-gray-500 hover:text-gray-900 transition-colors font-medium">
                Continue Shopping &rarr;
            </a>
        </div>
    </div>
@endsection
