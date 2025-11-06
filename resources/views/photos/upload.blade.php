<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Upload Photos - {{ $gallery->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/dropzone@6/dist/dropzone.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/dropzone@6/dist/dropzone.css" type="text/css" />
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
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="mb-6">
                <a href="{{ route('galleries.show', $gallery) }}" class="text-indigo-600 hover:text-indigo-800">← Back to Gallery</a>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8">
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Upload Photos</h1>
                    <p class="text-gray-600">Add photos to <strong>{{ $gallery->name }}</strong></p>
                    <p class="text-sm text-gray-500 mt-2">You can upload up to 100 photos at once (max 50MB each). Supported formats: JPEG, PNG, HEIC.</p>
                </div>

                <!-- Upload Zone -->
                <form action="{{ route('galleries.photos.store', $gallery) }}" 
                      class="dropzone border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-indigo-400 transition"
                      id="photo-upload-form">
                    @csrf
                    <div class="dz-message">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                        </svg>
                        <p class="text-lg text-gray-700 mb-2">Drop files here or click to upload</p>
                        <p class="text-sm text-gray-500">Select up to 100 photos (JPEG, PNG, HEIC)</p>
                    </div>
                </form>

                <!-- Progress Section -->
                <div id="upload-progress" class="hidden mt-6">
                    <div class="mb-2 flex justify-between text-sm text-gray-600">
                        <span>Upload Progress</span>
                        <span id="progress-text">0%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progress-bar" class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <div id="progress-status" class="mt-2 text-sm text-gray-600"></div>
                </div>

                <!-- Success/Error Messages -->
                <div id="upload-messages" class="mt-6 space-y-2"></div>

                <!-- Uploaded Files Preview -->
                <div id="uploaded-files" class="mt-8 hidden">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Processing Photos</h3>
                    <div id="files-list" class="space-y-2"></div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Configure Dropzone
        Dropzone.autoDiscover = false;

        let uploadBatchId = null;
        let progressInterval = null;

        const dropzone = new Dropzone("#photo-upload-form", {
            paramName: "photos",
            maxFiles: 100,
            maxFilesize: 50, // 50MB
            acceptedFiles: ".jpeg,.jpg,.png,.heic,.heif",
            parallelUploads: 1,
            uploadMultiple: true,
            addRemoveLinks: false,
            dictDefaultMessage: "",
            dictFileTooBig: "File is too big ({{filesize}}MB). Max filesize: {{maxFilesize}}MB.",
            dictInvalidFileType: "You can't upload files of this type.",
            dictMaxFilesExceeded: "You can't upload more than 100 files.",
            
            successmultiple: function(files, response) {
                if (response.success && response.batch_id) {
                    uploadBatchId = response.batch_id;
                    document.getElementById('upload-progress').classList.remove('hidden');
                    document.getElementById('uploaded-files').classList.remove('hidden');
                    
                    // Show uploaded files
                    files.forEach(file => {
                        const fileDiv = document.createElement('div');
                        fileDiv.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-lg';
                        fileDiv.innerHTML = `
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                    <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">${file.name}</p>
                                    <p class="text-xs text-gray-500">Processing...</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <div class="w-4 h-4 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                            </div>
                        `;
                        document.getElementById('files-list').appendChild(fileDiv);
                    });

                    // Start polling for progress
                    startProgressPolling();
                } else {
                    showMessage('Upload started but batch ID not received', 'error');
                }
            },

            errormultiple: function(files, message) {
                showMessage('Upload failed: ' + message, 'error');
            },

            error: function(file, message) {
                showMessage(file.name + ': ' + message, 'error');
            }
        });

        function startProgressPolling() {
            if (!uploadBatchId) return;

            progressInterval = setInterval(() => {
                fetch(`{{ url('/staff/photos/upload/progress') }}/${uploadBatchId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            clearInterval(progressInterval);
                            showMessage('Error checking progress: ' + data.error, 'error');
                            return;
                        }

                        const progress = data.progress || 0;
                        const processed = data.processed_jobs || 0;
                        const total = data.total_jobs || 0;
                        const failed = data.failed_jobs || 0;

                        // Update progress bar
                        document.getElementById('progress-bar').style.width = progress + '%';
                        document.getElementById('progress-text').textContent = Math.round(progress) + '%';
                        document.getElementById('progress-status').textContent = 
                            `Processing: ${processed}/${total} photos${failed > 0 ? ` (${failed} failed)` : ''}`;

                        // Check if finished
                        if (data.finished) {
                            clearInterval(progressInterval);
                            if (failed === 0) {
                                showMessage('All photos uploaded and processed successfully!', 'success');
                                setTimeout(() => {
                                    window.location.href = '{{ route("galleries.show", $gallery) }}';
                                }, 2000);
                            } else {
                                showMessage(`Upload completed with ${failed} failed photos.`, 'warning');
                            }
                        } else if (data.cancelled) {
                            clearInterval(progressInterval);
                            showMessage('Upload was cancelled.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching progress:', error);
                    });
            }, 2000); // Poll every 2 seconds
        }

        function showMessage(message, type) {
            const messagesDiv = document.getElementById('upload-messages');
            const messageDiv = document.createElement('div');
            
            const bgColor = type === 'success' ? 'bg-green-50 border-green-200 text-green-800' :
                          type === 'error' ? 'bg-red-50 border-red-200 text-red-800' :
                          'bg-yellow-50 border-yellow-200 text-yellow-800';
            
            messageDiv.className = `border rounded-lg px-4 py-3 ${bgColor}`;
            messageDiv.textContent = message;
            messagesDiv.appendChild(messageDiv);

            // Auto-remove after 5 seconds
            setTimeout(() => {
                messageDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>

