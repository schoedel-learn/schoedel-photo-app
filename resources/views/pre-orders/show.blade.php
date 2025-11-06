<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pre-Order #{{ $order->id }} - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">Pre-Order #{{ $order->id }}</h1>
                <p class="text-sm text-gray-600 mt-1">Status: 
                    <span class="font-semibold 
                        @if($order->status === 'pre_order_paid') text-green-600
                        @elseif($order->status === 'pre_order_selecting') text-blue-600
                        @elseif($order->status === 'pre_order_finalized') text-indigo-600
                        @else text-gray-600
                        @endif">
                        {{ ucfirst(str_replace('_', ' ', $order->status)) }}
                    </span>
                </p>
            </div>
        </header>

        <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Package Details</h2>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Package:</span>
                                <span class="font-semibold">{{ $order->package->name }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Photos Included:</span>
                                <span class="font-semibold">{{ $order->package->photo_count }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Selected Photos:</span>
                                <span class="font-semibold">{{ $order->selected_photo_count ?? 0 }}</span>
                            </div>
                            @if($order->upsell_amount > 0)
                                <div class="flex justify-between text-indigo-600">
                                    <span>Additional Photos:</span>
                                    <span class="font-semibold">${{ number_format($order->upsell_amount, 2) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($order->status === 'pre_order_paid' && Auth::guard('staff')->check() && Auth::guard('staff')->id() === $order->photographer_id)
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-blue-900 mb-2">Ready to Finalize</h3>
                            <p class="text-sm text-blue-800 mb-4">Select a gallery to begin the photo selection process for this client.</p>
                            <form method="POST" action="{{ route('staff.pre-orders.start-finalization', $order) }}">
                                @csrf
                                <select name="gallery_id" required class="w-full px-4 py-2 border border-blue-300 rounded-lg mb-4">
                                    <option value="">Select Gallery...</option>
                                    @if(isset($galleries))
                                        @foreach($galleries as $gallery)
                                            <option value="{{ $gallery->id }}">{{ $gallery->name }}</option>
                                        @endforeach
                                    @endif
                                </select>
                                <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                    Start Finalization
                                </button>
                            </form>
                        </div>
                    @endif

                    @if($order->status === 'pre_order_selecting')
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-yellow-900 mb-2">Photo Selection Required</h3>
                            <p class="text-sm text-yellow-800 mb-4">
                                @if(Auth::guard('client')->check())
                                    Please select your photos to finalize this pre-order.
                                @else
                                    Client is selecting photos for this pre-order.
                                @endif
                            </p>
                            @if(Auth::guard('client')->check() && $order->user_id === Auth::guard('client')->id())
                                <a href="{{ route('pre-orders.finalize', $order) }}" 
                                   class="inline-block px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                                    Select Photos
                                </a>
                            @endif
                        </div>
                    @endif

                    @if($order->status === 'pre_order_finalized')
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-green-900 mb-2">Order Finalized</h3>
                            <p class="text-sm text-green-800">All photos have been selected and the order is complete.</p>
                        </div>
                    @endif

                    @if($order->status === 'pre_order_pending')
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6">
                            <h3 class="text-lg font-semibold text-yellow-900 mb-2">Payment Pending</h3>
                            <p class="text-sm text-yellow-800 mb-4">
                                Please complete payment to finalize your pre-order.
                            </p>
                            <a href="{{ route('payments.show', $order) }}" 
                               class="inline-block px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition">
                                Complete Payment
                            </a>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                        <div class="space-y-2 mb-4">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal:</span>
                                <span>${{ number_format($order->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax:</span>
                                <span>${{ number_format($order->tax, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-lg font-semibold border-t pt-2">
                                <span>Total:</span>
                                <span>${{ number_format($order->total, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Information</h2>
                        <div class="space-y-2 text-sm">
                            <div>
                                <span class="text-gray-600">Order Date:</span>
                                <span class="font-semibold">{{ $order->created_at->format('M d, Y') }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Client:</span>
                                <span class="font-semibold">{{ $order->user->name }}</span>
                            </div>
                            <div>
                                <span class="text-gray-600">Photographer:</span>
                                <span class="font-semibold">{{ $order->photographer->name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>

