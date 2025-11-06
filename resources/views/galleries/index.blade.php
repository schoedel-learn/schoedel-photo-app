<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My Galleries - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Photo Platform</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-700">{{ Auth::guard('staff')->user()->name }}</span>
                        <form method="POST" action="{{ route('staff.logout') }}">
                            @csrf
                            <button type="submit" class="text-sm text-gray-600 hover:text-gray-900">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Header -->
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900">My Galleries</h2>
                    <p class="mt-2 text-gray-600">Manage your photo galleries</p>
                </div>
                <a href="{{ route('galleries.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                    Create Gallery
                </a>
            </div>

            <!-- Success Message -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Galleries Grid -->
            @if($galleries->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($galleries as $gallery)
                        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition">
                            <div class="p-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                            <a href="{{ route('galleries.show', $gallery) }}" class="hover:text-indigo-600">
                                                {{ $gallery->name }}
                                            </a>
                                        </h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                            @if($gallery->access_type === 'public') bg-green-100 text-green-800
                                            @elseif($gallery->access_type === 'private') bg-gray-100 text-gray-800
                                            @else bg-yellow-100 text-yellow-800
                                            @endif">
                                            {{ ucfirst(str_replace('_', ' ', $gallery->access_type)) }}
                                        </span>
                                    </div>
                                </div>

                                @if($gallery->description)
                                    <p class="text-sm text-gray-600 mb-4 line-clamp-2">{{ $gallery->description }}</p>
                                @endif

                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span>{{ $gallery->photos_count }} {{ Str::plural('photo', $gallery->photos_count) }}</span>
                                    @if($gallery->expires_at)
                                        <span class="@if($gallery->is_expired) text-red-600 @endif">
                                            Expires: {{ $gallery->expires_at->format('M d, Y') }}
                                        </span>
                                    @endif
                                </div>

                                <div class="flex space-x-2">
                                    <a href="{{ route('galleries.show', $gallery) }}" class="flex-1 text-center px-3 py-2 text-sm bg-gray-100 text-gray-700 rounded hover:bg-gray-200 transition">
                                        View
                                    </a>
                                    <a href="{{ route('galleries.edit', $gallery) }}" class="flex-1 text-center px-3 py-2 text-sm bg-indigo-50 text-indigo-700 rounded hover:bg-indigo-100 transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('galleries.destroy', $gallery) }}" class="inline" onsubmit="return confirm('Are you sure you want to archive this gallery?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3 py-2 text-sm text-red-600 hover:text-red-800 transition">
                                            Archive
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-8">
                    {{ $galleries->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <h3 class="mt-4 text-lg font-medium text-gray-900">No galleries yet</h3>
                    <p class="mt-2 text-sm text-gray-500">Get started by creating your first gallery.</p>
                    <div class="mt-6">
                        <a href="{{ route('galleries.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            Create Gallery
                        </a>
                    </div>
                </div>
            @endif
        </main>
    </div>
</body>
</html>

