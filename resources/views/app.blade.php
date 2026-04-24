<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mailulator</title>
    @php
        $manifestPath = public_path('vendor/mailulator/manifest.json');
        $manifest = file_exists($manifestPath) ? json_decode(file_get_contents($manifestPath), true) : null;
        $entry = $manifest['resources/js/app.js'] ?? null;
    @endphp
    @if ($entry)
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
    @if ($entry)
        <div id="mailulator-app"></div>
    @else
        <div class="callout">
            <h1 style="margin-top:0;">Mailulator assets not published</h1>
            <p>Run <code>php artisan vendor:publish --tag=mailulator-assets</code> to copy the compiled UI into <code>public/vendor/mailulator</code>.</p>
        </div>
    @endif
</body>
</html>
