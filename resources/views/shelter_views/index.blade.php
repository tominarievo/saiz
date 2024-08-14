@extends('layouts.app')

@section('content')


  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0">支援先ビュー</h1>
          </div><!-- /.col -->

        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

  <!-- MAIN CONTENT -->
    <section class="content">
    <div class="container-fluid">

      {{-- エラーメッセージ --}}
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

      {!! Form::model($condition, ['route' => ['shelter_views.index'], 'id' => 'search_form' ,'class' => 'form-horizontal', 'method' => 'GET']) !!}

      <div class="row">

        <section class="col-lg-12 connectedSortable">

          <div class="card">

            <div class="card-body">

              <div class="row">

                <div class="col-sm-6">

                  <div class="form-group">
                    <label>支援先</label>

                    {{ Form::select('shelter_id', $shelters, null, ['id' => "shelter_id", 'class' => 'form-control select2', 'placeholder' => "選択してください"]) }}

                  </div>
                </div>

              </div>


            </div>

          </div>

          @if (filled($condition->shelter_id))
          <div class="card">
            <div class="card-body">

              <script src="/plugins/jQuery.Gantt-master/js/jquery.fn.gantt.js"></script>
              <link rel="stylesheet" type="text/css" href="/plugins/jQuery.Gantt-master/css/style.css">

              <style>
                .fn-gantt .leftPanel {
                  width: 300px;
                }

                .fn-gantt .leftPanel .fn-label {
                  width: 50%;
                }

                /* 表示エリアを未来に広げるためにセットしている隠し要素のbarを透明にする */
                .fn-gantt .ganttNone {
                  background-color: transparent;
                }

                /* アイコンの二重表示を防ぐ */
                .nav-link {
                  /*padding:.5rem 1rem*/
                  padding:.5rem .5rem
                }

              </style>


              <div class="row mb-4">
                <div class="col">
                  <label>支援内容</label>

                  {{ Form::select('support_category1_id', $support_category1_ids, null, ['id' => "support_category1_id", 'class' => 'form-control']) }}

                </div>
              </div>


              <div id="ganttChart"></div>


{{--              <div class="row">--}}
{{--                <div class="col-sm-6">--}}

{{--                  <div class="form-group">--}}
{{--                    <label>表示期間</label>--}}

{{--                    <div class="input-group">--}}
{{--                      {{ Form::text('gant_start_date', null, ['class' => 'form-control datepicker']) }}--}}
{{--                      <div class="input-group-append">--}}
{{--                        <span class="input-group-text">--}}
{{--                        から--}}
{{--                        </span>--}}
{{--                      </div>--}}
{{--                    </div>--}}

{{--                  </div>--}}
{{--                </div>--}}
{{--                <div class="col-sm-6">--}}
{{--                  <div class="form-group">--}}

{{--                    <label>　</label>--}}

{{--                    <div class="input-group">--}}
{{--                      {{ Form::text('gant_end_date', null, ['class' => 'form-control datepicker']) }}--}}
{{--                      <div class="input-group-append">--}}
{{--                        <span class="input-group-text">--}}
{{--                          まで--}}
{{--                        </span>--}}
{{--                      </div>--}}
{{--                    </div>--}}

{{--                  </div>--}}
{{--                </div>--}}
{{--              </div>--}}


              {{-- Vue.js --}}
              <div id="vue-app-vis3">
                <!-- 予定のModal -->
                <div class="modal" :style="activeStyle" id="planModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="">
                  <div class="modal-dialog" role="document">
                    <div class="modal-content" style="width:740px; margin-left: -70px;">
                      <div class="modal-body">

                        <div class="myPlanCotent">

                          <div class="mb-3 row">
                            <div class="col-6">
                              <h3>@{{ shelter.name }}</h3>
                              [支援団体]@{{ organization.name }}　@{{ from }} - @{{ to }}<br>
                            </div>
                            <div class="col-6">

                              <div class="row">
                                <div class="col">
                                  <div class="float-right">
                                    <a v-if="isEditable" :href="editUrl" type="button" class="btn btn-primary">編集</a>
                                    <button v-if="isEditable" type="button" @click="deletePlan()" class="btn btn-danger">削除</button>

                                    <button type="button" class="btn btn-secondary" @click="closeModal()"> 閉じる</button>

                                  </div>
                                </div>
                              </div>

                            </div>
                          </div>

                              <div>
                                <div v-html="messageWithBr"></div>
                                <br>
                                <dl>
                                  <dt>[支援種別]</dt>

                                  {{-- 中分類の表示 --}}
                                  <dd v-for="support1 in support_category_info">
                                  	<span>@{{ support1.name }}</span>
                                    <template v-for="support2 in support1.support2_list">
                                      <div>
                                        ・@{{ support2.name }} <br>
                                        <span v-html="support2.pivot.memo"></span>
                                      </div>
                                    </template>
                                  </dd>
                                </dl>

                              </div>

                          <div style="height: 400px; overflow-y: scroll">

                          <div class="mb-3 row">
                            <div class="col">
                              <div class="mb-1">
                                <textarea class="form-control" v-model="newComment" rows="5"></textarea>
                              </div>
                              <div class="float-right">
                                <button @click="registerComment" type="button" class="btn btn-primary" >コメントする</button>
                              </div>
                            </div>
                         </div>

                          <div v-for="comment in planComments" class="mb-3">
                            <i class="fa fa-user"></i>
                            @{{ comment.user.name }}

                            <span>
                            @{{ comment.user.organization_id ? "("+comment.user.organization.name+")" : "(事務局)" }}
                            </span>

                            @{{ comment.post_datetime }}

                            {{-- 時刻右の未読、既読のバッチ表示 --}}
                            <template v-if="readTarget(comment)">

                              {{-- プラン作成者は既読表示のみ --}}
                              <template v-if="comment.post_comment_read && comment.post_comment_read.read_status">
                                <span class="badge badge-success">既読</span>
                              </template>

                            </template>
                            <template v-if=" ! readTarget(comment)">

                              {{-- プラン作成者以外はラベル表示のみ --}}
                              <template v-if="(plan.organization.id != comment.organization_id)">

                                <template v-if=" ! comment.post_comment_read">
                                  <span class="badge badge-warning">未読</span><br>
                                </template>
                                <template v-if="comment.post_comment_read && comment.post_comment_read.read_status">
                                  <span class="badge badge-success">既読</span><br>
                                </template>

                              </template>

                            </template>

                            <br>

                            <p v-html="comment.comment"></p>

                            {{-- 既読にする操作はプラン作成者のみ --}}
                            <template v-if="readTarget(comment)">
                              <template v-if=" ! comment.post_comment_read">
                                <button type="button" @click="readComment(comment, true)" class="btn btn-sm btn-primary">既読にする </button>
                              </template>
                            </template>

                            <template v-if="(loginUserId == comment.user_id)">
                              <button @click="deleteComment(comment.id)" type="button" class="btn btn-default btn-sm">削除</button>
                            </template>

                          </div>

                          </div>

                        </div>

                      </div>

                    </div>
                  </div>
                </div>

              </div>





            </div>

          </div>
          @endif
        </section>


      </div>


      @if (filled($condition->shelter_id))
      <div class="row">

        <section class="col-lg-12 connectedSortable">

        <div class="card card-info">
          <div class="card-header">
            <h3 class="card-title">直近の活動状況</h3>
          </div>
          <!-- /.card-header -->
          <div class="card-body">

            <div class="">

              @foreach($timeline_reports as $timeline_report)
                <div class="card card-info">
                  <div class="card-body">

                    @if($timeline_report->table_type === 'plan_comments')

                      {{ $timeline_report->plan_comment->post_datetime }}
                      <i class="nav-icon fas fa-comment text-dark"></i> [
                      {{ $timeline_report->plan_comment->user->name }}
                      <i class="nav-icon fas fa-arrow-right"></i>
                      {{ $timeline_report->plan_comment->plan->organization->name }}
                      (
                      {{ $timeline_report->plan_comment->plan->getFromLabel() }} 開始の支援予定
                      )
                      ]

                     <br>

                     {{ \Illuminate\Support\Str::limit($timeline_report->comment, 250) }}

                    @else
                      <strong>
                        <a href="{{ route('reports.show', ['report' => $timeline_report->report->id]) }}">{{ $timeline_report->report->report_date }}</a>
                      </strong>

                      @foreach($timeline_report->report->getSupportCategoryInfo() as $support_category_info)

                        <span>
                          @if ($support_category_info["signal"] == 1)
                            <i class="nav-icon fas fa-flag text-info"></i>
                          @elseif ($support_category_info["signal"] == 2)
                            <i class="nav-icon fas fa-flag text-warning"></i>
                          @elseif ($support_category_info["signal"] == 3)
                            <i class="nav-icon fas fa-flag text-danger"></i>
                          @elseif ($support_category_info["signal"] == \App\Signal::NO_SIGNAL)
                            <i class="nav-icon fas fa-comment text-gray"></i>
                          @endif
                          {{ $support_category_info["name"] }}
                          </span>
                      @endforeach

                      [
                      {{ $timeline_report->report->organization->name }}
                      <i class="nav-icon fas fa-arrow-right"></i>
                      {{ $timeline_report->report->shelter->name }}
                      /{{ $timeline_report->report->disaster->name }}
                      ]
                      <br>

                      {{ \Illuminate\Support\Str::limit($timeline_report->comment, 250) }}

                    @endif

                  </div>
                </div>

              @endforeach

              @if (filled($timeline_reports))
                {{ $timeline_reports->links('vendor.pagination.bootstrap-4') }}
              @else
                  <div class="alert alert-dark">
                    <div class="pT-1 pB-1">
                      <strong>登録がないか、条件に一致するデータがありません。</strong>

                    </div>
                  </div>
              @endif

            </div>

          </div>


        </div>

        </section>

      </div>
      @endif

      {!! Form::close() !!}

    </div>
    <!-- END CONTAINER FLUID -->
  </div>
  <!-- END MAIN CONTENT -->

  </section>


  @component("components.plan_modal")@endcomponent


@endsection


@section('script')

  <script src="https://cdn.jsdelivr.net/npm/vue@2"></script>
  <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

  <script>

    const vueApp = new Vue({
      el: '#vue-app-vis3',
      data: function() {
        return {
          plan: {},
          loginUserId: {{ Auth::user()->id }},
          loginUserOrganizationId: {{ Auth::user()->organization_id ?: 'undefined' }},
          isAdmin: @json(blank(Auth::user()->organization_id)),
          id: "", // plan_id
          show: false,
          isEditable: false,
          isEdited: false, // 登録、削除が行われたかどうかのフラグ
          editUrl: "",
          deleteUrl: "",
          desc: "",
          shelter: {},
          organization: {},
          dateRange: "",
          planComments: [],
          editingCommentId: null,
          newComment: "",
          support_category_info: [],
        }
      },
      watch: {
      },
      methods: {
        /**
         * モーダルを閉じたあと再読み込み。
         */
        closeModal: function() {
          this.show = false;

          // 更新があった場合はコメント一覧の反映のため再読み込み
          if (this.isEdited) {
            location.reload();
          }
        },
        /**
         * 予定を削除する
         */
        deletePlan: function() {

          if ( ! confirm('削除します。よろしいですか？')){
            return false;
          }

          const dataPromise = this.doDeletePost();

          dataPromise.then((d) => {
            alert("予定を削除しました")
            this.show = false;
            location.reload();
          });

        },
        registerComment: function() {

          this.isEdited = true;

          const dataPromise = this.storeComment();

          dataPromise.then((d) => {

            this.planComments = d.planComments;

            this.newComment = "";
          });
        },
        readComment: function(comment, read_status) {

          const dataPromise = this.updateReadStatus(comment, read_status);

          dataPromise.then((d) => {

            comment.post_comment_read = {
              read_status: read_status
            }

          });





        },
        /**
         * APIを介して予定を削除する。
         * @returns {Promise<any|*[]>}
         */
        doDeletePost: async function(comment, read_status) {

          const params = {
            _method: "delete",
            from_page: "shelter_view",
          }

          const response = await axios.post(this.deleteUrl, params)

          if (response.data) {
            return response.data;
          }

          return [];
        },
        /**
         * APIを介してデータを取得する。
         * @returns {Promise<any|*[]>}
         */
        storeComment: async function() {

          const params = {
            plan_id: this.id,
            comment: this.newComment,
          }

          const response = await axios.post('{{ route('api.plan_comments.store') }}', params)

          if (response.data) {
            return response.data;
          }

          return [];
        },
        /**
         * APIを介してコメントを既読にする。
         * @returns {Promise<any|*[]>}
         */
        updateReadStatus: async function(comment, read_status) {

          const params = {
            comment_id: comment.id,
            read_status: read_status,
          }

          const response = await axios.post('{{ route('api.plan_comment_reads.update') }}', params)

          if (response.data) {
            return response.data;
          }

          return [];
        },
        deleteComment: function(commentId) {

          this.isEdited = true;

          const dataPromise = this.doDeleteComment(commentId);

          dataPromise.then((d) => {
            this.planComments = d.planComments;
          });
        },

        /**
         * コメントを削除する。
         * @returns {Promise<any|*[]>}
         */
        doDeleteComment: async function(commentId) {

          const params = {
            plan_id: this.id,
            commentId: commentId,
          }

          const response = await axios.post('{{ route('api.plan_comments.delete') }}', params)

          if (response.data) {
            return response.data;
          }

          return [];
        },
        /*
         * コメントの未読、既読を操作できるかどうかの判定
         */
        readTarget(comment) {
          return  ! this.isAdmin && (this.loginUserId != comment.user_id) && (this.loginUserOrganizationId == this.plan.organization.id);
        }
      },
      computed: {
        activeStyle: function() {
          return this.show ? "display: block"
            : "display: none"
        },
        // 改行を <br> タグに置換する算出プロパティ
        messageWithBr() {
          return this.desc ? this.desc.replace(/\n/g, '<br>') : "";
        },
      },
      mounted: function() {

      },
    })
  </script>

  <script>
    $(function () {
      $("#ganttChart").gantt({
        source: @json($sources),
        scrollToToday: true,
        navigate: "scroll",
        scale: "days",
        months : [
          "1月", "2月", "3月", "4月", "5月", "6月",
          "7月", "8月", "9月", "10月", "11月", "12月"
        ],
        dow:["日", "月", "火", "水", "木", "金", "土"],
        maxScale: "months",
        minScale: "days",
        itemsPerPage: 100,
        useCookie: false,
        onItemClick: function(data) {

          if (data.isReport) {
            location.href = data.reportSearchUrl;
            return;
          }

          var options = {};


          vueApp.plan         = data;
          vueApp.id           = data.id;
          vueApp.show         = true;
          vueApp.shelter      = data.shelter;
          vueApp.organization = data.organization;
          vueApp.planComments = data.planComments;
          vueApp.isEditable   = data.isEditable;
          vueApp.editUrl      = data.editUrl;
          vueApp.deleteUrl    = data.deleteUrl;
          vueApp.label        = data.label;
          vueApp.desc         = data.desc;
          vueApp.from         = data.from;
          vueApp.to           = data.to;
          vueApp.support_category_info = data.support_category_info;

        },
        onAddClick: function(dt, rowId) {
          var date = new Date(dt);
          var dateString = toISOStringWithTimezone(date)

          if (confirm("予定を追加しますか？")) {
            location.href = "{{ route("plans.create") }}?from="+dateString+"&shelter_id={{ $condition->shelter_id }}"
            return;
          }
        },
        onRender: function() {

        }
      });
    });

    function zeroPadding(s) {
      return ('0' + s).slice(-2);
    }

    // タイムゾーンを考慮した日付文字列を取得する。
    function toISOStringWithTimezone(date) {
      const year = date.getFullYear().toString();
      const month = zeroPadding((date.getMonth() + 1).toString());
      const day = zeroPadding(date.getDate().toString());
      return `${year}-${month}-${day}`;
    }

  </script>


  <script>

    $('#shelter_id').on("change", function() {
      $("#search_form").submit();
    });

    $('#support_category1_id').on("change", function() {
      $("#search_form").submit();
    });

  </script>

@endsection
