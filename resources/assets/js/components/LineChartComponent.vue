

<script>

    import {Line, mixins} from 'vue-chartjs'
    const { reactiveProp } = mixins

    export default {
      extends: Line,
      mixins: [reactiveProp],
      props: ['options'],
      mounted() {
        console.log('Vue-chartjs Pie Component mounted.')
        this.renderChart(this.chartData, this.options)
      },
      watch: {
        // optionsの変更はmixinで検知されないので手動で再描画
        // https://vue-chartjs.org/ja/guide/#%E3%83%81%E3%83%A3%E3%83%BC%E3%83%88%E3%83%87%E3%83%BC%E3%82%BF%E3%81%AE%E6%9B%B4%E6%96%B0
        options () {
          console.log('optionsを検知')
          this.renderChart(this.chartData, this.options)
        }
      }
    }
</script>
