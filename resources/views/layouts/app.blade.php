<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Projects Portal') - Eindhoven University of Technology</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@300;400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/gilroy-bold" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white text-gray-900 font-sans">
    @livewire('navbar')
    @include('components.impersonate-banner')

    <main class="min-h-screen">
        @yield('content')
    </main>

    <footer class="bg-gray-50 border-t border-gray-200 mt-8 sm:mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
            <div class="text-center text-xs sm:text-sm text-gray-600 space-y-2">
                <p>&copy; {{ date('Y') }} Eindhoven University of Technology. All rights reserved.</p>
                <p>
                    <a href="{{ route('privacy') }}" class="text-[#7fabc9] hover:underline">Privacy Policy</a>
                    <span class="mx-2">|</span>
                    <a href="{{ route('privacy', ['lang' => 'nl']) }}" class="text-[#7fabc9] hover:underline">Privacyverklaring</a>
                </p>
            </div>
        </div>
    </footer>

    <script>
        // Email obfuscation function (hex decoding)
        function decodeEmail(encodedString) {
            let email = '';
            for (let i = 0; i < encodedString.length; i += 2) {
                email += String.fromCharCode(parseInt(encodedString.substr(i, 2), 16));
            }
            return email;
        }

        // Initialize email links on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.obfuscated-email').forEach(function(element) {
                const encoded = element.dataset.encoded;
                if (encoded) {
                    const email = decodeEmail(encoded);
                    element.href = 'mailto:' + email;
                    element.textContent = email;
                }
            });
        });
    </script>
</body>
</html>

