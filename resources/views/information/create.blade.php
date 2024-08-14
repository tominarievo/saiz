@extends('layouts.app')

@section('content')

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">お知らせ</h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- MAIN CONTENT -->
    <section class="content">
      <div class="container-fluid">

        @component("components.session_message") @endcomponent

    <!-- ROW -->
      <div class="row">

        <section class="col-lg-12 connectedSortable">

          <div class="card card-info">
            <div class="card-header">
              <h3 class="card-title">お知らせ
                新規作成
              </h3>
            </div>

          {!! Form::model($entity, ['route' => ['information.store'], 'id' => 'input-form', 'class' => 'form-horizontal', 'enctype'=>'multipart/form-data']) !!}

              {{ Form::hidden('file_data', null) }}

            <!-- /.card-header -->
            <div class="card-body">

              <p>必要事項を入力し、画面下のボタンを押してください。</p>

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  ステータス
                </label>

                <div class="col-sm-10">
                  <label for="status" class="">
                    {{ Form::checkbox('status', true, null, ['class' => '', 'id' => 'status']) }} 公開
                  </label>

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  公開日
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">
                  {{ Form::text('published_at', $entity->formatted_published_at, ['class' => 'form-control datepicker']) }}
                  {{ Form::inputError('published_at') }}
                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  タイトル
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::text('title', null, ['class' => 'form-control']) }}
                  {{ Form::inputError('title') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  本文
                  <span class="right badge badge-danger">必須</span>
                </label>

                <div class="col-sm-10">

                  {{ Form::textarea('content', null, ['class' => 'form-control summernote-editor', "rows" => 10]) }}
                  {{ Form::inputError('content') }}

                </div>
              </div>
              {{-- / 1入力項目 --}}


              {{-- 1入力項目 --}}
              <div class="form-group row">

                <label for="inputEmail3" class="col-sm-2 col-form-label">
                  画像
                </label>

                <div class="col-sm-10">

                  {{-- Vueで制御 --}}
                  <div id="vueApp" class="col-12 contents-item notification-detail">

                    <div class="notification" v-if="errors">
                      <div class="alert alert-danger" role="alert">
                        <span v-for="error in errors">
                          <i class="far fa-times-circle me-2"></i>@{{ error[0] }}<br>
                        </span>
                      </div>
                    </div>


                    <span v-for="(uploadedFile, index) in uploadedFiles">
                            <div class="input-group mb-2">
                              <input type="file" class="form-control" v-on:change="fileSelected('sample', index)">
                            {{-- 削除ボタン --}}
                            <button @click="removeUploadForm(index)" type="button" class="btn btn-outline-secondary"><i class="fas fa-trash"></i> 削除する</button>
                            </div>

                            <div v-if="uploadedFile.file_path" class="mb-2">
                              <img :src="'/file_download?type=dataset&id=&file_path=' + uploadedFile.file_path + '&file_name=' + uploadedFile.file_name" style="width: 350px;" /><br />
                              @{{ uploadedFile.file_name }}
                            </div>

                          </span>

                    <div v-if="uploadedFiles.length < file_max" class="mt-2 pt-2 border-top-dashed">
                      <button type="button" class="btn btn-outline-primary" @click="addUploadForm"><i class="fas fa-plus"></i> 添付ファイルを追加</button>
                    </div>
                  </div>

                </div>
              </div>
              {{-- / 1入力項目 --}}


            </div>

            <div class="card-footer">

              <button class="btn btn-info" value="submit">保存</button>
            </div>

            {!! Form::close() !!}

            <!-- END BASIC TABLE -->
        </div>
      </div>
      <!-- END ROW -->
      </div>
    </section>
  </div>

@endsection

@section('script')

  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <script>

    var app = new Vue({
      el: '#vueApp',
      data: {
        file_max: 1,
        errors: null,
        uploadedFiles: {!! $entity->file_data ?? "[]" !!},
      },
      mounted() {

        if (this.uploadedFiles.length === 0) {
            this.addUploadForm();
        }

      },
      methods: {
        addUploadForm: function () {

          var self = this;

          self.uploadedFiles.push({
            index: '',
            file_path: "",
            file_name: "",
          });

        },
        removeUploadForm: function (index) {
          var self = this;

          // リアクティブとするためspliceを使用する。
          self.uploadedFiles.splice(index, 1);  // => インデックス0, 1の要素を含む配列が戻り値

          self.syncHidden()
        },
        /**
         * hidden要素を同期
         */
        syncHidden: function() {

          var self = this;

          $('input[name="file_data"]').val(JSON.stringify(self.uploadedFiles))

        },
        fileSelected: function(inputKey, index){

          var self = this;

          self.fileUpload(inputKey, index, event.target.files[0])
        },
        fileUpload: function(inputKey, index, uploadFileInfo){
          var self = this;

          var formData = new FormData()

          formData.append('file', uploadFileInfo)
          formData.append('file_type', 'information')

          axios.post(
            '{{ route('upload.information.file') }}',
            formData,
            {headers: {'X-CSRF-TOKEN': $('input[name=\"_token\"]').val()}}
          ).then(function(response) {

            // 配列の要素を置き換える
            self.uploadedFiles.splice(index, 1, {
              index: index,
              file_path: response.data.file_path,
              file_name: response.data.file_name,
            })

            self.syncHidden();

          }).catch(function(error) {

            self.errors    = error.response.data.errors;
          });
        }
      }
    })

  </script>
@endsection
