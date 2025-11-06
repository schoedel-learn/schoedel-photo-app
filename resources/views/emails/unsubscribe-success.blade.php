<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsubscribe Successful</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-md w-full bg-white rounded-lg shadow-lg p-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Successfully Unsubscribed</h1>
            <p class="text-gray-600 mb-6">
                Hi {{ $user->name }},<br>
                You have been unsubscribed from marketing emails. You will no longer receive promotional emails from us.
            </p>
            <p class="text-sm text-gray-500">
                Note: You will still receive important transactional emails related to your orders and account.
            </p>
        </div>
    </div>
</body>
</html>

