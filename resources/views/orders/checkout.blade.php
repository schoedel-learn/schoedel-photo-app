<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">Checkout</h1>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('orders.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                @csrf

                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Billing Information</h2>

                        <div class="space-y-4">
                            <div>
                                <label for="billing_address_name" class="block text-sm font-medium text-gray-700 mb-1">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="billing_address_name" name="billing_address[name]" required
                                    value="{{ old('billing_address.name', Auth::guard('client')->user()->name ?? '') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="billing_address_email" class="block text-sm font-medium text-gray-700 mb-1">
                                    Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="billing_address_email" name="billing_address[email]" required
                                    value="{{ old('billing_address.email', Auth::guard('client')->user()->email ?? '') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div>
                                <label for="billing_address_street" class="block text-sm font-medium text-gray-700 mb-1">
                                    Street Address <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="billing_address_street" name="billing_address[street]" required
                                    value="{{ old('billing_address.street') }}"
                                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="billing_address_city" class="block text-sm font-medium text-gray-700 mb-1">
                                        City <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="billing_address_city" name="billing_address[city]" required
                                        value="{{ old('billing_address.city') }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label for="billing_address_state" class="block text-sm font-medium text-gray-700 mb-1">
                                        State <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="billing_address_state" name="billing_address[state]" required
                                        value="{{ old('billing_address.state') }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="billing_address_zip" class="block text-sm font-medium text-gray-700 mb-1">
                                        ZIP Code <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="billing_address_zip" name="billing_address[zip]" required
                                        value="{{ old('billing_address.zip') }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>

                                <div>
                                    <label for="billing_address_country" class="block text-sm font-medium text-gray-700 mb-1">
                                        Country <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="billing_address_country" name="billing_address[country]" required
                                        value="{{ old('billing_address.country', 'US') }}"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Notes (Optional)</h2>
                        <textarea name="notes" rows="3" 
                                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Any special instructions or notes for your order...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-4">
                        <label class="flex items-start">
                            <input type="checkbox" name="terms" value="1" required class="mt-1 mr-3">
                            <span class="text-sm text-gray-700">
                                I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-800">Terms and Conditions</a> 
                                and <a href="#" class="text-indigo-600 hover:text-indigo-800">Privacy Policy</a>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

                        <div class="space-y-2 mb-4 text-sm">
                            <div class="text-gray-600 mb-3">
                                {{ $cart['item_count'] }} {{ Str::plural('item', $cart['item_count']) }}
                            </div>

                            @foreach($cart['items'] as $item)
                                <div class="flex justify-between pb-2 border-b border-gray-100">
                                    <span class="text-gray-700">{{ $item['photo_filename'] }}</span>
                                    <span class="font-medium">${{ number_format($item['total_price'], 2) }}</span>
                                </div>
                            @endforeach
                        </div>

                        <div class="space-y-2 pt-4 border-t">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-medium">${{ number_format($cart['subtotal'], 2) }}</span>
                            </div>

                            @if($cart['discount'])
                                <div class="flex justify-between text-sm text-green-600">
                                    <span>Discount:</span>
                                    <span class="font-medium">-${{ number_format($cart['discount_amount'], 2) }}</span>
                                </div>
                            @endif

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">Tax:</span>
                                <span class="font-medium">${{ number_format($cart['tax'], 2) }}</span>
                            </div>

                            <div class="flex justify-between text-lg font-semibold pt-2 border-t">
                                <span>Total:</span>
                                <span>${{ number_format($cart['total'], 2) }}</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full mt-6 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                            Place Order
                        </button>

                        <p class="text-xs text-gray-500 mt-2 text-center">
                            Payment will be processed securely. You'll receive an order confirmation email.
                        </p>
                    </div>
                </div>
            </form>
        </main>
    </div>
</body>
</html>

