<?php

namespace App\Http\Controllers;

use App\DisasterType;
use App\Http\Requests\DisasterRequest;
use App\Disaster;
use App\Shelter;
use Illuminate\Http\Request;

/**
 * 災害シェルターのコントローラークラス
 */
class DisasterShelterController extends Controller
{
    /**
     * 災害情報の編集画面を表示する関数
     *
     * @param Request $request
     * @param int $disaster_id 災害ID
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, $disaster_id)
    {
        $disaster = Disaster::findOrFail($disaster_id);

        $shelters = Shelter::where('status', true)
            ->orderBy('name', 'ASC')
            ->get();

		return view('disaster_shelters.edit', compact('disaster', 'shelters'));
    }

    /**
     * 災害情報を更新する関数
     *
     * @param DisasterRequest $request
     * @param int $disaster_id 災害ID
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, $disaster_id)
    {
        $disaster = Disaster::findOrFail($disaster_id);

        $shelters = $request->input('shelters');

        // 災害種別
        $disaster->shelters()->detach();
        $disaster->shelters()->syncWithoutDetaching($shelters);

        $request->session()->flash('status', '更新に成功しました。');

		return redirect(route('disasters.index'));
    }
}
