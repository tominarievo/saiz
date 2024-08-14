
@if(session('message'))
  <div class="alert alert-danger">
    {{ session('message') }}
  </div>
@endif

@if (session()->has('status'))
  <div class="alert alert-success">
    {{ session('status') }}
  </div>
@endif

@if (session()->has('success'))
  <div class="alert alert-success">
    {{ session('success') }}
  </div>
@endif
