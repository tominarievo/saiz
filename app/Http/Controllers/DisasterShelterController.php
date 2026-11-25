<?php

namespace App\Http\Controllers;

use App\Disaster;
use App\Shelter;
use Illuminate\Http\Request;

/**
 * 災害シェルターのコントローラークラス
 */
class DisasterShelterController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $this->authorizeAdmin();
            return $next($request);
        });
    }

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

        // 登録済みの支援先
        $registered = $disaster->shelters()
            ->orderBy('name', 'ASC')
            ->get();

        $registeredIds = $registered->pluck('id')->all();

        // 未登録の支援先（有効なもののみ・検索条件で絞り込み）
        $candidates = Shelter::where('status', true)
            ->whereNotIn('id', $registeredIds)
            ->when(filled($request->input('name')), function ($query) use ($request) {
                $this->applyKeyword($query, (string) $request->input('name'), [
                    'name',
                    'npo_col_2',
                ]);
            })
            ->when(filled($request->input('address')), function ($query) use ($request) {
                $this->applyKeyword($query, (string) $request->input('address'), [
                    'npo_col_11',
                    'npo_col_12',
                    'npo_col_3',
                    'npo_col_4',
                ]);
            })
            ->orderBy('name', 'ASC')
            ->paginate(50)
            ->appends($request->only(['name', 'address']));

        return view('disaster_shelters.edit', compact('disaster', 'registered', 'candidates'));
    }

    /**
     * 災害情報を更新する関数
     *
     * @param Request $request
     * @param int $disaster_id 災害ID
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, $disaster_id)
    {
        $disaster = Disaster::findOrFail($disaster_id);

        $validated = $request->validate([
            'shelters' => ['nullable', 'array'],
            'shelters.*' => ['integer', 'exists:shelters,id'],
        ]);

        $disaster->shelters()->sync($validated['shelters'] ?? []);

        $request->session()->flash('status', '更新に成功しました。');

        return redirect()->route('disaster_shelters.edit', ['disaster_id' => $disaster->id]);
    }

    /**
     * キーワードを指定カラムに対するAND検索として適用する
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $keyword
     * @param array<int, string> $columns
     * @return void
     */
    private function applyKeyword($query, string $keyword, array $columns): void
    {
        $keyword = trim($keyword);
        $keyword = (string) preg_replace('/[\s　]+/u', ' ', $keyword);

        if ($keyword === '') {
            return;
        }

        foreach (explode(' ', $keyword) as $word) {
            if ($word === '') {
                continue;
            }

            $query->where(function ($sub) use ($word, $columns) {
                foreach ($columns as $column) {
                    $sub->orWhere($column, 'like', "%{$word}%");
                }
            });
        }
    }
}
