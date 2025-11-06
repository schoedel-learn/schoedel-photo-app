<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Downloads - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">My Downloads</h1>
                <p class="text-sm text-gray-600 mt-1">View and download your purchased photos</p>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if($downloads->isEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No Downloads Yet</h3>
                    <p class="text-gray-600">You haven't purchased any photos yet. Browse galleries to find photos you'd like to purchase.</p>
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Photo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expires</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($downloads as $download)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($download->photo)
                                                <div class="flex-shrink-0 h-12 w-12 bg-gray-200 rounded overflow-hidden">
                                                    @if($download->photo->storage_disk === 'local' || $download->photo->storage_disk === 'public')
                                                        <img src="{{ Storage::disk($download->photo->storage_disk)->url($download->photo->storage_path) }}" 
                                                             alt="{{ $download->photo->filename }}" 
                                                             class="h-full w-full object-cover">
                                                    @else
                                                        <svg class="h-full w-full text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                        </svg>
                                                    @endif
                                                </div>
                                                <div class="ml-4">
                                                    <div class="text-sm font-medium text-gray-900">{{ $download->photo->filename }}</div>
                                                    <div class="text-sm text-gray-500">{{ $download->created_at->format('M d, Y') }}</div>
                                                </div>
                                            @else
                                                <span class="text-sm text-gray-500">Photo not found</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">#{{ $download->order->order_number }}</div>
                                        <div class="text-sm text-gray-500">${{ number_format($download->order->total, 2) }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($download->isExpired() || !$download->hasAttemptsRemaining())
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Expired
                                            </span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Active
                                            </span>
                                        @endif
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $download->attempts }} / {{ $download->max_attempts }} attempts
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {{ $download->expires_at->format('M d, Y') }}
                                        @if($download->expires_at->isPast())
                                            <span class="text-red-600">(Expired)</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($download->isValid())
                                            @php
                                                $downloadService = app(\App\Services\DownloadService::class);
                                                $downloadUrl = $downloadService->getDownloadUrl($download);
                                            @endphp
                                            <a href="{{ $downloadUrl }}" 
                                               class="text-indigo-600 hover:text-indigo-900">
                                                Download
                                            </a>
                                        @else
                                            <span class="text-gray-400">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $downloads->links() }}
                </div>
            @endif
        </main>
    </div>
</body>
</html>

