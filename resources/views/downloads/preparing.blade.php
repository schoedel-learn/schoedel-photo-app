<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preparing Download - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-blue-500 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Preparing Your Download</h1>
            <p class="text-gray-600 mb-6">
                {{ $message ?? 'We\'re preparing your download archive. This may take a few minutes.' }}
            </p>

            @if(isset($order))
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <p class="text-sm font-medium text-gray-900 mb-2">Order #{{ $order->order_number }}</p>
                    <p class="text-sm text-gray-600">
                        You will receive an email with your download link once it's ready.
                    </p>
                </div>
            @endif

            <div class="space-y-3">
                <a href="{{ route('downloads.index') }}" 
                   class="block w-full px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    View My Downloads
                </a>
                <a href="/" 
                   class="block w-full px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                    Return to Home
                </a>
            </div>
        </div>
    </div>
</body>
</html>

