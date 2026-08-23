<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="shortcut icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" sizes="180x180" href="/favicon/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="32x32" href="/favicon/favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="/favicon/favicon-16x16.png">
<link rel="manifest" href="/site.webmanifest">

@hasSection('title')
    <title>@yield('title')</title>
@else
    @hasSection('header')
        <title>@yield('header')@hasSection('subheader'){{ ' | ' }}@yield('subheader')@endif</title>
    @else
        <title>{{ config('app.name') }}</title>
    @endif
@endif

@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@endif
