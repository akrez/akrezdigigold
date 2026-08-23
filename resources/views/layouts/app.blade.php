@spaceless
    <!doctype html>
    <html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('layouts._head')
        @yield('POS_HEAD')
    </head>
    <body dir="rtl">
        @yield('content')
    </body>

    </html>
@endspaceless
