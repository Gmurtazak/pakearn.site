<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Oops! Something Went Wrong</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen flex items-center justify-center bg-white px-4">

    <!-- Card -->
    <div class="w-full max-w-md bg-white border border-gray-200 rounded-2xl shadow-lg p-8 text-center">

        <!-- Icon -->
        <div class="mx-auto mb-6 flex items-center justify-center w-16 h-16 rounded-full bg-red-50 text-red-500 text-3xl">
            ❌
        </div>

        <!-- Title -->
        <h1 class="text-2xl font-bold text-gray-800 mb-2">
            Oops!
        </h1>

        <!-- Message -->
        <p class="text-gray-600 mb-6 leading-relaxed">
            Something really went wrong.<br>
            Please try again.
        </p>

        <!-- Button -->
        <button
            onclick="location.reload()"
            class="px-6 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-medium hover:bg-gray-800 transition"
        >
            Try Again
        </button>

        <!-- Small note -->
        <p class="text-xs text-gray-400 mt-6">
            Error code: 500 • Unexpected failure
        </p>

    </div>

</body>
</html>
