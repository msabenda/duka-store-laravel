<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Duka Store') - Duka Store</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 antialiased" style="font-family: 'Inter', sans-serif;">

    {{-- Header --}}
    <header class="bg-white border-b border-green-100 sticky top-0 z-50 shadow-[0_1px_3px_rgba(0,0,0,0.04)]">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="{{ route('home') }}" class="flex items-center gap-2 group">
                    <div class="w-8 h-8 bg-gray-900 rounded-lg flex items-center justify-center group-hover:bg-gray-700 transition-colors">
                        <span class="text-white text-xs font-bold">D</span>
                    </div>
                    <span class="text-lg font-bold tracking-tight text-gray-900">Duka Store</span>
                </a>

                <div class="flex items-center gap-1 sm:gap-3">
                    <a href="{{ route('home') }}"
                       class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 rounded-lg hover:bg-green-50 transition-colors">
                        Home
                    </a>

                    <a href="{{ route('cart.index') }}"
                       class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 rounded-lg hover:bg-green-50 transition-colors relative">
                        Cart
                        @if(isset($cartCount) && $cartCount > 0)
                            <span class="absolute -top-0.5 -right-0.5 bg-gray-900 text-white text-[10px] rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 font-medium">
                                {{ $cartCount }}
                            </span>
                        @endif
                    </a>

                    @php $authUser = session('duka_user'); @endphp
                    @if($authUser)
                        <a href="{{ route('dashboard.index') }}"
                           class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 rounded-lg hover:bg-green-50 transition-colors">
                            Dashboard
                        </a>
                        <a href="{{ route('auth.logout') }}"
                           class="px-3 py-2 text-sm text-gray-500 hover:text-red-600 rounded-lg hover:bg-red-50 transition-colors">
                            Logout
                        </a>
                    @else
                        <a href="{{ route('auth.login') }}"
                           class="px-3 py-2 text-sm text-gray-600 hover:text-gray-900 rounded-lg hover:bg-green-50 transition-colors">
                            Login
                        </a>
                        <a href="{{ route('auth.register') }}"
                           class="bg-gray-900 text-white text-sm px-4 py-2 rounded-lg hover:bg-gray-800 transition-colors font-medium">
                            Register
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </header>

    {{-- Flash Messages --}}
    @if(session('success') || session('error') || session('info'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-xl flex items-start gap-3 mb-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm">{{ session('success') }}</span>
            </div>
        @endif

        @if(session('error'))
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl flex items-start gap-3 mb-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm">{{ session('error') }}</span>
            </div>
        @endif

        @if(session('info'))
            <div class="bg-blue-50 border border-blue-200 text-blue-700 px-4 py-3 rounded-xl flex items-start gap-3 mb-3">
                <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="text-sm">{{ session('info') }}</span>
            </div>
        @endif
    </div>
    @endif

    <main class="min-h-[60vh]">
        @yield('content')
    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-gray-400 mt-24">
        <div class="border-b border-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div>
                        <p class="text-white font-semibold">Stay in the loop</p>
                        <p class="text-sm text-gray-500">New drops, restocks and offers straight to your inbox.</p>
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <input type="email" placeholder="your@email.com"
                               class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-green-600 flex-1 sm:flex-initial">
                        <button class="bg-green-700 hover:bg-green-600 text-white px-5 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap">
                            Subscribe
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 bg-white rounded-lg flex items-center justify-center">
                            <span class="text-gray-900 text-xs font-bold">D</span>
                        </div>
                        <span class="text-lg font-bold text-white">Duka Store</span>
                    </div>
                    <p class="text-sm text-gray-500 leading-relaxed">
                        Fashion essentials at one price. No fuss, no hidden costs.
                    </p>
                </div>

                <div>
                    <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-widest mb-4">Shop</h3>
                    <ul class="space-y-2.5">
                        <li><a href="{{ route('home') }}#collection" class="text-sm text-gray-500 hover:text-white transition-colors">Tops</a></li>
                        <li><a href="{{ route('home') }}#collection" class="text-sm text-gray-500 hover:text-white transition-colors">Bottoms</a></li>
                        <li><a href="{{ route('home') }}#collection" class="text-sm text-gray-500 hover:text-white transition-colors">Shoes</a></li>
                        <li><a href="{{ route('home') }}#collection" class="text-sm text-gray-500 hover:text-white transition-colors">Accessories</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-widest mb-4">Account</h3>
                    <ul class="space-y-2.5">
                        @if(session('duka_user'))
                            <li><a href="{{ route('dashboard.index') }}" class="text-sm text-gray-500 hover:text-white transition-colors">Dashboard</a></li>
                            <li><a href="{{ route('auth.logout') }}" class="text-sm text-gray-500 hover:text-white transition-colors">Logout</a></li>
                        @else
                            <li><a href="{{ route('auth.login') }}" class="text-sm text-gray-500 hover:text-white transition-colors">Login</a></li>
                            <li><a href="{{ route('auth.register') }}" class="text-sm text-gray-500 hover:text-white transition-colors">Register</a></li>
                        @endif
                        <li><a href="{{ route('cart.index') }}" class="text-sm text-gray-500 hover:text-white transition-colors">Cart</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-xs font-semibold text-gray-300 uppercase tracking-widest mb-4">Payment</h3>
                    <ul class="space-y-2.5">
                        <li class="text-sm text-gray-500">Mobile Money</li>
                        <li class="text-sm text-gray-500">Card Payments</li>
                        <li class="text-sm text-gray-500">QR Code</li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-10 pt-8 flex flex-col sm:flex-row items-center justify-between gap-3">
                <p class="text-sm text-gray-600">
                    &copy; {{ date('Y') }} Duka Store. All rights reserved.
                </p>
                <div class="flex items-center gap-4 text-xs text-gray-600">
                    <span>Privacy</span>
                    <span>Terms</span>
                    <span>Help</span>
                </div>
            </div>
        </div>
    </footer>

    @yield('scripts')
</body>
</html>
