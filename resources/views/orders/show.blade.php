<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #{{ $order->order_number }} - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">Order #{{ $order->order_number }}</h1>
                <p class="text-sm text-gray-600 mt-1">Status: 
                    <span class="font-semibold 
                        @if(in_array($order->status, ['processing', 'completed'])) text-green-600
                        @elseif($order->status === 'refunded') text-red-600
                        @elseif(in_array($order->status, ['pending', 'pre_order_pending'])) text-yellow-600
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

                    @if($order->transactions->count() > 0)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h2>
                            <div class="space-y-3">
                                @foreach($order->transactions as $transaction)
                                    <div class="flex justify-between items-center pb-3 border-b border-gray-200 last:border-0">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ ucfirst($transaction->payment_method) }}</p>
                                            <p class="text-sm text-gray-600">{{ ucfirst($transaction->status) }}</p>
                                            @if(isset($transaction->metadata['receipt_url']))
                                                <a href="{{ $transaction->metadata['receipt_url'] }}" target="_blank" 
                                                   class="text-sm text-indigo-600 hover:text-indigo-800 mt-1 inline-block">
                                                    View Receipt
                                                </a>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="font-semibold text-gray-900">${{ number_format($transaction->amount, 2) }}</p>
                                            <p class="text-xs text-gray-500">{{ $transaction->created_at->format('M d, Y') }}</p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="space-y-6">
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                        <div class="space-y-2">
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

                    @if(\Illuminate\Support\Facades\Auth::guard('staff')->check() && 
                        \Illuminate\Support\Facades\Auth::guard('staff')->id() === $order->photographer_id &&
                        $order->transactions()->where('status', 'completed')->exists() &&
                        !$order->transactions()->where('status', 'refunded')->exists())
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                            <h2 class="text-lg font-semibold text-gray-900 mb-4">Refund</h2>
                            <p class="text-sm text-gray-600 mb-4">Issue a refund for this order.</p>
                            <form method="POST" action="{{ route('staff.orders.refund', $order) }}" 
                                  onsubmit="return confirm('Are you sure you want to process a refund for this order?');">
                                @csrf
                                <button type="submit" 
                                        class="w-full px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                                    Process Refund
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </div>
</body>
</html>

