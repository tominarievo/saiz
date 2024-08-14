<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * レポートモデルは、災害報告や支援情報などの管理を担当します。
 *
 * Class Report
 * @package App
 */
class Report extends Model
{
    use HasFactory;

    /**
     * モデルの複数代入を行う属性。
     *
     * @var array
     */
    protected $fillable = [
        "organization_id",
        "disaster_id",
        "shelter_id",
        "report_date",
        "organization_id",
        "comment",
        "hidden_comment"
    ];

    /**
     * このレポートに関連する組織を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class)->withDefault(new Organization());
    }

    /**
     * このレポートに関連する避難所を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shelter()
    {
        return $this->belongsTo(Shelter::class)->withDefault(new Shelter());
    }

    /**
     * このレポートに関連する災害情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function disaster()
    {
        return $this->belongsTo(Disaster::class)->withDefault(new Disaster());
    }

    /**
     * このレポートに関連するサポートカテゴリ2の情報を取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function supportCategory2s()
    {
        return $this->belongsToMany(SupportCategory2::class)->withPivot('signal', 'memo');
    }

    /**
     * このレポートを最後に更新したユーザーを取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function updateUser()
    {
        return $this->belongsTo(User::class, 'update_user_id', 'id')->withDefault(new User());
    }

    /**
     * コメント以外のシグナルがついたデータを取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function validSupportCategory2s()
    {
        return $this->belongsToMany(SupportCategory2::class)
            ->withPivot('signal', 'memo')
            ->where('signal', "!=", Signal::NO_SIGNAL);
    }

    /**
     * このレポートに関連するタグを取得します。
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * report_date属性のアクセサです。
     *
     * @param $value
     * @return string
     */
    public function getReportDateAttribute($value)
    {
        if (blank($value)) {
            return "";
        }

        $carbon = new Carbon($value);

        return $carbon->format('Y/m/d');
    }

    /**
     * このレポートに関連するタグの名前をカンマ区切りで取得します。
     *
     * @return string
     */
    public function getTagListStr()
    {
        if (blank($this->tags)) {
            return "";
        }

        return implode(",", $this->tags->pluck('name')->all());
    }

    /**
     * 日報一覧画面で表示するシグナル情報用の情報を整形して取得します。
     *
     * @return array
     */
    public function getSupportCategoryInfo()
    {
        $ret = [];

        foreach ($this->supportCategory2s as $supportCategory2) {

            // 支援種別(大)ごとに1単位となるようなデータを作成
            if ( ! isset($ret[$supportCategory2->support_category1_id])) {
                $ret[$supportCategory2->support_category1_id] = [
                    "id"   => $supportCategory2->supportCategory1->id,
                    "name" => $supportCategory2->supportCategory1->name
                ];
            }

            if ( ! isset($ret[$supportCategory2->support_category1_id]['signal'])) {
                $ret[$supportCategory2->support_category1_id]['signal'] = $supportCategory2->pivot->signal;
            } else {

                if ($supportCategory2->pivot->signal == Signal::NO_SIGNAL) {
                    continue;
                }

                // 最初に登録されたのがメモだった場合は条件なく、メモ以外のフラグで上書き
                if ($ret[$supportCategory2->support_category1_id]['signal'] == Signal::NO_SIGNAL) {
                    $ret[$supportCategory2->support_category1_id]['signal'] = $supportCategory2->pivot->signal;

                } else if ($ret[$supportCategory2->support_category1_id]['signal'] < $supportCategory2->pivot->signal) {
                    //　強いシグナルがあればそれで上書きする。
                    $ret[$supportCategory2->support_category1_id]['signal'] = $supportCategory2->pivot->signal;
                }
            }
        }

        return $ret;
    }

    /**
     * 大分類で絞り込んだ中分類を取得します。
     *
     * @param $support_category1_id
     * @return array
     */
    public function getSubCategories($support_category1_id)
    {
        $ret = [];

        // コメントも含め全ての中分類
        foreach ($this->supportCategory2s as $supportCategory2) {

            // 支援種別(大)ごとに1単位となるようなデータを作成
            if ($supportCategory2->support_category1_id != $support_category1_id) {
                continue;
            }

            $ret[] = $supportCategory2;
        }

        return $ret;
    }

    /**
     * 支援種別大で絞り込んだ一番強いフラグを取得します。
     *
     * @param $support_category1_id
     * @return int
     */
    public function getStrongestSignal($support_category1_id)
    {
        $signal = Signal::SIGNAL_INFO;

        foreach ($this->validSupportCategory2s as $supportCategory2) {

            // 支援種別(大)ごとに1単位となるようなデータを作成
            if ($supportCategory2->support_category1_id != $support_category1_id) {
                continue;
            }

            if ($supportCategory2->pivot->signal > $signal) {
                $signal = $supportCategory2->pivot->signal;
            }
        }

        return $signal;
    }

    /**
     * 引数のユーザーが日報を登録する組織のユーザーかどうかを判定します。
     * adminかどうかの判定だけで足りるが、今後の拡張のため念の為判定をしている。
     * @param $login_user_id
     * @return bool
     */
    public function isUpdateByAdminUser($login_user_id)
    {
        $users = $this->organization->users;

        if (blank($users)) {
            return true;
        }

        foreach ($users as $user) {

            // 1名でもユーザーが一致すれば組織のユーザーが更新したということ。
            if ($user->id == $login_user_id) {
                return false;
            }
        }

        return true;
    }
}
