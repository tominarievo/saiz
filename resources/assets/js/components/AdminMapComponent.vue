<template>

  <form>

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
              <td class="cell-w-200">状態</td>
              <td>
                <ul class="list-unstyled item-list item-list--col3 input-type-list">

                  <li v-for="(value, name) in statuses">
                    <label class="fancy-radio">
                      <input type="radio" v-model="status" :value="name"> <span><i></i>{{ value }}</span>
                    </label>
                  </li>

                </ul>
              </td>
            </tr>

            <tr>
              <td>公開日時</td>
              <td>
                <input class="form-control datetimepicker" v-model="publishedAt">
              </td>
            </tr>

            <tr>
              <td>タイトル<span class="label label-danger pull-right">必須</span></td>
              <td>
                <input class="form-control" v-model="title">
              </td>
            </tr>

            <tr>
              <td>説明文</td>
              <td>
                <textarea v-model="description" class='form-control'></textarea>
              </td>
            </tr>

            <tr>
              <td>出典</td>
              <td>
                <div v-for="source in sources" :index="source.url">

                  <dl class="form-group">
                    <dt>URL</dt>
                    <dd><input class="form-control" v-model="source.url"></dd>
                  </dl>

                  <dl class="form-group">
                    <dt>表示用テキスト</dt>
                    <dd><input class="form-control" v-model="source.label"></dd>
                  </dl>

                  <div class="form-group">
                    <button type="button" :disabled="!canDeleteSource" @click="removeSource(source)" class="btn btn-danger"><i class="fa fa-minus"></i> 削除</button>
                  </div>

                  <hr>
                </div>

                <div class="form-group">
                  <button type="button" class="btn btn-default" @click="addSource()"><i class="fa fa-plus"></i> 追加</button>
                </div>

              </td>
            </tr>

            <tr>
              <td>CSVから読み込む</td>
              <td>
                <input type="file" v-if="fileResetToggle" @change="changeFile">
              </td>
            </tr>

            <tr>
              <td>リソースから読み込む</td>
              <td>

                <span v-if="selectedResource">{{ selectedResource.text }}</span>

                <div v-if="isShownResourceSearch">

                  <input v-model="keyword" @change="changeKeyword()" class="form-control">

                  <div class="suggest-content">
                    <div class="suggest-body">
                      <ul>
                        <li v-for="(resource, index) in filteredResources" :index="index">
                          <a href="#" @click.prevent="selectResource(resource)">{{ resource.text }}</a>
                        </li>
                      </ul>
                    </div>

                    <div class="suggest-footer">
                      <ul class="suggest-pagination">
                        <li v-if="hasPrev"><a @click.prevent="onPrev" aria-label="前へ"><span aria-hidden="true"><i class="fa fa-angle-left" aria-hidden="true"></i></span></a></li>
                        <li v-if="hasNext"><a @click.prevent="onNext" aria-label="次へ"><span aria-hidden="true"><i class="fa fa-angle-right" aria-hidden="true"></i></span></a></li>
                      </ul>
                    </div>
                  </div><!-- /.modal-content -->

                  <div class="form-group">
                    <button type="button" class="btn btn-default" @click="loadResource"> リソース読み込み </button>
                  </div>
                </div>
                <div v-else>
                  <button type="button" class="btn btn-default" @click="showResourceSearch()"> リソースの再検索 </button>
                </div>
              </td>
            </tr>

            <tr>
              <td>サムネイル画像</td>
              <td>

                <input @change="changeImage" v-if="fileResetSwitch" type="file" name="image">

                <template v-if="currentUploadedImage">
                  <img :src="currentUploadedImage" width="250" />
                  <button type="button" @click="deleteNewImage()">画像を削除</button>
                </template>
                <template v-else-if="oldUploadedImage">
                  <img :src="oldUploadedImageUrl" width="250" />
                  <button type="button" @click="deleteOldImage()">画像を削除</button>
                </template>

                <input type="hidden" name="old_uploaded_image" :value="oldUploadedImage">

              </td>
            </tr>

          </tbody>
          </table>
        </div>

        <div class="panel">

          <table class="table table-bordered tbl-regist tbl-stripe">
            <thead>
            <tr class="tr-clr1">
              <th colspan="3" class="text-left">マップ</th>
            </tr>
            </thead>
            <tbody>

            <tr>
              <td v-if="columnData.length > 0" >緯度<span class="label label-danger pull-right">必須</span></td>
              <td v-if="columnData.length > 0" >

                <select v-model="lat" class="form-control">
                  <option v-for="(column, index) in columnData" :id="index" :value="index">
                    {{ column.colTitle }}
                  </option>
                </select>
              </td>

              <td rowspan="5">
                <div style="width:700px; height: 600px">

                  <div id="map"></div>

                </div>
              </td>

            </tr>

            <tr v-if="columnData.length > 0" >
              <td>経度<span class="label label-danger pull-right">必須</span></td>
              <td>
                <select v-model="lng" class="form-control">
                  <option v-for="(column, index) in columnData" :id="index" :value="index">
                    {{ column.colTitle }}
                  </option>
                </select>
              </td>
            </tr>

            <tr v-if="columnData.length > 0" >
              <td>ポップアップ内のタイトル<span class="label label-danger pull-right">必須</span></td>
              <td>
                <select v-model="makerTitleColumn" class="form-control">
                  <option v-for="(column, index) in columnData" :id="index" :value="index">
                    {{ column.colTitle }}
                  </option>
                </select>
              </td>
            </tr>

            <tr v-if="columnData.length > 0" >
              <td>ポップアップ内の説明文</td>
              <td>

                <template v-for="yColumn in yColumns">

                  <div class="form-group">
                    <select v-model="yColumn.value" @change="calcYMax()" class="form-control">
                      <option v-for="(column, index) in columnData" :id="index" :value="index">
                        {{ column.colTitle }}
                      </option>
                    </select>
                  </div>

                  <div class="form-group">
                    <button :disabled="!canDeleteYColumn" type="button" @click="removeY(yColumn)" class="btn btn-danger"><i class="fa fa-minus"></i> 削除</button>
                  </div>

                  <hr>

                </template>

                <div class="form-group">
                  <button :disabled="!canAddYColumn" type="button" class="btn btn-default" @click="addY()"><i class="fa fa-plus"></i> 追加</button>
                </div>
              </td>
            </tr>

            <tr v-if="columnData.length > 0" >
              <td>マップ詳細</td>
              <td>

                <div class="form-group">
                  <dl>
                    <dt>マップ中心の緯度経度</dt>
                    <dd>{{ center.lat }}, {{ center.lng }}</dd>
                  </dl>
                </div>

                <div class="form-group">
                  <dl>
                    <dt>ズーム率</dt>
                    <dd>{{ zoom }}</dd>
                  </dl>
                </div>

              </td>
            </tr>

            </tbody>
          </table>

          <div v-if="columnData.length > 0" class="form-group" style="text-align: center">
            <button type="button" class="btn btn-default" @click="refreshMap()">入力をマップに反映</button>
          </div>

        </div>

        <div v-if="columnData.length > 0" class="panel" style="overflow-x:scroll">


          <div>
            <table class="table table-bordered tbl-regist tbl-stripe">
              <thead>
              <tr class="tr-clr1">
                <th> </th>
                <th v-for="column in columnData" :id="'th'+column.colTitle">{{ column.colTitle }}</th>
              </tr>
              </thead>
              <tbody>
              <tr v-for="lineIndex in lineLength" v-show="isLineShown(lineIndex)" :id="'tr'+lineIndex">
                <td>
                  <input type="checkbox" v-model="checkedLine[lineIndex - 1].checked"></input>
                </td>
                <td v-for="colIndex in columnData.length" :id="'td'+colIndex">
                  <input v-model="columnData[colIndex - 1].data[lineIndex - 1]">
                </td>
              </tr>
              </tbody>

            </table>

            <div class="form-group" style="text-align: center">
              <button type="button" @click="displayCsvToggle()" class="btn btn-default">表示数切り替え</button>
            </div>

          </div>

        </div>
      </div>
    </div>




    <div class="pull-right">
      <button :disabled="columnData.length == 0" type="button" class="btn btn-primary" @click="save()">登録</button>
    </div>

  </form>


</template>
<script>

  import 'leaflet/dist/leaflet.css'
  import L from 'leaflet'

  // 参考 https://qiita.com/Satachito/items/929a63e9b266f6db7958
  delete  L.Icon.Default.prototype._getIconUrl

    export default {
      data() {
        return {
          // リソース検索
          page: 1,
          perPage: 10, //1ページ毎の表示件数
          totalPage: 1, //総ページ数
          count: 0, //itemsの総数
          keyword: '',
          prevKeyword: '', //直前のキーワードを保持しておく変数
          selectedResource: null,
          isShownResourceSearch: true,

          fileResetToggle:true, // input[type=file]をリセットするためのフラグ
          title: '',
          description: '',
          sources: [],
          statuses: {},
          status: 1,
          publishedAt: '',
          uploadFile: '',
          resourceId: '',
          resources: [],

          checkedLine: [], // グラフに使用するかどうかのフラグを保持する
          graphType: 'map',
          yColumns: [
            {
              value: 0
            }
          ],

          columnData: [],
          isShownAll: false,
          center: {
            lat: 34.340133,
            lng: 132.583943
          },

          markers: [],

          makerTitleColumn: '',
          lat: 0,
          lng: 0,
          zoom: 13,

          map: null,

          // サムネイルのアップロード
          uploadedImage: '',
          fileResetSwitch : true,
          oldUploadedImage: '',
          oldUploadedImageUrl : '',
          currentUploadedImage: ''
        }
      },
      methods: {
        selectResource(resource) {

          this.uploadFile = null

          // フラグをon/offすることでinput[type=file]をリセットする。
          this.fileResetToggle = false
          this.$nextTick(() => this.fileResetToggle = true)

          this.selectedResource = resource
          this.resourceId       = resource.value

          // ajaxでリソースをロード
          this.loadResource(this.resourceId)

          this.isShownResourceSearch = false
        },
        changeKeyword() {
          // this.page = 1
        },
        // 検索条件反映後にも対応するため、引数のリソースを使用してページ設定を行う。
        calcPages(resources) {
          this.totalPage = Math.ceil(resources.length / this.perPage)
          this.count     = resources.length
        },
        showResourceSearch() {
          this.isShownResourceSearch = true
        },
        onPrev() {
          this.page= Math.max(this.page- 1, 1);
        },
        onNext() {
          this.page= Math.min(this.page+ 1, this.totalPage);
        },
        addSource() {
          this.sources.push(
            {
              url: '',
              label: ''
            }
          )
        },
        removeSource(source) {
          // 指定した要素を削除する
          this.sources = this.sources.filter( el => el.url !== source.url )
        },
        displayCsvToggle() {
          this.isShownAll = !this.isShownAll
        },
        isLineShown(index) {
          if (this.isShownAll) {
            return true
          }

          return index <= 5
        },
        refreshMap() {

          // 標準のleafletのみ使用しているのでreactive処理は独自実装
          this.map.setZoom(this.zoom);



          // チェックされているラベルだけ取得する
          const titles = this.columnData[this.makerTitleColumn].data.filter( (_, index) => this.checkedLine[index].checked )
          const lats = this.columnData[this.lat].data.filter( (_, index) => this.checkedLine[index].checked )
          const lngs = this.columnData[this.lng].data.filter( (_, index) => this.checkedLine[index].checked )

          const contents = this.createDatasets()

          // リセット
          this.markers.forEach( marker => this.map.removeLayer(marker))

          // ひとまず緯度だけ回し、経度とタイトルはインデックスで取得する
          lats.forEach( (lat, index) => {

            const title = titles[index]

            const marker = L.marker(L.latLng(lat, lngs[index]))

            let values= []

            contents.forEach( column => {

              values.push(`[ ${column.label} ] ${column.data[index]} \n`)

            })

            console.log()

            marker.bindPopup(`<strong>${title}</strong><br>${values.join('<br>')}`)

            // 削除用に保持
            this.markers.push(marker)


            this.map.addLayer(marker)
          })


        },
        addY() {
          this.yColumns.push(
            {
              value: 0,
            }
          )
        },
        removeY(yColumn) {
          // 指定した要素を削除する
          this.yColumns = this.yColumns.filter( el => el.value !== yColumn.value )
        },
        createLabels() {

          if ( ! this.columnData[this.xColumn]) {
            return []
          }

          // チェックされているラベルだけ取得する
          return this.columnData[this.xColumn].data.filter( (_, index) => this.checkedLine[index].checked )
        },
        createDatasets() {

          if (this.columnData.length == 0) {
            return []
          }

          //y軸用のchart.js用データセットを作成する
          return this.yColumns.reduce((accumulator, current, index) => {

            const column = this.columnData[current.value]

            if (!column) {
              return accumulator
            }

            // チェックされているデータだけ取得する
            const checkedData = column.data.filter( (_, index) => this.checkedLine[index].checked)

            accumulator.push({
              label          : column.colTitle,
              data           : checkedData,
            })

            return accumulator
          }, [])
        },
        findLatLng() {

          this.lat = this.columnData.findIndex(column => {
            return column.colTitle == '緯度'
          })

          this.lng = this.columnData.findIndex(column => {
            return column.colTitle == '経度'
          })

          /*
           * 名称、住所もあれば出す
           */

          this.makerTitleColumn = this.columnData.findIndex(column => {
            return column.colTitle == '名称'
          })

          this.yColumns[0].value = this.columnData.findIndex(column => {
            return column.colTitle == '住所'
          })

          const yCol2Index = this.columnData.findIndex(column => {
            return column.colTitle == '電話番号'
          })

          if (yCol2Index) {
            this.yColumns.push({value:yCol2Index})
          }

        },
        loadStringToData($raw) {

          console.log($raw)

          const lines = $raw.split("\n");

          // 1行目ヘッダー行を使用して空のデータ置き場を作成する。
          let columnData = lines[0].split(',').reduce( (accumulator, current, index) => {

            if (current === '') {
              return accumulator
            }

            accumulator.push({
              colIndex: index,
              colTitle: current,
              data: []
            })

            return accumulator
          }, [])

          // ヘッダ行、空行以外を保持する
          lines.forEach( (line, lineIndex) => {

            if (lineIndex === 0 || line === '') {
              return
            }

            // 対象の列のみ保持
            line.split(',').forEach( (value, columnIndex) => {
              if (columnData[columnIndex]) {
                columnData[columnIndex].data.push(value)
              }
            })
          })

          return columnData

        },
        changeFile(e) {

          // リソース選択をリセット
          this.selectedResource = null
          this.resourceId       = ''

          new Promise((resolve, reject) => {

            let reader = new FileReader();

            // ファイル読み取りに失敗したとき
            reader.onerror = () => alert('ファイル読み取りに失敗しました')

            // ファイル読み取りに成功したとき
            reader.onload = (e) => {

              console.log(reader.result)

              resolve(this.loadStringToData(reader.result))


              // resolve(columnData)
            }

            // 選択されたファイルの情報を取得
            const files = e.target.files || e.dataTransfer.files;

            this.uploadFile = files[0]

            // ファイルの読み込み開始
            reader.readAsText(this.uploadFile);

          }).then( result => {

            this.columnData = result

            // 1列目のデータの行数を使ってcheckedLineを初期化 (どの列も行数は同じなので0を使用)
            this.checkedLine = this.columnData[0].data.map( (_, index) => ({ index:index, checked:true}) )

            this.findLatLng()
          });

        },
        loadResource(resourceId) {

          // リソースをロードする。
          axios.get(window.Laravel.baseUrl + '/graphs/get_resource/'+resourceId).then((res)=>{

            console.log(res.data)

            // ファイルアップロード時と同処理を行う。
            this.columnData = this.loadStringToData(res.data.data)

            // 1列目のデータの行数を使ってcheckedLineを初期化 (どの列も行数は同じなので0を使用)
            this.checkedLine = this.columnData[0].data.map( (_, index) => ({ index:index, checked:true}) )

            this.findLatLng()
          })

        },
        changeImage: function(e) {
          e.preventDefault();
          var files = e.target.files;
          this.uploadedImage = files[0];

          this.createImage(files[0]);
          console.log(this.uploadedImage)
        },
        // アップロードした画像を表示
        createImage: function(file) {

          this.deleteOldImage()

          var reader = new FileReader();

          // 退避
          var vueThis = this

          reader.onload = function (e) {
            vueThis.currentUploadedImage = e.target.result;
          };

          reader.readAsDataURL(file);
        },
        deleteOldImage: function() {
          this.oldUploadedImage = ''
        },
        deleteNewImage: function() {
          this.currentUploadedImage = ''
          this.fileResetSwitch = false

          this.$nextTick(function () {
            this.fileResetSwitch = true
          })

        },
        save: function () {

          if (this.title === '') {
            alert('タイトルは必須です')
            return
          }

          /*
           * computedのcomputedChartDataのJSON変換に失敗したので、
           * 同じ処理を行っている。
           */

          const datasets = this.graphType === 'bubble'
            ? this.createBubbleDatasets()
            : this.createDatasets()

          const chartData = {
            labels  : this.createLabels(),
            datasets: datasets
          }

          // FormData を利用して File を POST する
          let formData = new FormData();

          // 編集画面の場合は擬似的にPUTプロトコルであることを_methodで持つ
          if (window.Laravel.graph.managedData) {
            formData.append('_method', 'PUT')
          }

          formData.append('title', this.title);
          formData.append('description', this.description);
          formData.append('sources', JSON.stringify(this.sources));
          formData.append('status', this.status);
          formData.append('published_at', this.publishedAt);
          formData.append('file', this.uploadFile);
          formData.append('image', this.uploadedImage);
          formData.append('old_uploaded_image', this.oldUploadedImage);
          formData.append('_token', window.Laravel.csrfToken);
          formData.append('resource_id', this.resourceId);
          formData.append('graph_type', this.graphType);
          formData.append('managed_data', JSON.stringify({
            checkedLine: this.checkedLine,
            graphType  : this.graphType,
            resourceId : this.resourceId,
            makerTitleColumn    : this.makerTitleColumn,
            yColumns   : this.yColumns,
            lat       : this.lat,
            lng       : this.lng,
            zoom: this.zoom,
            columnData : this.columnData,
            center: this.center
          }));
          formData.append('graph_data', JSON.stringify({
            data: chartData,
            options: this.computedOptions
          }));

          let config = {
            headers: {
              'content-type': 'multipart/form-data'
            }
          };

          axios.post(window.Laravel.actionUrl, formData, config).then((res)=>{

            console.log('save')
            location.href = window.Laravel.redirectUrl

          })

        }
      },
      watch: {
        checkedLine:{
          handler: function (val, oldVal) {
            console.log('watch 1', 'newval: ', val, '   oldVal:', oldVal)
            this.refreshMap()
          },
          deep: true
        }
      },
      computed: {
        hasPrev() {
          return this.page > 1
        },
        hasNext() {
          return this.page < this.totalPage
        },
        filteredResources() {

          const resources = this.keyword === ''
            ? this.resources
            : this.resources.filter((element) => {
              return element.text.indexOf(this.keyword) > -1
            })

          // キーワードに変化があれば保持する。TODO: この処理をcomputedで行って良いのかどうか。
          if (this.keyword !== this.prevKeyword) {
            this.page = 1
            this.prevKeyword = this.keyword
          }

          // 全ページ数などを再計算
          this.calcPages(resources)


          return resources.slice((this.page - 1) * this.perPage, this.page * this.perPage);

        },
        canAddYColumn() {
          return true
        },
        canDeleteYColumn() {
          // ポップアップ内の説明文はないケースもあるので削除OKとする。
          return true
        },
        canDeleteSource() {
          return this.sources.length > 1
        },
        // 行数を取得する
        lineLength() {

          // どの要素も同じ要素数なので1つ目から取得する
          return this.columnData.length > 0
            ? this.columnData[0].data.length
            : 0
        },
        computedChartData() {

          const datasets = this.graphType === 'bubble'
            ? this.createBubbleDatasets()
            : this.createDatasets()

          return {
            labels  : this.createLabels(),
            datasets: datasets
          }
        },
        computedOptions() {

          return {}
        }
      },
      created() {
        this.resources = window.Laravel.resources
        this.statuses  = window.Laravel.statuses

        // リソース検索のページ情報の初期化
        this.calcPages(this.resources)

        // 出典の初期化
        this.addSource()

        L.Icon.Default.mergeOptions(
          {
            iconUrl         : window.Laravel.iconUrl,
            iconRetinaUrl   : window.Laravel.iconRetinaUrl,
            shadowUrl       : window.Laravel.shadowUrl,
          }
        )
      },
      mounted() {

        $(".datetimepicker").datetimepicker()

        // DatetimePickerの変更をvueが受け取れないので、jQuery側で値を直接vueに渡す。
        $('.datetimepicker').on('change', (e) => {
          this.publishedAt = $(e.target).val()
        })

        const graph = window.Laravel.graph

        if (graph) {
          this.status      = graph.status,
          this.publishedAt = graph.publishedAt

          if (graph.center) {
            this.center     = graph.center
          }
        }

        // 編集画面 (= managedDataがある)
        if (graph.managedData) {

          this.title       = graph.title
          this.description = graph.description
          this.sources     = graph.sources
          this.resourceId  = graph.resourceId
          this.graphType   = graph.graphType
          this.checkedLine = graph.managedData.checkedLine
          this.center     = graph.managedData.center
          this.yColumns    = graph.managedData.yColumns
          this.lat        = graph.managedData.lat
          this.lng        = graph.managedData.lng
          this.zoom = graph.managedData.zoom
          this.makerTitleColumn = graph.managedData.makerTitleColumn
          this.columnData  = graph.managedData.columnData

          this.selectedResource = this.resources.find((el) => {
            return el.value == this.resourceId
          })

          // リソース検索エリアを表示するかどうか
          if (this.selectedResource) {
            this.isShownResourceSearch = false
          }

          this.oldUploadedImage  = graph.oldUploadedImage,
          this.oldUploadedImageUrl = graph.oldUploadedImageUrl
        }

        this.map = L.map( 'map', { center: L.latLng( this.center.lat, this.center.lng ), zoom: this.zoom } )

        this.map.addLayer(
          L.tileLayer( 'http://{s}.tile.osm.org/{z}/{x}/{y}.png' )
        )

        this.map.on('moveend', () => {
          this.zoom       = this.map.getZoom()
          this.center.lat = this.map.getCenter().lat
          this.center.lng = this.map.getCenter().lng
        })
      }
    }
</script>
<style>
  #map {
    width: 100%;
    height: 100%;
  }
</style>
