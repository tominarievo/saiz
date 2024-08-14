@extends('layouts.app')

@section('content')
  <link href="{{ asset('assets/vendor/summernote/summernote-lite.min.css') }}" rel="stylesheet">
  <script src="{{ asset('assets/vendor/summernote/summernote-lite.min.js') }}"></script>
  <script>
    $(document).ready(function() {
      $('#summernote').summernote({
        height: 500,
        callbacks: {
          onImageUpload: function(files) {

            var editor = $(this)
            sendfile(files[0], editor);
          }
        }
      });

      $('#en_summernote').summernote({
        height: 500,
        callbacks: {
          onImageUpload: function(files) {

            var editor = $(this)
            sendfile(files[0], editor);
          }
        }
      });
    });

    function sendfile(fileData, editor) {

      var out = new FormData();
      out.append('file', fileData, fileData.name);

      $.ajax({
        data: out,
        dataType: 'JSON',
        type: 'POST',
        url: '{{ route('admin.wysiwyg_upload_file') }}',
        //check laravel document: https://laravel.com/docs/5.6/csrf#csrf-x-csrf-token
        headers: {'X-CSRF-TOKEN': '{{ csrf_token() }}'},
        cache: false,
        contentType: false,
        processData: false,
        success: function(r) {
          editor.summernote('insertImage', r.url);
        },
        error: function (jqXHR, textStatus, errorThrown) {
          console.error(textStatus + " " + errorThrown);
        }
      })

    }


  </script>

  <!-- MAIN CONTENT -->
  <div class="main-content">
    <div class="container-fluid">

      <p>必要事項を入力し、画面下のボタンを押してください。</p>

    {!! Form::model($page, ['route' => ['pages.update', $page->id]]) !!}
    @method('PUT')

    <!-- ROW -->
      <div class="row">
        <div class="col-md-12">
          <!-- BASIC TABLE -->
          <div class="panel">
            <table class="table table-bordered tbl-regist tbl-stripe">
              <thead>
              <tr class="tr-clr1">
                <th colspan="2" class="text-left">基本情報</th>
              </tr>
              </thead>
              <tbody>

              <tr>
                <td>タイトル</td>
                <td>
                  {{ $page->title }}
                </td>
              </tr>

              <tr>
                <td>本文 <span class="label label-danger pull-right">必須</span></td>
                <td>
                  {{ Form::textarea('content', null, ['id' => 'summernote', 'class' => 'form-control', 'rows' => '20']) }}
                  {{ Form::inputError('content') }}
                </td>
              </tr>

              <tr>
                <td>本文<span class="badge badge-info">英語</span> <span class="label label-danger pull-right">必須</span></td>
                <td>
                  {{ Form::textarea('en_content', null, ['id' => 'en_summernote', 'class' => 'form-control', 'rows' => '20']) }}
                  {{ Form::inputError('en_content') }}
                </td>
              </tr>

              </tbody>
            </table>
          </div>

          <!-- END BASIC TABLE -->
        </div>
      </div>
      <!-- END ROW -->


      <div class="pull-right">
        <button name="submit" class="btn btn-primary" value="submit">登録</button>
      </div>

      {!! Form::close() !!}
    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->

@endsection
