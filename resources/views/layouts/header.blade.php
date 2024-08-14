<!-- Preloader -->
<nav class="main-header navbar navbar-expand navbar-white navbar-light">

  <ul class="navbar-nav">
    {{-- メニューの開閉(レスポンシブ時も使用される) --}}
    <li class="nav-item">
      <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
    </li>
  </ul>


  <ul class="navbar-nav ml-auto">

      <li class="nav-item dropdown">
        <a id="dropdownSubMenu1" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle">
          <i class="fas fa-user"></i>
          {{ filled(\Illuminate\Support\Facades\Auth::user()->organization_id) ? "[".\Illuminate\Support\Facades\Auth::user()->organization->name."]" : '[事務局]' }}
          {{ \Illuminate\Support\Facades\Auth::user()->name ?? 'サンプルユーザー' }}

        </a>

        <ul aria-labelledby="dropdownSubMenu1" class="dropdown-menu border-0 shadow" style="left: 0px; right: inherit;">

          @if ( ! Auth::user()->isAdmin())

            <li>
              <a href="{{ route('organizations.show', ['organization' => Auth::user()->organization_id]) }}" class="dropdown-item">
                  団体情報
              </a>
            </li>

          @endif


          <li>
            <a href="{{ route('password_changes.edit', ['password_change' => Auth::id() ?? 1 ]) }}" class="dropdown-item">パスワード変更</a>
          </li>

          <li class="dropdown-divider"></li>

          <li>
            <a href="#"  onclick="event.preventDefault();
                      document.getElementById('logout-form').submit();" class="dropdown-item"><i class="lnr lnr-exit"></i> ログアウト</a>
          </li>

        </ul>
      </li>

  </ul>

  <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
    @csrf
  </form>

</nav>
