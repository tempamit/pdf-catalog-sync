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

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <p class="font-bold">Oops! Something went wrong:</p>
                <ul class="list-disc ml-5 mt-1 text-sm">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('upload.process') }}" method="POST" enctype="multipart/form-data" id="uploadForm" class="space-y-6">
    @csrf
    <div>
        <input type="file" name="catalog_pdf" required
            class="w-full border-2 border-dashed border-gray-300 rounded-lg p-4 text-center cursor-pointer hover:border-blue-500 transition">
    </div>

    <div id="buttonContainer">
        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-lg hover:bg-blue-700 transition">
            Sync Database
        </button>
    </div>

    <div id="loadingIndicator" class="hidden text-center space-y-4">
        <div class="flex items-center justify-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
        </div>
        <p class="text-blue-600 font-bold animate-pulse">Syncing Database... Please wait.</p>
        <p class="text-xs text-gray-400">Python is currently extracting tables and updating PostgreSQL.</p>
    </div>
</form>

<script>
    document.getElementById('uploadForm').onsubmit = function() {
        // Hide the button and show the status bar
        document.getElementById('buttonContainer').classList.add('hidden');
        document.getElementById('loadingIndicator').classList.remove('hidden');
    };
</script>
                <a href="/" class="text-sm text-blue-600 hover:underline">Go to Mobile Catalog</a>
            </div>
        </form>
    </div>

</body>
</html>