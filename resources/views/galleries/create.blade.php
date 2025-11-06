<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Gallery - Photo Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-sm border-b">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <a href="{{ route('galleries.index') }}" class="text-xl font-semibold text-gray-900">Photo Platform</a>
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
        <main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <div class="mb-6">
                    <h2 class="text-2xl font-bold text-gray-900">Create New Gallery</h2>
                    <p class="mt-2 text-gray-600">Set up a new gallery for your photos</p>
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

                <form method="POST" action="{{ route('galleries.store') }}">
                    @csrf

                    <!-- Gallery Name -->
                    <div class="mb-6">
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                            Gallery Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="name" 
                            name="name" 
                            value="{{ old('name') }}" 
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                    </div>

                    <!-- Description -->
                    <div class="mb-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Description
                        </label>
                        <textarea 
                            id="description" 
                            name="description" 
                            rows="4"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >{{ old('description') }}</textarea>
                    </div>

                    <!-- Access Type -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Access Type <span class="text-red-500">*</span>
                        </label>
                        <div class="space-y-2">
                            <label class="flex items-center">
                                <input type="radio" name="access_type" value="public" {{ old('access_type', 'private') === 'public' ? 'checked' : '' }} class="mr-3">
                                <span class="text-sm text-gray-700">Public - Anyone with the link can view</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="access_type" value="private" {{ old('access_type', 'private') === 'private' ? 'checked' : '' }} class="mr-3">
                                <span class="text-sm text-gray-700">Private - Only you can view</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="access_type" value="password_protected" {{ old('access_type') === 'password_protected' ? 'checked' : '' }} class="mr-3" id="access_type_password">
                                <span class="text-sm text-gray-700">Password Protected - Requires password to view</span>
                            </label>
                        </div>
                    </div>

                    <!-- Password Field (shown conditionally) -->
                    <div class="mb-6" id="password_field" style="display: none;">
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Gallery Password <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            minlength="6"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                        <p class="mt-1 text-sm text-gray-500">Minimum 6 characters</p>
                    </div>

                    <!-- Expiration Date -->
                    <div class="mb-6">
                        <label for="expires_at" class="block text-sm font-medium text-gray-700 mb-2">
                            Expiration Date (Optional)
                        </label>
                        <input 
                            type="date" 
                            id="expires_at" 
                            name="expires_at" 
                            value="{{ old('expires_at') }}"
                            min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                        <p class="mt-1 text-sm text-gray-500">Gallery will expire on this date</p>
                    </div>

                    <!-- Form Actions -->
                    <div class="flex justify-end space-x-4">
                        <a href="{{ route('galleries.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                            Create Gallery
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        // Show/hide password field based on access type
        const accessTypeRadios = document.querySelectorAll('input[name="access_type"]');
        const passwordField = document.getElementById('password_field');
        const passwordInput = document.getElementById('password');

        accessTypeRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'password_protected') {
                    passwordField.style.display = 'block';
                    passwordInput.required = true;
                } else {
                    passwordField.style.display = 'none';
                    passwordInput.required = false;
                    passwordInput.value = '';
                }
            });
        });

        // Initialize on page load
        if (document.getElementById('access_type_password').checked) {
            passwordField.style.display = 'block';
            passwordInput.required = true;
        }
    </script>
</body>
</html>

