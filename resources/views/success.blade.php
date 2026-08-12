@extends('layouts.app')

@section('title', $order ? (($order['status'] ?? '') === 'completed' ? 'Order Confirmed' : 'Order ' . ucfirst($order['status'] ?? '')) : 'Order Not Found')

@section('content')
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        <div class="bg-white rounded-2xl p-8 border border-green-100 shadow-sm text-center">
            @if(!$order)
                <h1 class="text-2xl font-bold text-gray-900 mb-2">Order Not Found</h1>
                <p class="text-gray-500 mb-8">We could not find that order. It may have expired or the link is incomplete.</p>
                <a href="/" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10">
                    Back to the Store
                </a>
            @else
                @if(($order['status'] ?? '') === 'completed')
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Order Confirmed</h1>
                    <p class="text-gray-500 mb-8">Thanks for your purchase. Snippe confirmed your payment.</p>
                @elseif(in_array($order['status'] ?? '', ['failed', 'cancelled', 'expired']))
                    <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-red-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Order {{ ucfirst($order['status']) }}</h1>
                    <p class="text-gray-500 mb-8">Your payment was not completed. You can try again from the store.</p>
                @else
                    <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-5">
                        <svg class="w-8 h-8 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Pending</h1>
                    <p class="text-gray-500 mb-8">We are waiting for Snippe to confirm your payment. This page updates automatically when the webhook arrives - no action needed.</p>
                @endif

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
                        <span class="text-sm font-semibold capitalize px-3 py-1 rounded-full {{ ($order['status'] ?? '') === 'completed' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">{{ $order['status'] }}</span>
                    </div>
                    @if(!empty($order['snippe_reference']))
                    <div class="flex items-center justify-between mt-3">
                        <span class="text-sm text-gray-500">Snippe Reference</span>
                        <span class="font-mono font-semibold text-gray-900 text-sm" style="word-break:break-all">{{ $order['snippe_reference'] }}</span>
                    </div>
                    @endif
                </div>

                <div class="flex gap-3 justify-center">
                    <a href="{{ route('dashboard.index') }}" class="inline-block bg-gray-900 text-white px-8 py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10">
                        View Dashboard
                    </a>
                    <a href="/" class="inline-block bg-green-50 text-teal-700 px-8 py-3 rounded-xl font-semibold hover:bg-green-100 transition-colors">
                        Continue Shopping
                    </a>
                </div>

                @if(($order['status'] ?? '') === 'pending')
                <script>
                    // Poll the server for the real status; reload when the webhook flips it.
                    (function () {
                        var ref = '{{ $order['reference'] }}';
                        var current = '{{ $order['status'] }}';
                        setInterval(function () {
                            fetch('{{ url('/success/status') }}?ref=' + encodeURIComponent(ref))
                                .then(function (r) { return r.json(); })
                                .then(function (d) {
                                    if (d.status && d.status !== current) window.location.reload();
                                })
                                .catch(function () { /* keep polling */ });
                        }, 3000);
                    })();
                </script>
                @endif
            @endif
        </div>
    </div>
@endsection
