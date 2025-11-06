<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">Order Confirmation</h1>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-6">
                <div class="text-center mb-6">
                    <svg class="mx-auto h-16 w-16 text-green-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Order Placed Successfully!</h2>
                    <p class="text-gray-600">Order Number: <span class="font-semibold">{{ $order->order_number }}</span></p>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Order Details</h3>
                            <div class="space-y-1 text-sm text-gray-600">
                                <p>Order ID: #{{ $order->id }}</p>
                                <p>Date: {{ $order->created_at->format('F d, Y g:i A') }}</p>
                                <p>Status: <span class="font-semibold text-gray-900">{{ ucfirst($order->status) }}</span></p>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900 mb-2">Photographer</h3>
                            <div class="text-sm text-gray-600">
                                <p class="font-medium text-gray-900">{{ $order->photographer->name }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Items</h2>
                <div class="space-y-4">
                    @foreach($order->items as $item)
                        <div class="flex items-start space-x-4 pb-4 border-b border-gray-200 last:border-0">
                            @if($item->photo)
                                <div class="w-20 h-20 bg-gray-200 rounded-lg flex items-center justify-center flex-shrink-0">
                                    @if($item->photo->storage_disk === 'local' || $item->photo->storage_disk === 'public')
                                        <img src="{{ \Illuminate\Support\Facades\Storage::disk($item->photo->storage_disk)->url($item->photo->storage_path) }}" 
                                             alt="{{ $item->photo->filename }}" class="w-full h-full object-cover rounded-lg">
                                    @else
                                        <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                    @endif
                                </div>
                            @endif

                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900">
                                    {{ $item->photo ? $item->photo->filename : 'Photo #' . $item->photo_id }}
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    {{ ucfirst(str_replace('_', ' ', $item->product_type)) }} × {{ $item->quantity }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="font-semibold text-gray-900">${{ number_format($item->total_price, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal:</span>
                        <span class="font-medium">${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax:</span>
                        <span class="font-medium">${{ number_format($order->tax, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-lg font-semibold border-t pt-2">
                        <span>Total:</span>
                        <span>${{ number_format($order->total, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($order->status === 'pending' || $order->status === 'pre_order_pending')
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 mb-6">
                    <h3 class="font-semibold text-yellow-900 mb-2">Payment Pending</h3>
                    <p class="text-sm text-yellow-800 mb-4">
                        Your order has been created. Please complete payment to finalize your order.
                    </p>
                    <a href="{{ route('payments.show', $order) }}" 
                       class="inline-block px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Complete Payment
                    </a>
                </div>
            @endif

            <div class="flex justify-center space-x-4">
                <a href="/packages" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Continue Shopping
                </a>
                <a href="{{ route('orders.show', $order) }}" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    View Order Details
                </a>
            </div>
        </main>
    </div>
</body>
</html>

