@if ($errors->has($name))

  <span class="error invalid-feedback" style="display: block">{{ $errors->first($name) }}</span>

  <script>
    $('input[name="{{ $name }}"], select[name="{{ $name }}"], textarea[name="{{ $name }}"]').addClass('is-invalid');
  </script>

@endif
