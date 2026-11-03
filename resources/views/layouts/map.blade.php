<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>

  <meta charset="utf-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

  <title>@yield('title', '災図')</title>

  <link rel="shortcut icon" href="{{ asset('img/favicon.ico') }}" type="image/vnd.microsoft.icon">
  <link rel="apple-touch-icon-precomposed" href="{{ asset('img/apple-touch-icon.png') }}">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="{{ asset('plugins/fontawesome-free/css/all.min.css') }}">
  <link rel="stylesheet" href="{{ asset('dist/css/adminlte.min.css') }}">

  @stack('styles')

</head>
<body class="map-app-body">

  @yield('content')

  @stack('scripts')

</body>
</html>
