<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'ORDO Project')</title>
    <link rel="stylesheet" href="{{ asset('css/ordo.css') }}">
</head>
<body><a class="skip-link" href="#main-content">Skip to content</a>
<div class="app-shell">
    <header class="topbar">
        <div class="brand">John Kelly <small><b>C</b> Company</small></div>
        <div class="search">⌕ &nbsp; Search ORDO</div>
        <div class="user">♧ <span>J</span></div>
    </header>
    <aside class="rail">
        @foreach(['▰','♙','⚑','▤','▧','▥','♟','◎','⌁','▣','⌘'] as $icon)
            <span class="rail-icon" aria-hidden="true">{{ $icon }}</span>
        @endforeach
    </aside>
    <main id="main-content">
@if(session('status'))<div class="notice" role="status">{{ session('status') }}</div>@endif
@if($errors->any())<div class="notice error" role="alert"><strong>Please correct the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
@yield('content')</main>
</div>
<script src="{{ asset('js/ordo.js') }}"></script>
@stack('scripts')
</body>
</html>
