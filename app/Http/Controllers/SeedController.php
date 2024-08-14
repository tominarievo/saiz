<?php

namespace App\Http\Controllers;

use App\Organization;
use App\OrganizationSeed;
use App\Prefecture;
use App\SupportCategory2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * 支援種別画面のコントローラー
 * ※ 支援団体と支援種別の中間テーブルを検索する機能。
 */
class SeedController extends Controller
{
    /**
     * 支援種別画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $condition = new \stdClass();
        $condition->organization_id = $request->input('organization_id');
        $condition->keyword         = $request->input('keyword');
        $condition->prefecture_id    = $request->input('prefecture_id');
        $condition->support_category2_id = $request->input('support_category2_id');

        $query = OrganizationSeed::query();

        $query->whereHas("organization", function($q) {
            $q->where('status', true);
        });

        $query->when(filled($condition->organization_id), function($q) use ($condition) {
            $q->where('organization_id', $condition->organization_id);
        });

        $query->when(filled($condition->support_category2_id), function($q) use ($condition) {
            $q->where('support_category2_id', $condition->support_category2_id);
        });

        $query->when(filled($condition->prefecture_id), function($q) use ($condition) {
            $q->whereHas('organization', function($sub) use ($condition) {
                // TODO カラム名変更
                $sub->where('npo_col_3', 'like', "%{$condition->prefecture_id}%");
            });
        });

        $query->when(filled($condition->keyword), function($q) use ($condition) {

            //連続スペースを1つの半角スペースに替えた上で検索
            $keyword = trim($condition->keyword);
            $keyword = preg_replace('/ +/', ' ', $keyword);
            $keyword = preg_replace('/　+/', ' ', $keyword);

            $words = explode(' ', $keyword);

            foreach ($words as $word) {
                // バラしたキーワードはAND検索
                $q->where(function ($sub) use ($word) {
                    // 複数のカラムが必要な場合はこの中でorWhereを使うことでスコープを閉じることができる。
                    $sub->where('comment', 'like', "%{$word}%");
                });
            }
        });


        $query->orderBy('organization_id', 'ASC');

        $seeds = $query->paginate(10)->appends((array)$condition);


        $organizations = Organization::where('status', true)
            ->get()
            ->pluck('name', 'id')
            ->all();

        View::share("organizations", $organizations);


        $support_category2s = SupportCategory2::orderBy('support_category1_id', "ASC")
            ->get()
            ->pluck('name', 'id')
            ->all();

        View::share("support_category2s", $support_category2s);

        $this->shareCommonVars();

        return view('seeds.index', compact('condition', 'seeds'));
    }

    /**
     * 共通の変数をビューと共有する
     *
     * @return void
     */
    private function shareCommonVars()
    {
        /*
         * 県
         */

        $prefectures = Prefecture::get()->pluck('name', 'name')->all();

        \View::share("prefectures", $prefectures);
    }
}
