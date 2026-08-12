{{-- 🎙️ DA NOTE — YOUR CUSTOM CHECKOUT (you built this page — reused in later sessions) --}}
{{-- Customer details + payment method selection. Posts to /checkout/mobile, /checkout/card, /checkout/qr. --}}
{{-- Snippe's hosted checkout (the payment_url redirect) is the alternative to this page. --}}
{{-- ⚠️ DISABLED FOR NOW - hosted Snippe checkout only. Route + controller commented out in routes/web.php and CheckoutController.php; re-enable to use this page again. --}}
@extends('layouts.app')

@section('title', 'Checkout')

@section('content')
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Steps indicator --}}
        <div class="flex items-center justify-center gap-2 sm:gap-4 mb-10 text-sm">
            <span class="text-gray-500 flex items-center gap-1.5">
                <span class="w-6 h-6 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                Cart
            </span>
            <span class="text-gray-300 w-4 h-px bg-gray-300"></span>
            <span class="text-gray-900 font-semibold flex items-center gap-1.5">
                <span class="w-6 h-6 bg-gray-900 text-white rounded-full flex items-center justify-center text-xs font-bold">2</span>
                Checkout
            </span>
            <span class="text-gray-300 w-4 h-px bg-gray-300"></span>
            <span class="text-gray-400 flex items-center gap-1.5">
                <span class="w-6 h-6 bg-gray-200 text-gray-500 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                Confirmation
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-8">
            {{-- Left: Form --}}
            <div class="lg:col-span-3 space-y-6">
                <form id="checkout-form" method="POST" action="">
                    @csrf
                    <input type="hidden" name="payment_method" id="payment_method" value="">

                    {{-- Customer Details --}}
                    <div class="bg-white rounded-xl p-6 sm:p-8 border border-green-100 shadow-sm">
                        <h2 class="font-semibold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Customer Details
                        </h2>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label for="customer_name" class="block text-sm font-medium text-gray-900 mb-1.5">Full Name</label>
                                <input type="text" name="customer_name" id="customer_name"
                                       value="{{ old('customer_name', $user ? trim($user['firstname'] . ' ' . $user['lastname']) : '') }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                                       placeholder="Your full name" required>
                                @error('customer_name')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="customer_email" class="block text-sm font-medium text-gray-900 mb-1.5">Email</label>
                                <input type="email" name="customer_email" id="customer_email"
                                       value="{{ old('customer_email', $user ? $user['email'] : '') }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                                       placeholder="your@email.com" required>
                                @error('customer_email')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="customer_phone" class="block text-sm font-medium text-gray-900 mb-1.5">Phone Number</label>
                                <input type="tel" name="customer_phone" id="customer_phone"
                                       value="{{ old('customer_phone') }}"
                                       class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-white text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-600 focus:border-transparent transition text-sm"
                                       placeholder="+255 7XX XXX XXX" required>
                                @error('customer_phone')
                                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Payment Methods --}}
                    <div class="bg-white rounded-xl p-6 sm:p-8 border border-green-100 shadow-sm">
                        <h2 class="font-semibold text-gray-900 mb-6 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                            Payment Method
                        </h2>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <button type="button" onclick="submitPayment('mobile')"
                                    class="border-2 border-green-100 bg-green-50 rounded-xl p-5 text-center hover:border-green-600 hover:bg-green-100 transition-all cursor-pointer group text-left">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-3 shadow-sm group-hover:shadow transition-shadow">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="font-semibold text-gray-900 text-sm mb-1">Mobile Money</div>
                                <p class="text-xs text-gray-500 leading-relaxed">Pay instantly via M-Pesa, Tigo Pesa or Airtel Money</p>
                            </button>

                            <button type="button" onclick="submitPayment('card')"
                                    class="border-2 border-green-100 bg-green-50 rounded-xl p-5 text-center hover:border-green-600 hover:bg-green-100 transition-all cursor-pointer group text-left">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-3 shadow-sm group-hover:shadow transition-shadow">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                </div>
                                <div class="font-semibold text-gray-900 text-sm mb-1">Card</div>
                                <p class="text-xs text-gray-500 leading-relaxed">Visa, Mastercard and other cards</p>
                            </button>

                            <button type="button" onclick="submitPayment('qr')"
                                    class="border-2 border-green-100 bg-green-50 rounded-xl p-5 text-center hover:border-green-600 hover:bg-green-100 transition-all cursor-pointer group text-left">
                                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center mb-3 shadow-sm group-hover:shadow transition-shadow">
                                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                    </svg>
                                </div>
                                <div class="font-semibold text-gray-900 text-sm mb-1">QR Code</div>
                                <p class="text-xs text-gray-500 leading-relaxed">Scan with your banking app</p>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Right: Order Summary --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl p-6 sm:p-8 border border-green-100 shadow-sm sticky top-24">
                    <h2 class="font-semibold text-gray-900 mb-5">Order Summary</h2>

                    <div class="space-y-4">
                        @foreach($items as $item)
                            <div class="flex items-start gap-3">
                                <div class="w-12 h-12 rounded-lg flex-shrink-0 overflow-hidden border border-green-100 bg-green-50">
                                    <img src="{{ $item['product']['image'] }}"
                                         alt="{{ $item['product']['name'] }}"
                                         class="w-full h-full object-cover">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900">{{ $item['product']['name'] }}</p>
                                    <p class="text-xs text-gray-400">x {{ $item['quantity'] }}</p>
                                </div>
                                <span class="text-sm font-medium text-gray-900 whitespace-nowrap">TZS {{ number_format($item['subtotal']) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-t border-green-100 mt-5 pt-5 space-y-2.5">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Subtotal</span>
                            <span class="text-gray-900">TZS {{ number_format($total) }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">Shipping</span>
                            <span class="text-green-700 font-medium text-xs">Free</span>
                        </div>
                        <div class="border-t border-green-100 pt-2.5 flex items-center justify-between font-bold">
                            <span class="text-gray-900">Total</span>
                            <span class="text-xl text-gray-900">TZS {{ number_format($total) }}</span>
                        </div>
                    </div>

                    <div class="mt-6 bg-green-50 rounded-xl p-4 border border-green-100">
                        <div class="flex items-start gap-3 text-xs text-gray-600">
                            <svg class="w-4 h-4 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                            <div>
                                <p class="font-medium text-gray-900 mb-0.5">Secure payment</p>
                                <p class="text-gray-500">Your information is protected by Snippe. We never store your card details.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        function submitPayment(method) {
            var form = document.getElementById('checkout-form');
            var routes = {
                mobile: '{{ route('checkout.mobile') }}',
                card: '{{ route('checkout.card') }}',
                qr: '{{ route('checkout.qr') }}'
            };
            form.action = routes[method];
            form.submit();
        }
    </script>
@endsection
