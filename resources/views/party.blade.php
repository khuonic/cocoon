<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="robots" content="noindex, nofollow">
        <meta name="color-scheme" content="light">
        <meta name="theme-color" content="#1B1B2F">
        <style>
            html {
                background-color: #22223B;
                color-scheme: light;
            }
        </style>

        <title inertia>La fête</title>

        <link rel="icon" href="/fete-favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/fete-apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Pixelify+Sans:wght@400;600&display=swap" rel="stylesheet">

        @vite(['resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="antialiased">
        @inertia
    </body>
</html>
