<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $gallery->name }} - Photo Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/js/gallery-viewer.js', 'resources/js/photo-selection.js'])
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">{{ $gallery->name }}</h1>
                    @if($gallery->description)
                        <p class="text-sm text-gray-600 mt-1">{{ $gallery->description }}</p>
                    @endif
                </div>
                <div class="flex items-center space-x-4">
                    <div id="selection-counter" class="hidden px-4 py-2 bg-indigo-100 text-indigo-700 rounded-lg text-sm font-medium">
                        <span id="selected-count">0</span> selected
                    </div>
                    <button id="select-all-btn" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Select All</button>
                    <button id="clear-selection-btn" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 hidden">Clear</button>
                </div>
            </div>
        </div>
    </header>

    <!-- Filters and Sort -->
    <div class="bg-white border-b sticky top-[73px] z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-700">Filter:</label>
                    <select id="filter-select" class="px-3 py-1 border border-gray-300 rounded text-sm">
                        <option value="all">All Photos</option>
                        <option value="selected">Selected Only</option>
                        <option value="unselected">Unselected Only</option>
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-sm text-gray-700">Sort:</label>
                    <select id="sort-select" class="px-3 py-1 border border-gray-300 rounded text-sm">
                        <option value="default">Default</option>
                        <option value="date-asc">Date (Oldest First)</option>
                        <option value="date-desc">Date (Newest First)</option>
                        <option value="name-asc">Name (A-Z)</option>
                        <option value="name-desc">Name (Z-A)</option>
                        <option value="selected-first">Selected First</option>
                    </select>
                </div>
                <div class="text-sm text-gray-600">
                    <span id="photo-count">{{ $photos->total() }}</span> photos
                </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
            <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                {{ session('success') }}
            </div>
        </div>
    @endif

    <!-- Photo Grid -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div id="photo-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-2 sm:gap-4">
            @foreach($photos as $photo)
                <div 
                    class="photo-item relative group cursor-pointer bg-gray-100 rounded-lg overflow-hidden aspect-square"
                    data-photo-id="{{ $photo->id }}"
                    data-photo-url="{{ $photo->full_url ?? $photo->signed_url }}"
                    data-photo-name="{{ $photo->filename }}"
                    data-photo-date="{{ $photo->created_at->timestamp }}"
                >
                    <!-- Lazy Loading Image -->
                    <img 
                        src="" 
                        data-src="{{ $photo->signed_url }}"
                        alt="{{ $photo->filename }}"
                        class="w-full h-full object-cover transition-opacity duration-300 opacity-0"
                        loading="lazy"
                    >
                    
                    <!-- Loading Skeleton -->
                    <div class="absolute inset-0 bg-gray-200 animate-pulse"></div>

                    <!-- Selection Checkbox -->
                    <div class="absolute top-2 left-2 z-10">
                        <input 
                            type="checkbox" 
                            class="photo-checkbox w-5 h-5 text-indigo-600 rounded focus:ring-indigo-500 cursor-pointer opacity-0 group-hover:opacity-100 transition-opacity"
                            data-photo-id="{{ $photo->id }}"
                        >
                    </div>

                    <!-- Overlay on Hover -->
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-40 transition-all duration-200 flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <span class="text-white text-sm font-medium">View Full Size</span>
                    </div>

                    <!-- Selected Indicator -->
                    <div class="selected-indicator absolute top-2 right-2 hidden">
                        <div class="w-6 h-6 bg-indigo-600 rounded-full flex items-center justify-center">
                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Add to Cart Section -->
        @if(isset($galleries) || true)
            <div class="mt-6 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Selected Photos</h3>
                        <p class="text-sm text-gray-600 mt-1">Add selected photos to cart for purchase</p>
                    </div>
                    <form method="POST" action="{{ route('cart.add-bulk') }}" id="add-to-cart-form">
                        @csrf
                        <div id="selected-photo-ids-container"></div>
                        <button type="submit" id="add-to-cart-btn" disabled
                                class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-semibold disabled:bg-gray-400 disabled:cursor-not-allowed">
                            Add Selected to Cart
                        </button>
                    </form>
                </div>
            </div>
        @endif

        <!-- Pagination -->
        @if($photos->hasPages())
            <div class="mt-8">
                {{ $photos->links() }}
            </div>
        @endif
    </main>

    <!-- Lightbox -->
    <div id="lightbox" class="fixed inset-0 bg-black bg-opacity-95 z-50 hidden">
        <div class="absolute top-4 right-4 z-50">
            <button id="lightbox-close" class="text-white hover:text-gray-300 p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="absolute top-4 left-4 z-50 flex items-center space-x-4">
            <button id="lightbox-prev" class="text-white hover:text-gray-300 p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
            </button>
            <span id="lightbox-counter" class="text-white text-sm"></span>
            <button id="lightbox-next" class="text-white hover:text-gray-300 p-2">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </button>
        </div>

        <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 z-50 flex items-center space-x-2">
            <button id="zoom-out" class="text-white hover:text-gray-300 p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7" />
                </svg>
            </button>
            <button id="zoom-fit" class="text-white hover:text-gray-300 p-2 px-4 text-sm">Fit</button>
            <button id="zoom-in" class="text-white hover:text-gray-300 p-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7" />
                </svg>
            </button>
        </div>

        <div id="lightbox-image-container" class="w-full h-full flex items-center justify-center overflow-hidden cursor-move">
            <img id="lightbox-image" src="" alt="" class="max-w-full max-h-full object-contain transition-transform duration-200">
        </div>
    </div>

    <!-- Gallery Data -->
    <script>
        window.galleryData = {
            slug: '{{ $gallery->slug }}',
            id: {{ $gallery->id }},
            csrfToken: '{{ csrf_token() }}',
            toggleSelectionUrl: '{{ route("client.photos.toggle", ":id") }}',
            clearSelectionUrl: '{{ route("client.gallery.clear-selections", $gallery) }}',
        };
    </script>
</body>
</html>

