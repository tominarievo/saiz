<?php

namespace App\Support;

/**
 * 自動収集データのカテゴリ定義
 */
class DisasterDataSourceCategory
{
    public const INFORMATION = 'information';

    public const ROADS = 'roads';

    public const WASTE_SITES = 'waste_sites';

    public const MEETING_INSIGHTS = 'meeting_insights';

    public const EVACUATION_SHELTERS = 'evacuation_shelters';

    public const VOLUNTEER_CENTERS = 'volunteer_centers';

    /**
     * カテゴリIDと表示名の一覧を返す。
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::INFORMATION => '最新情報',
            self::ROADS => '道路規制',
            self::WASTE_SITES => '災害廃棄物・仮置場',
            self::MEETING_INSIGHTS => '議事録',
            self::EVACUATION_SHELTERS => '避難所情報',
            self::VOLUNTEER_CENTERS => 'ボランティア募集情報',
        ];
    }
}
