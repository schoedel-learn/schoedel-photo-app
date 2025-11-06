<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase Package - {{ $package->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                <h1 class="text-2xl font-bold text-gray-900">Purchase Pre-Order Package</h1>
            </div>
        </header>

        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">{{ $package->name }}</h2>
                <div class="space-y-2 mb-6">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Price:</span>
                        <span class="font-semibold text-gray-900">${{ number_format($package->price, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Photos Included:</span>
                        <span class="font-semibold text-gray-900">{{ $package->photo_count }}</span>
                    </div>
                    @if($package->includes_digital)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Digital Downloads Included
                        </div>
                    @endif
                    @if($package->includes_prints)
                        <div class="flex items-center text-sm text-gray-600">
                            <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            Physical Prints Included
                        </div>
                    @endif
                </div>
            </div>

            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('pre-orders.store', $package) }}" class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                @csrf

                <h3 class="text-lg font-semibold text-gray-900 mb-4">Billing Information</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="billing_address_name" class="block text-sm font-medium text-gray-700 mb-1">
                            Full Name <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="billing_address_name" name="billing_address[name]" required
                            value="{{ old('billing_address.name', Auth::guard('client')->user()->name ?? '') }}"
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

                <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                    <div class="flex justify-between text-lg font-semibold">
                        <span>Total:</span>
                        <span>${{ number_format($package->price * 1.08, 2) }}</span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">Includes 8% tax</p>
                </div>

                <div class="mt-6">
                    <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold">
                        Complete Purchase
                    </button>
                    <p class="text-xs text-gray-500 mt-2 text-center">
                        You'll be able to select your photos after your session. Payment will be processed securely.
                    </p>
                </div>
            </form>
        </main>
    </div>
</body>
</html>

