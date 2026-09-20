<!DOCTYPE html>
<html lang="hr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0f6b64">
    <title>@yield('title', 'SuperSkyCrew')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    @include('partials.platform-styles')
</head>
<body>
<div class="guest-shell">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="@yield('guest-width', 'col-md-5')">
                @yield('content')
            </div>
        </div>
    </div>
</div>
</body>
</html>
