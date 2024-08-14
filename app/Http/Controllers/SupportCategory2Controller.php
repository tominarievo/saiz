<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRequest;
use App\Http\Requests\SupportCategory2Request;
use App\Organization;
use App\SupportCategory1;
use App\SupportCategory2;
use Illuminate\Http\Request;

/**
 * 支援種別(中)画面のコントローラー
 */
class SupportCategory2Controller extends Controller
{
    /**
     * 支援種別(中)画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $condition = new \stdClass();

        $support_category1_id = $request->input('support_category1_id');

        $support_category1 = SupportCategory1::findOrFail($support_category1_id);

        $support_category2s = SupportCategory2::where('support_category1_id', $support_category1_id)
            ->paginate(10)->appends((array)$condition);

        return view('support_category2s.index', compact('support_category2s', 'support_category1'));
    }

    /**
     * 支援種別(中)画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $support_category2 = new SupportCategory2();
        $support_category2->support_category1_id = $request->input('support_category1_id');

        return view('support_category2s.create', compact('support_category2'));
    }

    /**
     * 支援種別(中)を登録する
     *
     * @param SupportCategory2Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(SupportCategory2Request $request)
    {
        $support_category2 = new SupportCategory2();
        $support_category2->support_category1_id = $request->input('support_category1_id');
        $support_category2->name                 = $request->input('name');
        $support_category2->level1                 = $request->input('level1');
        $support_category2->level2                 = $request->input('level2');
        $support_category2->level3                 = $request->input('level3');
        $support_category2->save();

        $request->session()->flash('status', '登録に成功しました。');

        return redirect(route('support_category2s.index', ['support_category1_id' => $support_category2->support_category1_id]));
    }

    /**
     * 支援種別(中)を編集する
     * @param Request $request
     * @param SupportCategory2 $support_category2
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, SupportCategory2 $support_category2)
    {
		return view('support_category2s.edit', compact('support_category2'));
    }

    /**
     * 支援種別(中)を更新する
     * @param SupportCategory2Request $request
     * @param SupportCategory2 $support_category2
     * @return \Illuminate\Http\Response
     */
    public function update(SupportCategory2Request $request, SupportCategory2 $support_category2)
    {
        $support_category2->support_category1_id = $request->input('support_category1_id');
        $support_category2->name          = $request->input('name');
        $support_category2->level1                 = $request->input('level1');
        $support_category2->level2                 = $request->input('level2');
        $support_category2->level3                 = $request->input('level3');
        $support_category2->save();

        $request->session()->flash('status', '更新に成功しました。');

        return redirect(route('support_category2s.index', ['support_category1_id' => $support_category2->support_category1_id]));
    }

    /**
     * 支援種別(中)を削除する
     * @param SupportCategory2Request $request
     * @param SupportCategory2 $support_category2
     * @return \Illuminate\Http\Response
     */
    public function destroy(SupportCategory2Request $request, SupportCategory2 $support_category2)
    {
//        TODO ユーザーや情報があれば削除不可にする。

        $support_category2->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('support_category2s.index', ['support_category1_id' => $support_category2->support_category1_id]));
    }
}
