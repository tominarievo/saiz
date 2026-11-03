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

  <style>
    .public-hero {
      background: linear-gradient(135deg, #1f3c88 0%, #2b65b3 100%);
      color: #fff;
      padding: 3.5rem 0 3rem;
    }
    .public-hero h1 {
      font-size: 2.2rem;
      font-weight: 700;
      margin-bottom: 0.75rem;
    }
    .public-hero p {
      font-size: 1.05rem;
      margin-bottom: 0;
      opacity: 0.92;
    }
    .public-card-title {
      font-weight: 700;
    }
    .public-count {
      font-size: 2rem;
      font-weight: 700;
      line-height: 1.1;
    }
    .public-footer {
      border-top: 1px solid #e9ecef;
      color: #6c757d;
      font-size: 0.85rem;
      padding: 1.5rem 0;
    }
  </style>

  @stack('styles')

</head>
<body class="hold-transition layout-top-nav">
<div class="wrapper">

  <nav class="main-header navbar navbar-expand-md navbar-white navbar-light">
    <div class="container">
      <a href="{{ url('/') }}" class="navbar-brand">
        <img src="{{ asset('img/logo2.png') }}" alt="災図" style="width: 38px; opacity: .85" class="brand-image mr-2">
      </a>

      <ul class="navbar-nav ml-auto">
        <li class="nav-item">
          <a href="{{ route('terms') }}" class="nav-link">利用規約</a>
        </li>
        <li class="nav-item">
          <a href="{{ route('login') }}" class="nav-link">ログイン</a>
        </li>
        @if (config('signup.enabled'))
          <li class="nav-item">
            <a href="{{ route('signup.create') }}" class="btn btn-primary btn-sm mt-2 ml-2">新規登録</a>
          </li>
        @endif
      </ul>
    </div>
  </nav>

  @yield('content')

  <footer class="public-footer">
    <div class="container d-flex flex-wrap justify-content-between">
      <span>災害支援情報共有システム「災図」</span>
      <span>
        <a href="{{ route('terms') }}">利用規約</a>
        <span class="mx-2">|</span>
        <a href="{{ route('login') }}">ログイン</a>
      </span>
    </div>
  </footer>

</div>

<script src="{{ asset('plugins/jquery/jquery.min.js') }}"></script>
<script src="{{ asset('plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('dist/js/adminlte.min.js') }}"></script>

@stack('scripts')

</body>
</html>
