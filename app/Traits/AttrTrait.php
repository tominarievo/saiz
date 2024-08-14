<?php

namespace App\Traits;

/**
 * 属性マスタの共通処理トレイト
 * @package App\Traits
 */
trait AttrTrait
{
    /**
     * 属性テーブルでプルダウン用に配列を作成する。
     * @param bool $hasPlaceholder
     * @param string $placeholder
     * @return array
     */
    public static function selectOptions($hasPlaceholder = true, $placeholder = '--')
    {
        $list = self::orderBy('order', 'ASC')->orderBy('id', 'ASC')->get();

        $ret = [];

        if ($hasPlaceholder)
        {
            $ret[''] = $placeholder;
        }

        foreach ($list as $row)
        {
            $ret[$row->id] = $row->name;
        }

        return $ret;
    }
}
