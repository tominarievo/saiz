<?php

namespace App\Http\Controllers;

use App\Organization;
use App\OrganizationSeed;
use App\SupportCategory2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * OrganizationSeedControllerクラス
 * 
 * 組織のシードデータに関連する操作を処理するコントローラークラス
 */
class OrganizationSeedController extends Controller
{
    /**
     * インデックスページを表示する
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * 新しいリソースの作成フォームを表示する
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $organization = Organization::find($request->input('organization_id'));
        $this->authorize('update', $organization);


        $organization_seed = new OrganizationSeed();
        $organization_seed->organization_id = $request->input('organization_id');

        $this->setCommonVars();

        return view('organization_seeds.create', compact('organization_seed'));
    }

    /**
     * 新しいリソースを保存する
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $support_category2 = SupportCategory2::find($request->input('support_category2_id'));

        $organization_seed = new OrganizationSeed();
        $organization_seed->organization_id = $request->input('organization_id');
        $organization_seed->support_category1_id = $support_category2->support_category1_id;
        $organization_seed->support_category2_id = $request->input('support_category2_id');
        $organization_seed->comment = $request->input('comment');
        $organization_seed->save();

        $request->session()->flash('status', '追加に成功しました。');

        return redirect(route('organizations.show', ['organization' => $organization_seed->organization_id]));
    }

    /**
     * 指定されたリソースを表示する
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * リソースの編集フォームを表示する
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, OrganizationSeed $organization_seed)
    {
        $this->setCommonVars();

        return view("organization_seeds.edit", compact("organization_seed"));
    }

    /**
     * 指定されたリソースを更新する
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, OrganizationSeed $organization_seed)
    {
        $support_category2 = SupportCategory2::find($request->input('support_category2_id'));

        $organization_seed->support_category1_id = $support_category2->support_category1_id;
        $organization_seed->support_category2_id = $request->input('support_category2_id');
        $organization_seed->comment = $request->input('comment');
        $organization_seed->save();

        $request->session()->flash('status', '追加に成功しました。');

        return redirect(route('organizations.show', ['organization' => $organization_seed->organization_id]));
    }

    /**
     * 指定されたリソースを削除する
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, OrganizationSeed $organization_seed)
    {
        $organization_seed->delete();

        $request->session()->flash('status', '削除に成功しました。');

        return redirect(route('organizations.show', ['organization' => $organization_seed->organization_id]));
    }

    /**
     * 一般的な変数を設定する
     *
     * @return array
     */
    private function setCommonVars()
    {
        $list = SupportCategory2::orderBy('support_category1_id', 'ASC')->get();

        $support_category2s = [];

        foreach ($list as $support_category2) {

            $support_category2s[$support_category2->id] = $support_category2->supportCategory1->name . ' - ' . $support_category2->name;
        }

        View::share("support_category2s", $support_category2s);
    }
}
