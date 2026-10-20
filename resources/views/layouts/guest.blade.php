<!DOCTYPE html>
<html lang="en" class="h-full bg-zinc-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Sign In') — RealEstate</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body class="h-full font-sans antialiased">
<div id="toast-container" class="fixed top-4 right-4 z-50 flex flex-col gap-1 pointer-events-none" aria-live="assertive"></div>
@yield('content')
<script src="{{ asset('js/toast.js') }}"></script>
<script src="{{ asset('js/api.js') }}"></script>
@stack('scripts')
</body>
</html>
