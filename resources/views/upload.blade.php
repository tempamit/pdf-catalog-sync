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

        <form action="{{ route('upload.process') }}" method="POST" enctype="multipart/form-data" id="syncForm" class="space-y-4">
    @csrf
    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
        <input type="file" name="catalog_pdf" class="hidden" id="fileInput" required>
        <label for="fileInput" class="cursor-pointer text-blue-600 hover:underline">Choose PDF Catalog</label>
        <p id="fileName" class="text-xs text-gray-500 mt-2">No file selected</p>
    </div>

    <button type="submit" id="syncBtn" class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg shadow-md hover:bg-blue-700 transition">
        Sync Database
    </button>

    <div id="statusBar" class="hidden">
        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700">
            <div class="bg-blue-600 h-2.5 rounded-full animate-pulse" style="width: 100%"></div>
        </div>
        <p class="text-center text-sm text-blue-600 mt-2 font-medium">Syncing... Python is extracting PDF data.</p>
    </div>
</form>

<script>
    document.getElementById('fileInput').onchange = function() {
        document.getElementById('fileName').innerText = this.files[0].name;
    };

    document.getElementById('syncForm').onsubmit = function() {
        document.getElementById('syncBtn').disabled = true;
        document.getElementById('syncBtn').classList.add('opacity-50');
        document.getElementById('statusBar').classList.remove('hidden');
    };
</script>
                <a href="/" class="text-sm text-blue-600 hover:underline">Go to Mobile Catalog</a>
            </div>
        </form>
    </div>

</body>
</html>