<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupportCategory1Request;
use App\SupportCategory1;
use Illuminate\Http\Request;

/**
 * 支援種別(大)画面のコントローラー
 */
class SupportCategory1Controller extends Controller
{
    /**
     * 支援種別(大)画面を表示する
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $condition = new \stdClass();

        $support_category1s = SupportCategory1::paginate(10)->appends((array)$condition);

        return view('support_category1s.index', compact('support_category1s'));
    }

   /*
    * 支援種別(大)画面を表示する
    *
    * @param Request $request
    * @return \Illuminate\Http\Response
    */
    public function create(Request $request)
    {
        $support_category1 = new SupportCategory1();

        return view('support_category1s.create', compact('support_category1'));
    }

   /*
    * 支援種別(大)を登録する
    *
    * @param SupportCategory1Request $request
    * @return \Illuminate\Http\Response
    */
    public function store(SupportCategory1Request $request)
    {
        $support_category1 = new SupportCategory1();
        $support_category1->name          = $request->input('name');
        $support_category1->save();

        $request->session()->flash('status', '登録に成功しました。');

        return redirect(route('support_category1s.index'));
    }

   /*
    * 支援種別(大)を編集する
    * @param Request $request
    * @param SupportCategory1 $support_category1
    * @return \Illuminate\Http\Response
    */
    public function edit(Request $request, SupportCategory1 $support_category1)
    {
		return view('support_category1s.edit', compact('support_category1'));
    }

    /*
     * 支援種別(大)を更新する
     * @param SupportCategory1Request $request
     * @param SupportCategory1 $support_category1
     * @return \Illuminate\Http\Response
     */
    public function update(SupportCategory1Request $request, SupportCategory1 $support_category1)
    {
        $support_category1->name          = $request->input('name');
		$support_category1->save();

        $request->session()->flash('status', '更新に成功しました。');

		return redirect(route('support_category1s.index'));
    }

    /*
     * 支援種別(大)を削除する
     * @param SupportCategory1Request $request
     * @param SupportCategory1 $support_category1
     * @return \Illuminate\Http\Response
     */
    public function destroy(SupportCategory1Request $request, SupportCategory1 $support_category1)
    {
//        TODO ユーザーや情報があれば削除不可にする。

        $support_category1->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('support_category1s.index'));
    }
}
