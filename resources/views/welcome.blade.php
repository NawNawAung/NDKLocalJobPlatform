<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'NDK Job Platform') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/dist/tabler-icons.min.css">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div id="app"></div>
        <script>
            window.__AUTH_BOOTSTRAP__ = {{ Illuminate\Support\Js::from($authBootstrap ?? [
                'authPage' => null,
                'authenticated' => auth()->check(),
                'csrfToken' => csrf_token(),
                'errors' => [],
                'old' => [],
                'regions' => [],
            ]) }};
        </script>
    </body>
</html>
