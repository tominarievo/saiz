<?php

namespace App\Http\Controllers;

use App\DisasterType;
use App\Http\Requests\DisasterRequest;
use App\Disaster;
use Illuminate\Http\Request;

class DisasterController extends Controller
{
    /**
     * 災害一覧ページを表示するためのメソッド
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {

        $condition = new \stdClass();

        $disasters = Disaster::orderBy('event_date', 'DESC')
            ->paginate(10)->appends((array)$condition);

        return view('disasters.index', compact('disasters'));
    }

    /**
     * 新しい災害を作成するためのフォームを表示するメソッド
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function create(Request $request)
    {
        $disaster = new Disaster();

        $disaster_types = DisasterType::get();

        return view('disasters.create', compact('disaster', 'disaster_types'));
    }

    /**
     * 新しい災害を保存するメソッド
     *
     * @param DisasterRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(DisasterRequest $request)
    {

        $disaster = new Disaster();
        $disaster->status = true;
        $disaster->name                     = $request->input('name');
        $disaster->npo_col_1                = $request->input('npo_col_1');
        $disaster->npo_col_2                = $request->input('npo_col_2');
        $disaster->npo_col_3                = $request->input('npo_col_3');
        $disaster->is_catastrophic_disaster = filled($request->input('is_catastrophic_disaster')) ?: false;
        $disaster->event_date               = $request->input('event_date');
        $disaster->save();


        // 災害種別
        $disaster->disasterTypes()->detach();
        $disaster->disasterTypes()->syncWithoutDetaching($request->input('disaster_type_ids'));


        $request->session()->flash('status', '登録に成功しました。');

        return redirect(route('disasters.index'));
    }

    /**
     * 災害情報を編集するためのフォームを表示するメソッド
     *
     * @param Request $request
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, Disaster $disaster)
    {

        $disaster_types = DisasterType::get();

        /*
         * 災害種別
         */

        $disaster_type_ids = [];

        foreach ($disaster->disasterTypes as $disaster_type) {
            $disaster_type_ids[] = $disaster_type->id;
        }

        $disaster->disaster_type_ids = $disaster_type_ids;

		return view('disasters.edit', compact('disaster', 'disaster_types'));
    }

    /**
     * 災害情報を更新するためのメソッド
     *
     * @param DisasterRequest $request
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(DisasterRequest $request, Disaster $disaster)
    {

        $disaster->status = true;
        $disaster->name                     = $request->input('name');
        $disaster->npo_col_1                = $request->input('npo_col_1');
        $disaster->npo_col_2                = $request->input('npo_col_2');
        $disaster->npo_col_3                = $request->input('npo_col_3');
        $disaster->is_catastrophic_disaster = filled($request->input('is_catastrophic_disaster')) ?: false;
        $disaster->event_date               = $request->input('event_date');
		$disaster->save();

        // 災害種別
        $disaster->disasterTypes()->detach();
        $disaster->disasterTypes()->syncWithoutDetaching($request->input('disaster_type_ids'));

        $request->session()->flash('status', '更新に成功しました。');

		return redirect(route('disasters.index'));
    }

    /**
     * 災害情報を削除するためのメソッド
     *
     * @param DisasterRequest $request
     * @param Disaster $disaster
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy(DisasterRequest $request, Disaster $disaster)
    {
//        TODO ユーザーや情報があれば削除不可にする。

        $disaster->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('disasters.index'));
    }
}
