<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Disaster
 *
 * このクラスは災害情報を表すモデルです。
 * メンバーは以下の通りです。
 * - id: 災害情報のID
 * - event_date: 災害が発生した日時
 * - title: 災害のタイトル
 * - description: 災害の詳細
 * - created_at: レコードの作成日時
 * - updated_at: レコードの更新日時
 *
 * @package App
 */
class Disaster extends Model
{
    use HasFactory;

    /**
     * 災害が発生した日時を指定されたフォーマットで返します。
     *
     * @param  string|null  $value
     * @return string|null
     */
    function getEventDateAttribute($value)
    {
        return $value ? Carbon::parse($value)->format('Y/m/d') : null;
    }

    /**
     * この災害に関連するすべての災害タイプを取得します。
     *
     * 多対多のリレーションシップを利用して、DisasterモデルとDisasterTypeモデル間の関連を定義します。
     * belongsToManyメソッドにより、災害（Disaster）と災害タイプ（DisasterType）の間に設定される
     * 関連付けは、中間テーブルを通じて管理されます。このメソッドを使用することで、特定の災害に
     * 対応するすべての災害タイプを簡単に取得し、操作することが可能になります。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *         DisasterモデルとDisasterTypeモデル間の多対多リレーションシップを表すEloquentリレーション。
     */
    public function disasterTypes()
    {
        return $this->belongsToMany(DisasterType::class);
    }

    /**
     * この災害に関連するすべての避難所を取得します。
     *
     * 多対多のリレーションシップを利用して、DisasterモデルとShelterモデル間の関連を定義します。
     * belongsToManyメソッドにより、災害（Disaster）と避難所（Shelter）の間に設定される
     * 関連付けは、中間テーブルを通じて管理されます。このメソッドを使用することで、特定の災害に
     * 対応するすべての避難所を簡単に取得し、操作することが可能になります。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     *         DisasterモデルとShelterモデル間の多対多リレーションシップを表すEloquentリレーション。
     */
    public function shelters()
    {
        return $this->belongsToMany(Shelter::class);
    }
}
