<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download Expired - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Download Unavailable</h1>
            <p class="text-gray-600 mb-6">
                {{ $message ?? 'This download link is no longer available.' }}
            </p>

            @if(isset($order))
                <div class="bg-gray-50 rounded-lg p-4 mb-6 text-left">
                    <p class="text-sm font-medium text-gray-900 mb-2">Order #{{ $order->order_number }}</p>
                    <p class="text-sm text-gray-600">
                        If you need to download these photos again, please contact support or purchase a new download.
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

