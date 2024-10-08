<?php

/**
 * このファイルは、Macで作成されたファイル名がアップロードされた場合の文字列変換機能を提供します。
 * Macで作成されたファイル名はNFD形式であり、WindowsやLinuxで作成された場合と形式が異なります。
 * このクラスは、MacのNFD形式のファイル名をNFC形式に変換します。
 *
 * @category Models
 * @package  App
 */

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * NfdConverterクラス
 * Macで作られたファイルがアップロードされたとき、ファイル名で使われている平仮名、片仮名の濁点、半濁点が、WindowsやLinuxで作られた場合と形式が異なっていて、ファイル名の検索や文字数取得で不具合が出る。
 * MacはNFD形式、WindowsはNFC形式なので、NFD形式⇒NFC形式に変換する
 * 参考：https://qiita.com/Maranello/items/0638b71621c403cdd8c3
 * Class NfdConverter
 * @category Models
 * @package  App
 */
class NfdConverter extends Model
{
    /**
     * Macで作成された日本語ファイル名の濁点／半濁点を吸収するためのメソッドです。
     *
     * ファイル名を渡したらきれいにして返します。
     *
     * @param string $string 元のファイル名
     * @return string 変換後のファイル名
     */
    static public function normalizeUtf8MacFileName($string)
    {
        $newString = '';
        $beforeChar = '';
        //基本的に一文字前の文字を一文字ずつ繋げていくので、文字数よりも一回ループが多い
        for ($i = 0; $i <= mb_strlen($string, 'UTF-8'); $i++) {
            $nowChar = mb_substr($string, $i, 1, 'UTF-8');
            if ($nowChar == hex2bin('e38299')) { //Macの濁点
                $retChar = self::macConvertKana($beforeChar, false);
                $substituteChar = 'e3829b'; //Windowsの全角濁点
                goto convPoint;
            } elseif ($nowChar == hex2bin('e3829a')) { //Macの半濁点
                $retChar = self::macConvertKana($beforeChar, true);
                $substituteChar = 'e3829c'; //Windowsの全角半濁点

                convPoint: //濁点または半濁点があった場合の処理
                if ($retChar) { //前の文字と合体可能の場合
                    $newString .= $retChar;
                    $beforeChar = '';
                } else { //前の文字と合体不可能の場合
                    $newString .= $beforeChar;
                    $beforeChar = hex2bin($substituteChar); //Windowsの全角濁点／半濁点に置換
                }
            } else { //濁点／半濁点以外はそのままスルー
                $newString .= $beforeChar;
                $beforeChar = $nowChar;
            }
        }
        return $newString;
    }

    /**
     * 一文字渡された文字に対し、濁点付き、半濁点付きの文字を返します。
     *
     * @param string $char 対象の文字
     * @param boolean $half 半濁点かどうかのフラグ
     * @return string 変換後の文字
     */
    static public function macConvertKana($char, $half = false)
    {
        $retChar = '';
        if ($char) {
            //濁点の対応表
            $fullTable = array(
                'か' => 'が','き' => 'ぎ','く' => 'ぐ','け' => 'げ','こ' => 'ご',
                'さ' => 'ざ','し' => 'じ','す' => 'ず','せ' => 'ぜ','そ' => 'ぞ',
                'た' => 'だ','ち' => 'ぢ','つ' => 'づ','て' => 'で','と' => 'ど',
                'は' => 'ば','ひ' => 'び','ふ' => 'ぶ','へ' => 'べ','ほ' => 'ぼ',
                'ゝ' => 'ゞ',
                'カ' => 'ガ','キ' => 'ギ','ク' => 'グ','ケ' => 'ゲ','コ' => 'ゴ',
                'サ' => 'ザ','シ' => 'ジ','ス' => 'ズ','セ' => 'ゼ','ソ' => 'ゾ',
                'タ' => 'ダ','チ' => 'ヂ','ツ' => 'ヅ','テ' => 'デ','ト' => 'ド',
                'ハ' => 'バ','ヒ' => 'ビ','フ' => 'ブ','ヘ' => 'ベ','ホ' => 'ボ',
                'ウ' => 'ヴ','ヽ' => 'ヾ',
            );
            //半濁点の対応表
            $halfTable = array(
                'は' => 'ぱ','ひ' => 'ぴ','ふ' => 'ぷ','へ' => 'ぺ','ほ' => 'ぽ',
                'ハ' => 'パ','ヒ' => 'ピ','フ' => 'プ','ヘ' => 'ペ','ホ' => 'ポ',
            );
            //どちらの対応表を使うか
            if ($half) {
                $targetArray = $halfTable;
            } else {
                $targetArray = $fullTable;
            }
            //対応表に合致するか
            if (isset($targetArray[$char])) {
                $retChar = $targetArray[$char];
            }
        }
        return $retChar;
    }
}
