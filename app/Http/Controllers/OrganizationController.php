<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRequest;
use App\LocalGovernment;
use App\Organization;
use App\Prefecture;
use App\Status;
use App\SupportCategory2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * 組織に関する操作を処理するコントローラー
 */
class OrganizationController extends Controller
{
    /**
     * 組織一覧を表示する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View  レスポンス
     */
    public function index(Request $request)
    {
        $condition = new \stdClass();
        $condition->include_disabled = $request->input('include_disabled');
        $condition->prefecture_id    = $request->input('prefecture_id');
        $condition->organization_id  = $request->input('organization_id');
        $condition->keyword          = $request->input('keyword');
        $condition->support_category2_id = $request->input('support_category2_id');

        $query = Organization::query();

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
                    $sub->where('name', 'like', "%{$word}%");
                });
            }
        });

        $query->when(blank($condition->include_disabled), function($q) use ($condition) {
            // blankの場合を判定に使用しているので注意
            $q->where('status', true);
        });

        $query->when(filled($condition->prefecture_id), function($q) use ($condition) {
            $q->where('prefecture_id', $condition->prefecture_id);
        });

        // 支援種別
        $query->when(filled($condition->support_category2_id), function($q) use ($condition) {
            $q->whereHas('seeds', function($sub) use ($condition) {
                $sub->where('support_category2_id', $condition->support_category2_id);
            });
        });

        $organizations = $query->paginate(20)->appends((array)$condition);

        $support_category2s = SupportCategory2::orderBy('support_category1_id', "ASC")
            ->get()
            ->pluck('name', 'id')
            ->all();

        $this->shareCommonVars();

        return view('organizations.index', compact('organizations', 'condition', 'support_category2s'));
    }

    /**
     * 共通の変数をビューに共有する
     *
     * @return void
     */
    private function shareCommonVars()
    {
        /*
         * 県
         */

        $prefectures = Prefecture::get()->pluck('name', 'id')->all();

        \View::share("prefectures", $prefectures);



        $statuses = [
            '0' => '無効',
            '1' => '有効',
        ];

        View::share("statuses", $statuses);

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
    }

    /**
     * 新しい組織を作成するフォームを表示する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View  レスポンス
     */
    public function create(Request $request)
    {
        $organization = new Organization();
        $organization->status = true;

        $this->shareCommonVars();

        return view('organizations.create', compact('organization'));
    }

    /**
     * 新しい組織を登録する
     *
     * @param  \App\Http\Requests\OrganizationRequest  $request  リクエスト
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector  レスポンス
     */
    public function store(OrganizationRequest $request)
    {
        $organization = new Organization();
        $organization->name          = $request->input('name');
        $organization->status        = filled($request->input('status')) ?: false;
        $organization->description   = $request->input('description');

        $organization->local_government_id = $request->input('local_government_id');

        if (filled($organization->local_government_id)) {
            $organization->prefecture_id = $organization->localGovernment->prefecture_id;
        }

        $organization->npo_col_1          = $request->input('npo_col_1');
        $organization->npo_col_2          = $request->input('npo_col_2');
        $organization->npo_col_3          = $request->input('npo_col_3');
        $organization->npo_col_4          = $request->input('npo_col_4');
        $organization->npo_col_5          = $request->input('npo_col_5');
        $organization->npo_col_6          = $request->input('npo_col_6');
        $organization->save();

        $request->session()->flash('status', '登録に成功しました。');

        return redirect(route('organizations.index'));
    }

    /**
     * 特定の組織を表示する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @param  \App\Organization  $organization  組織
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View  レスポンス
     */
    public function show(Request $request, Organization $organization)
    {
        return view('organizations.show', compact('organization'));
    }


    /**
     * 特定の組織を編集するフォームを表示する
     *
     * @param  \Illuminate\Http\Request  $request  リクエスト
     * @param  \App\Organization  $organization  組織
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View  レスポンス
     */
    public function edit(Request $request, Organization $organization)
    {
        $this->authorize('update', $organization);

        $this->shareCommonVars();

		return view('organizations.edit', compact('organization'));
    }

    /**
     * 特定の組織を更新する
     *
     * @param  \App\Http\Requests\OrganizationRequest  $request  リクエスト
     * @param  \App\Organization  $organization  組織
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector  レスポンス
     */
    public function update(OrganizationRequest $request, Organization $organization)
    {

        $organization->name          = $request->input('name');
        $organization->status        = filled($request->input('status')) ?: false;
        $organization->description   = $request->input('description');

        $organization->local_government_id = $request->input('local_government_id');

        if (filled($organization->local_government_id)) {
            $organization->prefecture_id = $organization->localGovernment->prefecture_id;
        }

        $organization->npo_col_1          = $request->input('npo_col_1');
        $organization->npo_col_2          = $request->input('npo_col_2');
        $organization->npo_col_3          = $request->input('npo_col_3');
        $organization->npo_col_4          = $request->input('npo_col_4');
        $organization->npo_col_5          = $request->input('npo_col_5');
        $organization->npo_col_6          = $request->input('npo_col_6');
		$organization->save();

        $request->session()->flash('status', '更新に成功しました。');

        return redirect(route('organizations.show', ['organization' => $organization->id]));
    }

    /**
     * 特定の組織を削除する
     *
     * @param  \App\Http\Requests\OrganizationRequest  $request  リクエスト
     * @param  \App\Organization  $organization  組織
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector  レスポンス
     */
    public function destroy(OrganizationRequest $request, Organization $organization)
    {
//        TODO ユーザーや情報があれば削除不可にする。

        $organization->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('organizations.index'));
    }
}
