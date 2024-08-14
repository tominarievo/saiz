
<!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <!-- Brand Logo -->
  <a href="{{ route('home') }}" class="brand-link">
    <img src="/img/logo2.png" style="width: 50px; opacity: .8" alt="AdminLTE Logo" class="brand-image img-circle elevation-3">
    <span class="brand-text font-weight-light">情報共有システム</span>
  </a>

  <!-- Sidebar -->
  <div class="sidebar">

    <!-- Sidebar Menu -->
    <nav class="mt-2">

      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <!-- Add icons to the links using the .nav-icon class
             with font-awesome or any other icon font library -->

        @if (Auth::user()->isAdmin() && ( ! Auth::user()->is_writer))
        <li class="nav-header">メニュー</li>

        <li class="nav-item">
          <a href="{{ route('information.index') }}" class="nav-link">
            <i class="nav-icon fas fa-info"></i>
            <p>
              お知らせ管理
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('organizations.index') }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              支援団体
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('disasters.index') }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              災害情報
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('seeds.index') }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              支援種別
            </p>
          </a>
        </li>


        <li class="nav-item">
          <a href="{{ route('admin_users.index') }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              管理ユーザー
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('support_category1s.index') }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              支援種別マスタ
            </p>
          </a>
        </li>
        @endif

        @if ( ! Auth::user()->isAdmin())

          <li class="nav-header">メニュー</li>

          <li class="nav-item">
            <a href="{{ route('organizations.index') }}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                支援団体
              </p>
            </a>
          </li>

        @endif

        @if ( ! Auth::user()->is_writer)
        <li class="nav-item">
          <a href="{{ route('shelters.index') }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              支援先
            </p>
          </a>
        </li>
        @endif


        <li class="nav-item">
          <a href="{{ route('reports.index')."?from=menu" }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              支援団体日報
            </p>
          </a>
        </li>

        <li class="nav-item">
          <a href="{{ route('overview')."?from=menu" }}" class="nav-link">
            <i class="nav-icon fas fa-th"></i>
            <p>
              支援概況
            </p>
          </a>
        </li>

        @if ( ! Auth::user()->is_writer)
          <li class="nav-item">
            <a href="{{ route('organization_views.index') }}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                支援団体ビュー
              </p>
            </a>
          </li>
          <li class="nav-item">
            <a href="{{ route('shelter_views.index') }}" class="nav-link">
              <i class="nav-icon fas fa-th"></i>
              <p>
                支援先ビュー
              </p>
            </a>
          </li>
        @endif

        @if ( ! Auth::user()->isAdmin())
          <li class="nav-item">
            <a href="/manual/manual.html" target="_blank" class="nav-link">
              <i class="nav-icon fas fa-book"></i>
              <p>
                マニュアル
              </p>
            </a>
          </li>
        @else
          <li class="nav-item">
            <a href="/manual/admin_manual.html" target="_blank" class="nav-link">
              <i class="nav-icon fas fa-book"></i>
              <p>
                管理者マニュアル
              </p>
            </a>
          </li>
        @endif

        @php
            // TODO 暫定の左メニュー用の日付。AppServiceProvider内の処理と一致させる必要があるので注意。
            $menu_date = \Illuminate\Support\Carbon::today();
        @endphp

        <li class="nav-header">支援先の状態({{ $menu_date->format('m/d') }})</li>
        @foreach($signal_count as $signal_info)
        <li class="nav-item">
          <a href="{{ route('shelters.index', ["signal_id" => $signal_info['id'], "start_date" => $menu_date->format('Y/m/d'), "end_date" => $menu_date->format('Y/m/d')]) }}" class="nav-link">
            <i class="nav-icon fas fa-flag text-{{ $signal_info["css_class"] }}"></i>
            <p class="text">{{ $signal_info["label"] }}<span class="right badge badge-{{ $signal_info["css_class"] }}">{{ $signal_info["count"] }}</span></p>
          </a>
        </li>
        @endforeach


      </ul>
    </nav>
    <!-- /.sidebar-menu -->
  </div>
  <!-- /.sidebar -->
</aside>
