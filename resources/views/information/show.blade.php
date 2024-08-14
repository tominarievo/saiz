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
                <h3 class="card-title">{{ $entity->title }}
                </h3>
              </div>

              {{ Form::hidden('file_data', null) }}

            <!-- /.card-header -->
            <div class="card-body">

              {{-- 1入力項目 --}}
              <div class="form-group row">

                <div class="col-sm-12">

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

                            <div v-if="uploadedFile.file_path" class="mb-2">
                              <img :src="'/file_download?type=dataset&id=&file_path=' + uploadedFile.file_path + '&file_name=' + uploadedFile.file_name" style="width: 350px;" /><br />

                            </div>

                          </span>

                  </div>

                </div>
              </div>
              {{-- / 1入力項目 --}}

              <hr>


              {!! $entity->getEditedContent() !!}



            </div>

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
