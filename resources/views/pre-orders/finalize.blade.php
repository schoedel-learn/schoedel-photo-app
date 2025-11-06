<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Finalize Pre-Order - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <header class="bg-white shadow-sm border-b sticky top-0 z-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Finalize Pre-Order</h1>
                        <p class="text-sm text-gray-600 mt-1">{{ $package->name }} - Select {{ $package->photo_count }} photos</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <div id="selection-counter" class="px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-medium">
                            <span id="selected-count">0</span> / {{ $package->photo_count }} selected
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            @if($errors->any())
                <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg">
                <p class="font-semibold">Package: {{ $package->name }}</p>
                <p class="text-sm mt-1">You must select at least {{ $package->photo_count }} photos. Additional photos can be added for $5.00 each.</p>
            </div>

            <form method="POST" action="{{ route('pre-orders.complete-finalization', $order) }}" id="finalize-form">
                @csrf

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-4 mb-8">
                    @foreach($photos as $photo)
                        <div class="photo-item relative group cursor-pointer bg-gray-100 rounded-lg overflow-hidden aspect-square"
                             data-photo-id="{{ $photo->id }}">
                            <input type="checkbox" name="photo_ids[]" value="{{ $photo->id }}" 
                                   class="photo-checkbox hidden"
                                   id="photo_{{ $photo->id }}">
                            <label for="photo_{{ $photo->id }}" class="block w-full h-full">
                                <img src="{{ $photo->signed_url }}" alt="{{ $photo->filename }}"
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center">
                                    <div class="photo-select-indicator w-8 h-8 border-4 border-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity"></div>
                                </div>
                                <div class="photo-selected-indicator absolute top-2 right-2 hidden">
                                    <div class="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>

                @if($photos->count() === 0)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                        <p class="text-gray-600">No photos available in this gallery yet.</p>
                    </div>
                @endif

                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 sticky bottom-0">
                    <div class="flex justify-between items-center">
                        <div>
                            <p class="text-sm text-gray-600">Selected: <span id="selected-count-display">0</span> photos</p>
                            <p class="text-sm text-gray-600 mt-1">
                                Package includes: {{ $package->photo_count }} photos
                                <span id="upsell-indicator" class="hidden text-indigo-600">+ <span id="additional-count">0</span> additional ($<span id="upsell-amount">0.00</span>)</span>
                            </p>
                            <p class="text-lg font-semibold mt-2">
                                Total: $<span id="total-amount">{{ number_format($order->total, 2) }}</span>
                            </p>
                        </div>
                        <button type="submit" id="finalize-btn" disabled
                                class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed">
                            Finalize Order
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>

    <script>
        const requiredCount = {{ $package->photo_count }};
        const additionalPhotoPrice = 5.00;
        const baseTotal = {{ $order->subtotal }};

        document.querySelectorAll('.photo-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelection);
        });

        function updateSelection() {
            const selected = document.querySelectorAll('.photo-checkbox:checked');
            const count = selected.length;

            // Update counter
            document.getElementById('selected-count').textContent = count;
            document.getElementById('selected-count-display').textContent = count;

            // Update visual indicators
            document.querySelectorAll('.photo-item').forEach(item => {
                const checkbox = item.querySelector('.photo-checkbox');
                const indicator = item.querySelector('.photo-selected-indicator');
                if (checkbox.checked) {
                    item.classList.add('ring-4', 'ring-indigo-500');
                    indicator.classList.remove('hidden');
                } else {
                    item.classList.remove('ring-4', 'ring-indigo-500');
                    indicator.classList.add('hidden');
                }
            });

            // Calculate upsell
            let additionalCount = 0;
            let upsellAmount = 0;
            if (count > requiredCount) {
                additionalCount = count - requiredCount;
                upsellAmount = additionalCount * additionalPhotoPrice;
            }

            // Update upsell display
            const upsellIndicator = document.getElementById('upsell-indicator');
            if (additionalCount > 0) {
                upsellIndicator.classList.remove('hidden');
                document.getElementById('additional-count').textContent = additionalCount;
                document.getElementById('upsell-amount').textContent = upsellAmount.toFixed(2);
            } else {
                upsellIndicator.classList.add('hidden');
            }

            // Calculate total
            const subtotal = baseTotal + upsellAmount;
            const tax = subtotal * 0.08;
            const total = subtotal + tax;
            document.getElementById('total-amount').textContent = total.toFixed(2);

            // Enable/disable finalize button
            const finalizeBtn = document.getElementById('finalize-btn');
            if (count >= requiredCount) {
                finalizeBtn.disabled = false;
            } else {
                finalizeBtn.disabled = true;
            }
        }

        // Prevent form submission if not enough photos
        document.getElementById('finalize-form').addEventListener('submit', function(e) {
            const selected = document.querySelectorAll('.photo-checkbox:checked').length;
            if (selected < requiredCount) {
                e.preventDefault();
                alert(`Please select at least ${requiredCount} photos.`);
                return false;
            }
        });
    </script>
</body>
</html>

