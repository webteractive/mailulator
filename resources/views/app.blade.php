<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mailulator</title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%230f172a' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='2' y='4' width='20' height='16' rx='2'/%3E%3Cpath d='m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7'/%3E%3C/svg%3E">
    @php
        $hotPath = public_path('vendor/mailulator/hot');
        $hot = file_exists($hotPath) ? trim(file_get_contents($hotPath)) : null;

        $entry = null;
        if (! $hot) {
            $manifestPath = public_path('vendor/mailulator/.vite/manifest.json');
            $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
            $entry = $manifest['resources/js/app.js'] ?? null;
        }
    @endphp
    @if ($hot)
        <script type="module" src="{{ $hot }}/@@vite/client"></script>
        <script type="module" src="{{ $hot }}/resources/js/app.js"></script>
    @elseif ($entry)
        @if (! empty($entry['css']))
            @foreach ($entry['css'] as $css)
                <link rel="stylesheet" href="{{ asset('vendor/mailulator/'.$css) }}">
            @endforeach
        @endif
        <script type="module" src="{{ asset('vendor/mailulator/'.$entry['file']) }}" defer></script>
    @else
        <style>
            body { font-family: system-ui, sans-serif; padding: 2rem; color: #444; }
            .callout { max-width: 560px; border-left: 4px solid #f59e0b; padding: 1rem 1.25rem; background: #fffbeb; border-radius: 4px; }
            code { background: #fef3c7; padding: 2px 6px; border-radius: 3px; font-size: 90%; }
        </style>
    @endif
    <script>
        window.MAILULATOR_CONFIG = @json($config);
    </script>
</head>
<body>
    @if ($hot || $entry)
        <div id="mailulator-app"></div>
    @else
        <div class="callout">
            <h1 style="margin-top:0;">Mailulator assets not published</h1>
            <p>Run <code>php artisan vendor:publish --tag=mailulator-assets</code> to copy the compiled UI into <code>public/vendor/mailulator</code>, or start the dev server with <code>composer dev</code>.</p>
        </div>
    @endif
</body>
</html>
