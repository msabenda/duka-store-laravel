@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">Welcome, {{ $user['firstname'] }}</h1>
            <p class="text-gray-500 text-sm mt-1">Your order history and account info.</p>
        </div>

        @if(empty($orders))
            <div class="bg-green-50 rounded-2xl p-16 text-center border border-green-100">
                <div class="w-16 h-16 bg-white rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                    <svg class="w-7 h-7 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                </div>
                <p class="text-gray-500 font-medium mb-1">No orders yet</p>
                <p class="text-sm text-gray-400 mb-6">Start shopping to see your orders here.</p>
                <a href="{{ route('home') }}#collection"
                   class="inline-block bg-gray-900 text-white px-6 py-3 rounded-xl font-medium hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10">
                    Shop Now
                </a>
            </div>
        @else
            <div class="bg-white rounded-2xl border border-green-100 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-green-100 bg-green-50">
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="text-left px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="text-right px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr class="border-b border-green-50 last:border-b-0 hover:bg-green-50/60 transition-colors">
                                    <td class="px-6 py-4 font-mono text-sm text-gray-900">{{ $order['reference'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-500">{{ \Carbon\Carbon::parse($order['created_at'])->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 text-sm font-semibold text-gray-900">TZS {{ number_format($order['amount']) }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-block px-2.5 py-1 rounded-full text-xs font-semibold capitalize
                                            @if($order['status'] === 'completed') bg-green-100 text-green-700
                                            @elseif($order['status'] === 'failed') bg-red-100 text-red-700
                                            @elseif($order['status'] === 'cancelled') bg-gray-100 text-gray-700
                                            @else bg-yellow-100 text-yellow-700
                                            @endif">
                                            {{ $order['status'] }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('dashboard.order', $order['reference']) }}"
                                           class="text-sm font-medium text-green-700 hover:text-green-800 transition-colors">
                                            View &rarr;
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
@endsection
