<?php

namespace App\Http\Controllers;
use App\Prefecture;
use App\Disaster;
use Illuminate\Support\Facades\View;
use App\LocalGovernment;
use Illuminate\Http\Request;
use App\Report;
use App\Tag;
use App\SupportCategory1;   
use \Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * 支援概要に関する操作を処理するコントローラー
 */
class OverviewController extends Controller
{
    /**
     * 支援概要を表示する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View  レスポンス
     */
    public function index(Request $request)
    {
        Log::info("OverviewController index");

        //  リクエストからパラメータを取得
        $condition = new \stdClass();
        $condition->disaster_id = $request->input('disaster_id');
        $condition->tag_list = $request->input('tag_list');
        $condition->support_category1_id = $request->input('support_category1_id');
        if($request->input('start_date') == null) {
            $condition->start_date = date('Y-m-d', strtotime('-1 month'));
        } else {
            $condition->start_date = $request->input('start_date');
        }
        var_dump($request->input('end_date'));
        if($request->input('end_date') == null) {  
            $condition->end_date = date('Y-m-d');
        } else {
            $condition->end_date = $request->input('end_date');
        }
        $condition->local_government_id = $request->input('local_government_id'); 
              
        // JSON文字列をPHPの配列にデコード
        $tags = json_decode($condition->tag_list, true);

        $allTagRecs = collect(); // 空のコレクションを初期化
        
        if($tags == null) {
            $tags = [];
        }
        // 'value'キーの値だけを持つ配列を作成
        $valuesArray = array_column($tags, 'value');

        // 重複を除去
        $uniqueValues = array_unique($valuesArray);

        // 元の形式に戻す
        $uniqueArray = array_map(function ($value) {
            return ['value' => $value];
        }, $uniqueValues);

        // $uniqueArrayが空の場合、tagsテーブルの全件を取得
        if (empty($uniqueArray)) {
            $allTagRecs = DB::table('tags')->get();
        } else {
            // $uniqueArrayに要素が存在する場合の処理
            foreach ($uniqueArray as $tag) {
                $tagrecs = DB::table('tags')->where('name', 'LIKE', $tag["value"])->get();
                $allTagRecs = $allTagRecs->merge($tagrecs);
            }
        }

        // $allTagRecsを使用した処理（例：表示やその他のロジック）
        
        $allTagRecs = $allTagRecs->unique('id'); // IDに基づいて重複を排除
        log::info($allTagRecs);
        $ids = []; // 空の配列を初期化\
        foreach ($allTagRecs as $tagRec) {
            $ids[] = $tagRec;
        }

        //  終了日に1日をプラスする
        $condition->end_date = date('Y-m-d', strtotime($condition->end_date . ' +1 day'));

        $Reports = new Collection();
        foreach($ids as $id) {

            $results = DB::table('report_tag as rt')
            ->join('reports as r', 'rt.report_id', '=', 'r.id')
            ->join('report_support_category2 as rsc2', 'r.id', '=', 'rsc2.report_id')
            ->join('support_category2s as sc2', 'rsc2.support_category2_id', '=', 'sc2.id')
            ->join('support_category1s as sc1', 'sc2.support_category1_id', '=', 'sc1.id')
            ->join('shelters as sh', 'r.shelter_id', '=', 'sh.id')
            ->join('local_governments as lg', 'sh.local_government_id', '=', 'lg.id')

            ->select('sc1.name as support_category1_name','sc1.id as support_category1_id', 'sc2.name as support_category2_name', 
                    'rsc2.support_category2_id', 'sh.id as shelter_id', 'lg.name as local_government_name')
            ->where('rt.tag_id', '=', $id->id)
            ->where('r.report_date', '>=', $condition->start_date)
            ->where('r.report_date', '<', $condition->end_date)
            ->when($condition->disaster_id, function ($query) use ($condition) {
                return $query->where('r.disaster_id', $condition->disaster_id);
            })  
            ->when($condition->local_government_id, function ($query) use ($condition) {
                return $query->where('sh.local_government_id', $condition->local_government_id);
            })  
            ->when($condition->support_category1_id, function ($query) use ($condition) {
                return $query->where('sc1.id', $condition->support_category1_id);
            })  
            ->get();

            // resultsにtag_idを追加
            $results = $results->map(function ($results) use ($id) {
                $results->tag_id = $id->id; // tag_idを追加
                return $results;
            });
        
            // reportsにタグ名を追加
            $results = $results->map(function ($results) use ($id) {
                $results->tag_name = $id->name; // tag_nameを追加
                return $results;
            });
            $Reports = $Reports->merge($results);
        } 

        //  $Reportsにunique_keyを追加する
        $Reports = $Reports->map(function ($item) {
            $item->unique_key = $item->tag_id ."_" . $item->support_category1_id ."_". $item->support_category2_id;
            return $item;
        })->unique('unique_key')->values();

        //  $Reportsを順次処理する
        foreach ($Reports as $Report) {
            // 基本クエリの構築
            $baseQuery = DB::table('report_tag')
                ->join('reports', 'report_tag.report_id', '=', 'reports.id')
                ->join('report_support_category2', 'reports.id', '=', 'report_support_category2.report_id')
                ->where('report_tag.tag_id', $Report->tag_id)
                ->where('report_support_category2.support_category2_id', $Report->support_category2_id)
                ->where('reports.report_date', '>=', $condition->start_date)
                ->where('reports.report_date', '<', $condition->end_date)
                ->when($condition->disaster_id, function ($query) use ($condition) {
                    return $query->where('reports.disaster_id', $condition->disaster_id);
                });

                // Reportの件数を取得する
                $Report->report_count = $baseQuery->clone()->count();

                // Reportのorganization_idの件数を取得する
                $Report->organization_count = $baseQuery->clone()->distinct('reports.organization_id')->count('reports.organization_id');

                // SIGNAL_DANGERの件数を取得する
                $Report->signal_danger_count = $baseQuery->clone()->where('signal', '3')->count();

                // SIGNAL_DANGERとなっているshelter_idの件数を取得する
                $Report->signal_danger_shelter_count = $baseQuery->clone()->where('signal', '3')->distinct('reports.shelter_id')->count('reports.shelter_id');

                // 同様に、SIGNAL_WARNING と SIGNAL_INFO のカウントも取得します
                $Report->signal_warning_count = $baseQuery->clone()->where('signal', '2')->count();
                $Report->signal_warning_shelter_count = $baseQuery->clone()->where('signal', '2')->distinct('reports.shelter_id')->count('reports.shelter_id');

                $Report->signal_info_count = $baseQuery->clone()->where('signal', '1')->count();
                $Report->signal_info_shelter_count = $baseQuery->clone()->where('signal', '1')->distinct('reports.shelter_id')->count('reports.shelter_id');
        }
       
        // タグを取得
        $tags = Tag::all()->pluck('name');

        //  フロントに引数を渡す
        $this->shareCommonVars($request);
        View::share("Reports", $Reports);

        // 支援種別
        $support_category1_pulldown_list = SupportCategory1::get()->pluck('name', 'id')->all();
        View::share("support_category1_pulldown_list", $support_category1_pulldown_list);

        // 必要なデータのみをビューに渡す
        return view('overview.index', compact('tags', 'Reports', 'support_category1_pulldown_list'));
    }

    /*
     * 共通の変数をビューに共有する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return void
     */
    private function shareCommonVars(Request $request)
    {
        /*
         * 災害情報
         */
        $Disasters  = Disaster::get()->pluck('name', 'id')->all();
        \View::share("disasters", $Disasters);

        if($request->input('disaster_id') != null){
            log::info($request->input('disaster_id'));
            \View::share("disaster_id", $request->input('disaster_id'));
        } else {
            \View::share("disaster_id", null);
        }

        /*
         * 自治体コード
         */

        $local_government_pulldowns = [];

        $local_governments = LocalGovernment::select('local_governments.id', 'local_governments.name', 'local_governments.code', 'prefectures.name AS prefecture_name')
            ->join('prefectures', 'prefecture_id', 'prefectures.id')
            ->where('government_type', LocalGovernment::GOVERNMENT_TYPE_TOWN)
            ->orderBy('prefecture_id', "ASC")
            ->get();

        foreach ($local_governments as $local_government) {
            $local_government_pulldowns[$local_government->id] = $local_government->prefecture_name .' '. $local_government->name.' ('. $local_government->code.')';
        }

        View::share("local_government_pulldowns", $local_government_pulldowns);
        
        if($request->input('local_government_id') != null){
            log::info($request->input('local_government_id'));
           \View::share("local_government_id", $request->input('local_government_id'));
        } else {
            \View::share("local_government_id", null);
        }

        /*
         *　支援種別
            */
        if($request->input('support_category1_id') != null){
            log::info($request->input('support_category1_id'));
            \View::share("support_category1_id", $request->input('support_category1_id'));
        } else {
            \View::share("support_category1_id", null);
        }

       /*
        * 開始日
        */
        if($request->input('start_date') != null){
            log::info($request->input('start_date'));
            \View::share("start_date", $request->input('start_date'));
        } else {
            \View::share("start_date", date('Y-m-d', strtotime('-1 month')));
        }
       /*
        * 終了日
        */
        if($request->input('end_date') != null){
            log::info($request->input('end_date'));
            \View::share("end_date", $request->input('end_date'));
        } else {
            \View::share("end_date", date('Y-m-d'));
        }

    
    }

}
