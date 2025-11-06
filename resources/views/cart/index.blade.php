<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-3xl font-bold text-gray-900">Shopping Cart</h1>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            @if(count($cart['items']) > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <div class="lg:col-span-2">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                            <div class="p-6">
                                <h2 class="text-xl font-semibold text-gray-900 mb-6">Cart Items ({{ $cart['item_count'] }})</h2>
                                
                                <div class="space-y-4">
                                    @foreach($cart['items'] as $item)
                                        @php
                                            $itemId = $item['photo_id'] . '_' . $item['product_type'];
                                        @endphp
                                        <div class="flex items-start space-x-4 pb-4 border-b border-gray-200 last:border-0">
                                            @if(isset($item['photo_url']))
                                                <img src="{{ $item['photo_url'] }}" alt="{{ $item['photo_filename'] }}" 
                                                     class="w-24 h-24 object-cover rounded-lg">
                                            @else
                                                <div class="w-24 h-24 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                </div>
                                            @endif

                                            <div class="flex-1">
                                                <h3 class="font-medium text-gray-900">{{ $item['photo_filename'] }}</h3>
                                                <p class="text-sm text-gray-600 mt-1">{{ ucfirst(str_replace('_', ' ', $item['product_type'])) }}</p>
                                                
                                                <div class="mt-3 flex items-center space-x-4">
                                                    <div class="flex items-center space-x-2">
                                                        <label class="text-sm text-gray-700">Qty:</label>
                                                        <form method="POST" action="{{ route('cart.update', $itemId) }}" class="flex items-center space-x-2">
                                                            @csrf
                                                            @method('PUT')
                                                            <input type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="10"
                                                                   onchange="this.form.submit()"
                                                                   class="w-16 px-2 py-1 border border-gray-300 rounded text-sm">
                                                        </form>
                                                    </div>
                                                    <form method="POST" action="{{ route('cart.remove', $itemId) }}" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                                            Remove
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>

                                            <div class="text-right">
                                                <p class="font-semibold text-gray-900">${{ number_format($item['unit_price'], 2) }}</p>
                                                <p class="text-sm text-gray-600">× {{ $item['quantity'] }}</p>
                                                <p class="font-semibold text-gray-900 mt-1">${{ number_format($item['total_price'], 2) }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div class="mt-6 flex justify-end">
                                    <form method="POST" action="{{ route('cart.clear') }}" onsubmit="return confirm('Clear all items from cart?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-red-600 hover:text-red-800">
                                            Clear Cart
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <a href="/gallery" class="text-indigo-600 hover:text-indigo-800 text-sm">
                                ← Continue Shopping
                            </a>
                        </div>
                    </div>

                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky top-8">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>

                            <div class="space-y-3 mb-4">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Subtotal:</span>
                                    <span class="font-medium">${{ number_format($cart['subtotal'], 2) }}</span>
                                </div>

                                @if($cart['discount'])
                                    <div class="flex justify-between text-sm text-green-600">
                                        <span>Discount ({{ $cart['discount']['code'] }}):</span>
                                        <span class="font-medium">-${{ number_format($cart['discount_amount'], 2) }}</span>
                                    </div>
                                    <form method="POST" action="{{ route('cart.remove-discount') }}" class="text-xs">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-800">Remove discount</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('cart.apply-discount') }}" class="flex space-x-2">
                                        @csrf
                                        <input type="text" name="code" placeholder="Discount code" 
                                               class="flex-1 px-3 py-2 border border-gray-300 rounded text-sm">
                                        <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded text-sm hover:bg-gray-200">
                                            Apply
                                        </button>
                                    </form>
                                @endif

                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Tax:</span>
                                    <span class="font-medium">${{ number_format($cart['tax'], 2) }}</span>
                                </div>

                                <div class="border-t pt-3 mt-3">
                                    <div class="flex justify-between text-lg font-semibold">
                                        <span>Total:</span>
                                        <span>${{ number_format($cart['total'], 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            <a href="{{ route('orders.checkout') }}" 
                               class="block w-full text-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Your cart is empty</h3>
                    <p class="text-gray-600 mb-6">Start adding photos to your cart!</p>
                    <a href="/packages" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Browse Packages
                    </a>
                </div>
            @endif
        </main>
    </div>
</body>
</html>

