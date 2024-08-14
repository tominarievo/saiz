@extends('layouts.app')

@section('content')

  @php

    $disaster_types = \App\DisasterType::get();
    $all_shelters_for_modal = \App\Shelter::get();

  @endphp

    <!-- 支援先選択のModal -->
    <div class="" id="shelterModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="">
      <div class="modal-dialog" role="document">
        <div class="modal-content" style="width:740px; margin-left: -70px;">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">支援先選択</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">

            <ul class="nav nav-tabs" id="myTab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="home-tab" data-toggle="tab" href="#home" role="tab" aria-controls="home" aria-selected="true">条件検索</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="profile-tab" data-toggle="tab" href="#profile" role="tab" aria-controls="profile" aria-selected="false">地図検索</a>
              </li>
            </ul>


            <ul class="tab-content" id="myTabContent">
              <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">

                {{-- 条件検索タブ --}}

                {{-- Vue.js --}}
                <div id="vue-app">

                  <form id="modal_form">

                <div class="row">

                  <div class="card">

                    <div class="card-body">

                      <div class="row">
                        <div class="col-sm-6">

                          <div class="form-group">
                            <label>キーワード</label>

                            <div class="input-group">
                              {{ Form::text('keyword', null, ['class' => 'form-control']) }}
                            </div>

                          </div>
                        </div>
                        <div class="col-sm-6">
                          <div class="form-group">

                            <label>都道府県</label>

                            <div class="form-group">

                              {{ Form::select('prefecture_id', $prefectures, null, ['class' => 'form-control', 'placeholder' => "選択してください"]) }}

                            </div>

                          </div>
                        </div>
                      </div>


                      <div class="row">
                        <div class="col-sm-12">

                          <div class="form-group">
{{--                            <label>災害種別</label>--}}

                            <div class="input-group">

{{--                              @foreach($disaster_types as $disaster_type)--}}
{{--                                <div class="form-check">--}}

{{--                                  <label class="form-check-label">--}}

{{--                                    {{ Form::checkbox('disaster_type_ids[]', $disaster_type->id, null, ['class' => '', 'id' => '']) }}--}}

{{--                                    {{ $disaster_type->name }}--}}

{{--                                  </label>--}}
{{--                                </div>--}}
{{--                              @endforeach--}}

                            </div>

                          </div>
                        </div>

                      </div>

                    </div>
                    <div class="card-footer">

                      <button @click="search()" name="submit" class="btn btn-default" type="button" value="submit"><i class="fas fa-search"></i> 検索</button>

                    </div>

                </form>

                  </div>

                </div>

                  <table class="table table-bordered tbl-regist tbl-stripe">
                    <tr class="tr-clr1" style="background-color: #2b669a; color: #f2f2f2">
                      <th>住所</th>
                      <th>名称</th>
                      <th></th>
                    </tr>
                      <tr v-for="(shelter, index) in shelters">
                        <td>
                          @{{ shelter.name }}
                        </td>
                        <td>

                        </td>
                        <td>
                          <button type="button" @click="selectOne(shelter.id)"
                                  class="modal-shelter-select-btn btn btn-default" data-dismiss="modal" data-shelter-id="">選択</button>
                        </td>
                      </tr>
                  </table>

              <!-- ページネーションの表示 -->
              <ul v-if="(res.last_page > 1)" class="pagination" role="navigation">
                <template v-for="link in res.links">
                  <li class="page-item" :class="{
                    active : link.active
                  }">
                  <a
                    href="#"
                    :disabled="link.active || ( ! link.url)"
                    class="page-link"
                    @click="onClickLink(link.url)"
                  >

                    <span v-html="link.label"></span>
                  </a>
                  </li>
                </template>
              </ul>



            </div>

              </div>
              <div class="tab-pane fade" id="profile" role="tabpanel" aria-labelledby="profile-tab">

                {{-- 地図検索タブ --}}

                <p><small>※背景地図をマウスで動かしてピンの位置をスポットにあわせてください。あわせた位置の緯度経度を自動的に取得します。</small></p>

                <div id="map" style="width:700px;height:500px"></div>

              </div>
            </div>




            <br>
            支援先を新しく追加する場合は<a href="{{ route('shelters.create', ['from' => 'reports']) }}">こちら</a>


          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">閉じる</button>
          </div>
        </div>
      </div>
    </div>




    @endsection

@section('script')

  <script src="https://cdn.jsdelivr.net/npm/vue@2.5.16/dist/vue.js"></script>

  <script>

    new Vue({
      el: '#vue-app',
      data: function() {
        return {
          resultFlg: false,
          shelters: [],
          res: {}
        }
      },
      methods: {
        search: function(argUrl = undefined) {

          var vueThis = this

          var url = argUrl ? argUrl : "/api/shelters/index";

          console.log(url)

          {{-- TODO FormDataを使ってもう少しスマートに取れないか --}}

          var formData = {};
          formData.keyword       = $('input[name="keyword"]').val();
          formData.prefecture_id = $('select[name="prefecture_id"]').val();

          // formData.append('keyword', $('input[name="keyword"]').val())
          // formData.append('prefecture_id', $('select[name="prefecture_id"]').val())


          $.get(url, formData).done(function(res) {

            console.log(res.data)

            if (res.data.length != 0) {

              vueThis.res = res
              vueThis.shelters = res.data

            } else {
              vueThis.res = {}
              vueThis.shelters = []
            }



          });

        },
        selectOne: function(shelter_id) {

          // TODO: 選択処理

        },
        onClickLink: function(url) {

          this.search(url)

        },
      },
      created: function() {

        this.search();

      },
      computed: {

      }
    })
  </script>

@endsection
