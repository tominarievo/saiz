
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
        selectOne: function(selectedShelterId) {

          // 元画面にセット
          $('#all_shelter_select').val(selectedShelterId);

          console.log($('#all_shelter_select'))
          console.log("selectedShelterId: "+selectedShelterId)

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
