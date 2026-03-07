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

        <form action="{{ route('upload.process') }}" method="POST" enctype="multipart/form-data" id="syncForm">
    @csrf
    <div class="mb-4">
        <input type="file" name="catalog_pdf" class="w-full p-2 border rounded" required>
    </div>

    <button type="submit" id="syncBtn" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg">
        Sync Database
    </button>

    <div id="statusBar" class="hidden mt-4">
        <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
            <div class="bg-blue-600 h-full animate-pulse" style="width: 100%"></div>
        </div>
        <p class="text-sm text-blue-600 mt-2 text-center font-semibold italic">Processing PDF... Please do not refresh.</p>
    </div>
</form>

<script>
    document.getElementById('syncForm').onsubmit = function() {
        document.getElementById('syncBtn').disabled = true;
        document.getElementById('syncBtn').classList.add('opacity-50', 'cursor-not-allowed');
        document.getElementById('statusBar').classList.remove('hidden');
    };
</script>
                <a href="/" class="text-sm text-blue-600 hover:underline">Go to Mobile Catalog</a>
            </div>
        </form>
    </div>

</body>
</html>