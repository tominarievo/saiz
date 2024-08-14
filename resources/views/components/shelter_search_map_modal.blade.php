
    <!-- 支援先選択のマップModal -->
    <div class="modal fade" id="shelterMapModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="">
      <div class="modal-dialog" role="document">
        <div class="modal-content" style="width:740px; margin-left: -70px;">
          <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">支援先選択</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">

            {{-- 地図検索 --}}
            <div id="map" style="width:700px;height:500px"></div>

          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">閉じる</button>
          </div>
        </div>
      </div>
    </div>
