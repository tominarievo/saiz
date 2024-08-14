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

        <div v-if="columnData.length > 0" class="panel">

            <table class="table table-bordered tbl-regist tbl-stripe">
              <thead>
              <tr class="tr-clr1">
                <th colspan="3" class="text-left">グラフ</th>
              </tr>
              </thead>
              <tbody>

              <tr>

                <td class="cell-w-200">グラフ種別</td>

                <td>
                  <div class="form-group mb-4">
                  <select v-model="graphType" class="form-control">
                    <option v-for="graphTypeOption in graphTypes"
                            :id="graphTypeOption.value"
                            :value="graphTypeOption.value">
                      {{ graphTypeOption.text }}
                    </option>
                  </select>
                  </div>
                  <div v-if="(graphType == 'bar'　|| graphType == 'mixed'　|| graphType == 'horizontal-bar')">
                    <label><input v-model="isStackedBar" type="checkbox"> 積み上げ棒グラフ</label>

                    <label><input v-model="isHistogram" type="checkbox"> ヒストグラム形式</label>
                  </div>
                </td>

                <td rowspan="6">
                  <div style="max-width:600px">
                    <bar-chart-component v-if="(graphType == 'bar'　|| graphType == 'mixed')" :chart-data="computedChartData" :options="computedOptions"></bar-chart-component>
                    <horizontal-bar-chart-component v-if="graphType == 'band' || graphType == 'horizontal-bar'" :chart-data="computedChartData" :options="computedOptions"></horizontal-bar-chart-component>
                    <pie-chart-component v-if="graphType == 'pie'" :chart-data="computedChartData" :options="computedOptions" ></pie-chart-component>
                    <line-chart-component v-if="graphType == 'line'" :chart-data="computedChartData" :options="computedOptions" ></line-chart-component>
                    <bubble-chart-component v-if="graphType === 'bubble'" :chart-data="computedChartData" :options="computedOptions" ></bubble-chart-component>
                    <scatter-chart-component v-if="graphType == 'scatter'" :chart-data="computedChartData" :options="computedOptions" ></scatter-chart-component>
                    <radar-chart-component v-if="graphType == 'radar'" :chart-data="computedChartData" :options="computedOptions" ></radar-chart-component>
                  </div>
                </td>
            </tr>

            <tr>
              <td>{{ xScaleSectionTitle }}</td>
              <td>
                <select v-model="xColumn" class="form-control">
                  <option v-for="(column, index) in columnData" :id="index" :value="index">
                    {{ column.colTitle }}
                  </option>
                </select>
              </td>
            </tr>

            <tr>
              <td>
                <!-- 縦軸の要素ラベル -->
                {{ yScaleSectionTitle }}

                <div v-if="graphType === 'mixed' && isHistogram" class="alert alert-info">
                  ヒストグラムと組み合わせグラフの場合は、折れ線グラフを棒グラフよりも上側に設定します
                </div>
              </td>
              <td>

                <template v-for="(yColumn, yIndex) in yColumns">

                  <div v-if="graphType === 'scatter' || graphType === 'bubble'">
                    <span v-if="yIndex == 0">
                      <strong>横軸</strong>
                    </span>
                    <span v-else-if="yIndex == 1">
                      <strong>縦軸</strong>
                    </span>
                    <span v-else-if="yIndex == 2 && graphType === 'bubble'">
                      <strong>※ 円の大きさ</strong>
                    </span>
                  </div>

                  <div class="form-group">
                    <select v-model="yColumn.value" @change="calcYMax()" class="form-control">
                      <option v-for="(column, index) in columnData" :id="index" :value="index">
                        {{ column.colTitle }}
                      </option>
                    </select>
                  </div>

                  <div v-if="graphType === 'bar' || graphType === 'band' || graphType === 'line' || graphType === 'mixed' || graphType === 'horizontal-bar'" class="form-group">
                    <strong>色</strong>
                    <select v-model="yColumn.color" class="form-control">
                      <option v-for="(color, index) in colors" :id="index" :value="color.value">
                        {{ color.text }}
                      </option>
                    </select>
                  </div>

                  <!-- 組み合わせグラフ時のみ表示 -->
                  <div v-if="graphType === 'mixed'" class="form-group">
                    <select v-model="yColumn.mixGraphType" class="form-control">
                      <option v-for="graphTypeOption in mixGraphTypes"
                              :id="graphTypeOption.value"
                              :value="graphTypeOption.value">
                        {{ graphTypeOption.text }}
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

            <tr v-show="graphType !== 'pie' && graphType !== 'radar'">
              <td>横軸</td>
              <td>
                <input v-model="xScaleLabel" class="form-control">
                <br>

                <div v-if="graphType === 'scatter' || graphType === 'bubble' || graphType === 'horizontal-bar'">
                  <div class="form-group">
                    <dl>
                      <dt>最大値</dt>
                      <dd><input v-model.number="xMax" class="form-control"></dd>
                    </dl>
                  </div>

                  <div class="form-group">
                    <dl>
                      <dt>最小値</dt>
                      <dd><input v-model.number="xMin" class="form-control"></dd>
                    </dl>
                  </div>
                </div>
              </td>
            </tr>

            <tr v-show="graphType !== 'pie'">
              <td>
                縦軸
                <span v-if="graphType === 'mixed'">(左側)</span>
              </td>
              <td>

                <span v-if="graphType !== 'radar'">
                  <input v-model="yScaleLabel" class="form-control">
                  <br>
                </span>

                <span v-if="graphType !== 'band' && graphType !== 'horizontal-bar'">
                <div class="form-group">
                  <dl>
                    <dt>最大値</dt>
                    <dd><input v-model.number="yMax" class="form-control"></dd>
                  </dl>
                </div>

                <div class="form-group">
                  <dl>
                    <dt>最小値</dt>
                    <dd><input v-model.number="yMin" class="form-control"></dd>
                  </dl>
                </div>
                </span>

              </td>
            </tr>

            <tr v-if="graphType === 'mixed'">
              <td>縦軸(右側)</td>
              <td>
                <input v-model="yAxis2ScaleLabel" class="form-control">
                <br>

                <div class="form-group">
                  <dl>
                    <dt>最大値</dt>
                    <dd><input v-model.number="yAxis2Max" class="form-control"></dd>
                  </dl>
                </div>

                <div class="form-group">
                  <dl>
                    <dt>最小値</dt>
                    <dd><input v-model.number="yAxis2Min" class="form-control"></dd>
                  </dl>
                </div>

              </td>
            </tr>
            <tr v-else-if="graphType === 'bubble'">
              <td>円の大きさの基準</td>
              <td>

                <div class="form-group">
                  <dl>
                    <dt>割合を出す基準値</dt>
                    <dd><input v-model.number="yAxis2Max" class="form-control"></dd>
                  </dl>
                </div>

              </td>
            </tr>

            </tbody>
          </table>
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
          graphType: 'bar',
          yColumns: [
            {
              value: 0,
              color: '',
              mixGraphType: 'bar' // 組み合わせグラフで使用する
            }
          ],

          columnData: [],
          isShownAll: false,

          graphTypes: [
            { value: 'bar', text: '棒グラフ'},
            { value: 'horizontal-bar', text: '棒グラフ(横)'},
            { value: 'line', text: '折れ線グラフ'},
            { value: 'pie', text: '円グラフ'},
            { value: 'scatter', text: '散布図'},
            { value: 'band', text: '帯グラフ'},
            { value: 'mixed', text: '組み合わせグラフ'},
            { value: 'bubble', text: 'バブルチャート'},
            { value: 'radar', text: 'レーダーチャート'},
          ],
          colors: [
            { value: 0, text: 'ブルー'},
            { value: 1, text: 'レッド'},
            { value: 2, text: 'イエロー'},
            { value: 3, text: 'グリーン'},
            { value: 4, text: 'パープル'},
            { value: 5, text: 'オレンジ'},
          ],
          mixGraphTypes: [
              { value: 'bar', text: '棒グラフ'},
              { value: 'line', text: '折れ線グラフ'},
          ],
          // 積み上げ棒グラフにするかどうか
          isStackedBar: false,
          // ヒストグラム風表示にするかどうか
          isHistogram: false,
          xColumn: 0,

          xScaleLabel: 'X軸',
          xMin: 0,
          xMax: 100,

          yScaleLabel: 'Y軸',
          yMin: 0,
          yMax: 100,

          yAxis2ScaleLabel: 'Y軸(右)',
          yAxis2Min: 20,
          yAxis2Max: 100,

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
        addY() {
          this.yColumns.push(
            {
              value: 0,
              mixGraphType: 'bar'
            }
          )
        },
        removeY(yColumn) {
          // 指定した要素を削除する
          this.yColumns = this.yColumns.filter( el => el.value !== yColumn.value )
        },
        calcYMax() {

          let max = {max: 100}

          this.yColumns.reduce((accumulator, current, index) => {

            const max = Math.max.apply(null, this.columnData[current.value].data)

            // 大きければ最大値を変更
            accumulator.max = (max > accumulator.max) ? max : accumulator.max

            return accumulator

          }, max)

          this.yMax = max.max

        },
        createLabels() {

          if ( ! this.columnData[this.xColumn]) {
            return []
          }

          // チェックされているラベルだけ取得する
          return this.columnData[this.xColumn].data.filter( (_, index) => this.checkedLine[index].checked )
        },
        /*
         * レーダーチャート専用のlabelsを作成する。
         * 項目として選択した各列のタイトルを配列で返している。
         */
        createRadarLabels() {

          if (this.columnData.length == 0) {
            return []
          }

          return this.yColumns.reduce((accumulator, current, index) => {

            const column = this.columnData[current.value]

            accumulator.push(column.colTitle)

            return accumulator
          }, [])

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

            const color = this.getColor(this.graphType, current.color !== '' ? current.color : index)

            // チェックされているデータだけ取得する
            const checkedData = column.data.filter( (_, index) => this.checkedLine[index].checked)

            let data = {
              label          : column.colTitle,
              lineTension    : 0, //折れ線グラフ用
              fill           : false, //折れ線グラフ用
              data           : checkedData,

              backgroundColor: this.isHistogram ? color.borderColor : color.backgroundColor,
              borderColor    : color.borderColor,
              borderWidth    : 1
            }

            // 組み合わせグラフの場合
            if (this.graphType === 'mixed') {
              data.type    = current.mixGraphType
              data.yAxisID = current.mixGraphType == 'line' ? 'yAxis_2' : 'yAxis_1'
            }

            console.log(data)

            accumulator.push(data)

            return accumulator
          }, [])
        },
        /*
         * レーダーチャート専用のdatasetsを作成する。
         * 他のグラフとは異なり、列ではなく行を基準としている点に注意する。
         * 各行の中で、項目として選択されている列の値を配列でdataとしてdatasetsに持たせている。
         */
        createRadarDatasets() {

          if (this.columnData.length == 0) {
            return []
          }

          let ret = []

          for (let i = 0; i < this.lineLength; i++) {

            if ( ! this.checkedLine[i].checked) {
              continue
            }

            let lineData = []

            // その行の中で項目として選択された列の値のみを配列にする。
            this.yColumns.forEach(y => {
              lineData.push(this.columnData[y.value].data[i])
            })

            const color = this.getColor(this.graphType, i)

            ret.push(
              {
                label          : this.columnData[this.xColumn].data[i],
                data           : lineData,
                backgroundColor: color.backgroundColor,
                borderColor    : color.borderColor,
                borderWidth    : 1
              }
            )

          }


          return ret

        },
        createBubbleDatasets() {

          if (this.columnData.length == 0) {
            return []
          }

          // 散布図か、バブルチャートかによって軸の数が異なる。
          const yMax = this.graphType === 'bubble' ? 3 : 2

          while (this.yColumns.length < yMax) {
            this.addY()
          }

          // チェックされているデータだけ取得する
          const yCheckedData1 = this.columnData[this.yColumns[0].value].data.filter( (_, index) => this.checkedLine[index].checked)
          const yCheckedData2 = this.columnData[this.yColumns[1].value].data.filter( (_, index) => this.checkedLine[index].checked)

          if (this.yColumns.length == 3) {
            const yCheckedData3 = this.columnData[this.yColumns[2].value].data.filter( (_, index) => this.checkedLine[index].checked)
          }

          return this.createLabels().map((label, index) => {

            const value1 = yCheckedData1[index]
            const value2 = yCheckedData2[index]

            let y3Value = 5

            if (this.yColumns.length == 3) {
              const yCheckedData3 = this.columnData[this.yColumns[2].value].data.filter( (_, index) => this.checkedLine[index].checked)
              y3Value = yCheckedData3[index]
            }

            // 散布図の色は共通
            const color = this.getColor(this.graphType, 0)

            // バブルの円の直径を調整するための重み
            const WEIGHT = 50

            const backgroundColor = this.graphType === 'scatter' ? color.borderColor : color.backgroundColor

            return {
              label          : [label],
              data           : [{
                "x": value1,
                "y": value2,
                "r": Math.round( (y3Value / this.yAxis2Max * WEIGHT )),
                "bubbleValue": y3Value //tooltip用に保持する。
              }],
              backgroundColor: [backgroundColor],
            }
          })
        },
        getColor(graphType, index) {

          const colors = [
            '54, 162, 235',
            '255, 99, 132',
            '255, 206, 86',
            '75, 192, 192',
            '153, 102, 255',
            '255, 159, 64',

            '54, 162, 235',
            '255, 99, 132',
            '255, 206, 86',
            '75, 192, 192',
            '153, 102, 255',
            '255, 159, 64',

            '54, 162, 235',
            '255, 99, 132',
            '255, 206, 86',
            '75, 192, 192',
            '153, 102, 255',
            '255, 159, 64',

            '54, 162, 235',
            '255, 99, 132',
            '255, 206, 86',
            '75, 192, 192',
            '153, 102, 255',
            '255, 159, 64',

            '54, 162, 235',

          ].map(colorCode => ({borderColor: `rgba(${colorCode}, 1)`, backgroundColor: `rgba(${colorCode}, 0.2)`}))

          if (graphType === 'pie') {
            return {
              borderColor: colors.map(color => color.borderColor),
              backgroundColor: colors.map(color => color.backgroundColor),
            }
          }

          return colors[index] ? colors[index] : colors[0]
        },
        loadStringToData($raw) {


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

            // Y軸最大値を反映
            this.calcYMax()
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

            // Y軸最大値を反映
            this.calcYMax()
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

          let datasets;
          let labels;

          switch (this.graphType) {
            case 'scatter':
            case 'bubble':
              datasets = this.createBubbleDatasets()
              labels   = this.createLabels()
              break
            case 'radar':
              datasets = this.createRadarDatasets()
              labels   = this.createRadarLabels()
              break
            default:
              datasets = this.createDatasets()
              labels   = this.createLabels()
              break
          }

          const chartData = {
            labels  : labels,
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
            xColumn    : this.xColumn,
            yColumns   : this.yColumns,
            xScaleLabel: this.xScaleLabel,
            xMin       : this.xMin,
            xMax       : this.xMax,
            yScaleLabel: this.yScaleLabel,
            yMin       : this.yMin,
            yMax       : this.yMax,
            yAxis2ScaleLabel: this.yAxis2ScaleLabel,
            yAxis2Min       : this.yAxis2Min,
            yAxis2Max       : this.yAxis2Max,
            columnData : this.columnData,
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
          switch (this.graphType) {
            case 'scatter':
              return this.yColumns.length < 2
            case 'bubble':
              return this.yColumns.length < 3
            case 'pie':
              return this.yColumns.length < 1
            default:
              return true
          }
        },
        canDeleteYColumn() {
          switch (this.graphType) {
            case 'scatter':
              return this.yColumns.length > 2
            case 'bubble':
              return this.yColumns.length > 3
            default:
              return this.yColumns.length > 1
          }
        },
        canDeleteSource() {
          return this.sources.length > 1
        },
        xScaleSectionTitle() {
          switch (this.graphType) {
            case 'pie':
            case 'scatter':
            case 'bubble':
            case 'radar':
              return '凡例'
            case 'band':
            case 'horizontal-bar':
              return '縦軸の要素'
            default:
              return '横軸の要素'
          }
        },
        yScaleSectionTitle() {
          switch (this.graphType) {
            case 'pie':
            case 'scatter':
            case 'radar':
            case 'band':
            case 'horizontal-bar':
              return '項目'
            default:
              return '縦軸の要素'
          }
        },
        // 行数を取得する
        lineLength() {

          // どの要素も同じ要素数なので1つ目から取得する
          return this.columnData.length > 0
            ? this.columnData[0].data.length
            : 0
        },
        computedChartData() {

          if (this.graphType === 'scatter' || this.graphType === 'bubble') {
            return {
              labels  : this.createLabels(),
              datasets: this.createBubbleDatasets()
            }
          } else if (this.graphType === 'radar') {
            return {
              labels  : this.createRadarLabels(),
              datasets: this.createRadarDatasets()
            }
          } else {
            return {
              labels  : this.createLabels(),
              datasets: this.createDatasets()
            }
          }

        },
        computedOptions() {

          if (this.graphType === 'pie') {
            return {}
          }

          // 散布図は凡例を表示させない。
          const isLegendDisplayed = (this.graphType !== 'scatter' &&  this.graphType !== 'bubble')

          const isStacked = (this.graphType == 'bar' || this.graphType == 'mixed' || this.graphType == 'horizontal-bar')
            ? this.isStackedBar
            : false

          const isYAxis2Shown = this.graphType == 'mixed'

          const isAxisShown = (this.graphType !== 'radar')

          let option = {
            responsive: true,
            plugins: {
              // 帯グラフ用のプラグイン
              stacked100: {
                enable: (this.graphType === 'band'),
                replaceTooltipLabel: false
              }
            },
            legend: {
              display: isLegendDisplayed
            },
            scales: {
              xAxes: [{
                display: isAxisShown,
                stacked: isStacked, //積み上げ棒グラフにする設定
                barPercentage: this.isHistogram ? 1.3 : 1.0, // 縦棒グラフ用ヒストグラムは幅を太くする
                scaleLabel: {
                  display: true,
                  labelString: this.xScaleLabel
                },
                // 散布図、バブルチャート、帯グラフで使用する
                ticks: {
                  min: (this.graphType !== 'band') ? this.xMin : 0,
                  max: (this.graphType !== 'band') ? this.xMax : 100
                }
              }],
              yAxes: [
                {
                  stacked: isStacked, //積み上げ棒グラフにする設定
                  barPercentage: this.isHistogram ? 1.3 : 1.0, // 横棒グラフ用ヒストグラムは幅を太くする
                  id: "yAxis_1",
                  display: isAxisShown,
                  scaleLabel: {
                    display: true,
                    labelString: this.yScaleLabel
                  },
                  ticks: {
                    min: this.yMin,
                    max: this.yMax
                  }
                },
                {
                  id: "yAxis_2",
                  display: isYAxis2Shown, //組み合わせのみ右側の軸を表示
                  position: "right",
                  scaleLabel: {
                    display: true,
                    labelString: this.yAxis2ScaleLabel
                  },
                  ticks: {
                    min: this.yAxis2Min,
                    max: this.yAxis2Max
                  }
                },
              ]
            }
          }

          // 散布図、バブルチャート専用のツールチップ。
          // バブルチャートの場合、rの値がそのまま表示されてしまうのを防ぐために独自にセットしたbubbleValueを表示している。
          if (this.graphType === 'bubble' || this.graphType === 'scatter') {
            option.tooltips = {
              callbacks: {
                label: function(tooltipItem, data) {

                  const label = data.datasets[tooltipItem.datasetIndex].label || '';

                  let value = tooltipItem.xLabel+', '+tooltipItem.yLabel

                  if (this.graphType === 'bubble') {
                    value += ", " + data.datasets[tooltipItem.datasetIndex].data[0].bubbleValue
                  }

                  return label + " [" +value + ']'
                }
              }
            }
          }

          if (this.graphType === 'radar') {
            option.scale = {
              angleLines: {
                display: false
              },
              ticks: {
                suggestedMin: this.yMin,
                suggestedMax: this.yMax
              }
            }
          }

          return option
        }
      },
      created() {
        this.resources = window.Laravel.resources
        this.statuses  = window.Laravel.statuses

        // リソース検索のページ情報の初期化
        this.calcPages(this.resources)

        // 出典の初期化
        this.addSource()
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
        }

        // 編集画面 (= managedDataがある)
        if (graph.managedData) {

          this.title       = graph.title
          this.description = graph.description
          this.sources     = graph.sources
          this.resourceId  = graph.resourceId
          this.graphType   = graph.graphType
          this.checkedLine = graph.managedData.checkedLine
          this.xColumn     = graph.managedData.xColumn
          this.yColumns    = graph.managedData.yColumns

          this.xScaleLabel = graph.managedData.xScaleLabel
          this.xMin        = graph.managedData.xMin
          this.xMax        = graph.managedData.xMax

          this.yScaleLabel = graph.managedData.yScaleLabel
          this.yMin        = graph.managedData.yMin
          this.yMax        = graph.managedData.yMax

          this.yAxis2ScaleLabel = graph.managedData.yAxis2ScaleLabel
          this.yAxis2Min        = graph.managedData.yAxis2Min
          this.yAxis2Max        = graph.managedData.yAxis2Max

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
      }
    }
</script>
