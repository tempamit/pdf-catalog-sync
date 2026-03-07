<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Catalog PDF</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 h-screen flex items-center justify-center">

    <div class="bg-white p-10 rounded-lg shadow-lg w-full max-w-2xl">
        <h2 class="text-2xl font-bold mb-6 text-gray-800 border-b pb-2">Catalog Sync Management</h2>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('upload.process') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="catalog_pdf">
                    Upload Latest Master PDF (Exact format required)
                </label>
                <input type="file" name="catalog_pdf" id="catalog_pdf" accept=".pdf" required
                    class="block w-full text-sm text-gray-500
                           file:mr-4 file:py-2 file:px-4
                           file:rounded-md file:border-0
                           file:text-sm file:font-semibold
                           file:bg-blue-50 file:text-blue-700
                           hover:file:bg-blue-100 border border-gray-300 rounded p-2">
            </div>

            <div class="flex items-center justify-between mt-8">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded focus:outline-none focus:shadow-outline transition duration-150">
                    Sync Database
                </button>
                <a href="/" class="text-sm text-blue-600 hover:underline">Go to Mobile Catalog</a>
            </div>
        </form>
    </div>

</body>
</html>