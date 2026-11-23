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
    .saiz-ai-notice[hidden] {
      display: none;
    }

    .saiz-ai-notice {
      position: fixed;
      inset: 0;
      z-index: 20000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      background: rgba(15, 23, 42, 0.62);
    }

    .saiz-ai-notice__dialog {
      width: min(100%, 560px);
      padding: 1.4rem 1.5rem;
      color: #212529;
      background: #fff;
      border-radius: 0.75rem;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
    }

    .saiz-ai-notice__title {
      margin: 0 0 0.75rem;
      font-size: 1.15rem;
      font-weight: 700;
    }

    .saiz-ai-notice__body {
      margin: 0 0 0.75rem;
      font-size: 0.92rem;
      line-height: 1.7;
    }

    .saiz-ai-notice__question {
      margin: 0;
      font-weight: 700;
    }

    .saiz-ai-notice__actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      justify-content: flex-end;
      margin-top: 1.2rem;
    }

    .saiz-ai-notice__links {
      margin: 0.9rem 0 0;
      color: #6c757d;
      font-size: 0.78rem;
      text-align: right;
    }

    @media (max-width: 575.98px) {
      .saiz-ai-notice__dialog {
        padding: 1.1rem;
      }

      .saiz-ai-notice__actions .btn {
        flex: 1 1 100%;
      }
    }
  </style>

  @stack('styles')

</head>
<body class="map-app-body">

  @yield('content')

  <div id="saiz-ai-notice" class="saiz-ai-notice" hidden>
    <div
      class="saiz-ai-notice__dialog"
      role="dialog"
      aria-modal="true"
      aria-labelledby="saiz-ai-notice-title"
      aria-describedby="saiz-ai-notice-desc"
      tabindex="-1"
    >
      <h2 id="saiz-ai-notice-title" class="saiz-ai-notice__title">AI収集情報に関する注意</h2>
      <p id="saiz-ai-notice-desc" class="saiz-ai-notice__body">
        このサイトでは、災害情報をAIで収集・整理しています。内容に誤りがある可能性があります。必ず出典や一次情報を確認してください。
      </p>
      <p class="saiz-ai-notice__question">この注意を理解しましたか？</p>

      <div class="saiz-ai-notice__actions">
        <button type="button" id="saiz-ai-notice-yes" class="btn btn-primary">
          はい、理解しました
        </button>
        <button type="button" id="saiz-ai-notice-no" class="btn btn-outline-secondary">
          いいえ
        </button>
      </div>

      <p class="saiz-ai-notice__links">
        <a href="{{ route('terms') }}">利用規約</a>
        <span class="mx-1">/</span>
        <a href="{{ route('privacy') }}">プライバシーポリシー</a>
      </p>
    </div>
  </div>

  <script>
    (function () {
      var storageKey = 'saiz-ai-notice-v1';
      var notice = document.getElementById('saiz-ai-notice');

      if (!notice) {
        return;
      }

      var accepted = false;

      try {
        accepted = window.sessionStorage.getItem(storageKey) === '1';
      } catch (error) {
        accepted = false;
      }

      if (accepted) {
        notice.remove();
        return;
      }

      notice.hidden = false;
      document.body.style.overflow = 'hidden';

      var yesButton = document.getElementById('saiz-ai-notice-yes');
      var noButton = document.getElementById('saiz-ai-notice-no');

      if (yesButton) {
        yesButton.focus();
        yesButton.addEventListener('click', function () {
          try {
            window.sessionStorage.setItem(storageKey, '1');
          } catch (error) {
            // sessionStorageが使えない場合は、この画面だけ閉じる
          }

          notice.remove();
          document.body.style.overflow = '';
        });
      }

      if (noButton) {
        noButton.addEventListener('click', function () {
          window.location.href = 'https://www.google.com/';
        });
      }
    })();
  </script>

  @stack('scripts')

</body>
</html>
