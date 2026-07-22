@extends('layouts.app')

@section('title', 'Home')

@section('content')
    {{-- Hero --}}
    <div class="bg-green-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row items-center gap-10 py-16 lg:py-20">
                <div class="flex-1 text-center lg:text-left">
                    <h1 class="text-4xl lg:text-6xl font-extrabold text-gray-900 tracking-tight leading-tight">
                        Good style.<br>
                        <span class="text-green-700">One price.</span>
                    </h1>
                    <p class="text-gray-500 mt-4 text-lg leading-relaxed max-w-lg mx-auto lg:mx-0">
                        Every item in this store costs exactly 500 TZS. No discounts, no tricks.
                        Just good clothes at a fair price.
                    </p>
                    <div class="flex items-center gap-3 mt-8 justify-center lg:justify-start">
                        <a href="#collection"
                           class="bg-gray-900 text-white px-7 py-3 rounded-xl font-semibold hover:bg-gray-800 transition-colors shadow-lg shadow-gray-900/10">
                            Shop Now
                        </a>
                        <a href="{{ route('cart.index') }}"
                           class="text-gray-600 hover:text-gray-900 font-medium px-5 py-3 transition-colors">
                            View Cart &rarr;
                        </a>
                    </div>
                </div>
                <div class="flex-1 flex justify-center">
                    <div class="grid grid-cols-2 gap-3 w-full max-w-sm">
                        <img src="/images/tee.svg" alt="Cotton Tee"
                             class="rounded-2xl aspect-[3/4] object-cover w-full bg-green-50">
                        <img src="/images/dress.svg" alt="Linen Dress"
                             class="rounded-2xl aspect-[3/4] object-cover w-full mt-6 bg-green-50">
                        <img src="/images/belt.svg" alt="Leather Belt"
                             class="rounded-2xl aspect-[3/4] object-cover w-full -mt-3 bg-green-50">
                        <img src="/images/sneakers.svg" alt="Leather Sneakers"
                             class="rounded-2xl aspect-[3/4] object-cover w-full mt-3 bg-green-50">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats strip --}}
    <div class="border-b border-green-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <div>
                    <p class="text-2xl font-bold text-gray-900">500</p>
                    <p class="text-sm text-gray-500">TZS per item</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">8</p>
                    <p class="text-sm text-gray-500">different styles</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">Fast</p>
                    <p class="text-sm text-gray-500">checkout</p>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">Secure</p>
                    <p class="text-sm text-gray-500">payments via Snippe</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Collection --}}
    <div id="collection" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex items-end justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">All Products</h2>
                <p class="text-gray-500 mt-1 text-sm">Pick whatever you want. Everything is 500 TZS.</p>
            </div>
            <span class="text-xs text-gray-400 hidden sm:block">{{ count($products) }} items</span>
        </div>

        @if(empty($products))
            <div class="bg-green-50 rounded-2xl p-16 text-center border border-green-100">
                <p class="text-gray-500">No products right now. Check back later.</p>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                @foreach($products as $product)
                    <div class="bg-white rounded-2xl overflow-hidden border border-green-100 hover:shadow-lg hover:shadow-green-100/40 transition-all group">
                        <a href="{{ route('cart.add', $product['id']) }}">
                            <div class="aspect-[4/5] overflow-hidden bg-green-50">
                                <img src="{{ $product['image'] }}"
                                     alt="{{ $product['name'] }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                            </div>
                        </a>
                        <div class="p-4">
                            <div class="mb-1.5">
                                <span class="inline-block text-[10px] font-semibold text-green-700 bg-green-50 border border-green-100 px-2 py-0.5 rounded-full">
                                    {{ $product['category'] }}
                                </span>
                            </div>
                            <h3 class="font-semibold text-gray-900 text-sm leading-tight mb-1">{{ $product['name'] }}</h3>
                            <p class="text-xs text-gray-400 mb-3 line-clamp-2 leading-relaxed">{{ $product['description'] }}</p>
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-gray-900">TZS 500</span>
                                <a href="{{ route('cart.add', $product['id']) }}"
                                   class="bg-gray-900 text-white text-xs px-3.5 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium">
                                    + Add
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection
