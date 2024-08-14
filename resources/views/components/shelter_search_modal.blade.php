
    <!-- 支援先選択のModal -->
    <div class="modal" id="shelterModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="">
      <div class="modal-dialog" role="document">
        <div class="modal-content" style="width:740px; margin-left: -70px;">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">支援先選択</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">

                {{-- 条件検索タブ --}}

                {{-- Vue.js --}}
                <div id="vue-app">

                  <form id="modal_form">

                <div class="row">

                  <div class="card" style="width: 100%">

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

                              {{ Form::select('prefecture_id', $prefectures, null, ['class' => 'form-control select2', 'placeholder' => "選択してください"]) }}

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

                      <button @click="search()" name="submit" class="btn btn-primary" type="button" value="submit"><i class="fas fa-search"></i> 検索</button>

                    </div>


                  </div>

                </div>

                  </form>

                  <table class="table table-bordered tbl-regist tbl-stripe">
                    <tr class="tr-clr1" style="background-color: #2b669a; color: #f2f2f2">
                      <th>市区町村</th>
                      <th>名称</th>
                      <th></th>
                    </tr>
                      <tr v-for="(shelter, index) in shelters">
                        <td>
                          @{{ shelter.npo_col_11 }} @{{ shelter.npo_col_12 }}
                        </td>
                        <td>
                          @{{ shelter.name }}
                        </td>
                        <td>
                          <button type="button" @click="selectOne(shelter.id)"
                                  class="btn btn-primary" data-dismiss="modal" data-shelter-id="">選択</button>
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




                  <br>
                  支援先を新しく追加する場合は<a href="{{ route('shelters.create', ['from' => 'reports']) }}">こちら</a>


            </div>


            </div>



          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">閉じる</button>
          </div>
        </div>
      </div>
    </div>
