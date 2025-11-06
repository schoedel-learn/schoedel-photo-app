<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Packages - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-3xl font-bold text-gray-900">Pre-Order Packages</h1>
                <p class="mt-2 text-gray-600">Purchase a package before your photo session and save!</p>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @foreach($packages as $photographerId => $photographerPackages)
                @php
                    $photographer = $photographerPackages->first()->photographer;
                @endphp

                <div class="mb-12">
                    <h2 class="text-2xl font-semibold text-gray-900 mb-4">{{ $photographer->name }}</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach($photographerPackages as $package)
                            <div class="bg-white rounded-lg shadow-md border border-gray-200 overflow-hidden hover:shadow-lg transition">
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-4">
                                        <h3 class="text-xl font-bold text-gray-900">{{ $package->name }}</h3>
                                        <div class="text-2xl font-bold text-indigo-600">${{ number_format($package->price, 2) }}</div>
                                    </div>

                                    @if($package->description)
                                        <p class="text-gray-600 mb-4">{{ $package->description }}</p>
                                    @endif

                                    <div class="space-y-2 mb-6">
                                        <div class="flex items-center text-sm">
                                            <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                            </svg>
                                            <span>{{ $package->photo_count }} Photos Included</span>
                                        </div>
                                        @if($package->includes_digital)
                                            <div class="flex items-center text-sm">
                                                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Digital Downloads Included</span>
                                            </div>
                                        @endif
                                        @if($package->includes_prints)
                                            <div class="flex items-center text-sm">
                                                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                </svg>
                                                <span>Physical Prints Included</span>
                                            </div>
                                        @endif
                                    </div>

                                    <a href="{{ route('pre-orders.create', $package) }}" 
                                       class="block w-full text-center px-4 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                                        Purchase Package
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @if($packages->isEmpty())
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <p class="text-gray-600">No packages available at this time.</p>
                </div>
            @endif
        </main>
    </div>
</body>
</html>

