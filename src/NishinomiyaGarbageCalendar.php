<?php

namespace Gomicale;

use PHPHtmlParser\Dom;

class NishinomiyaGarbageCalendar
{

    /**
     * @var string　ゴミの情報自体がない場合のテキスト
     */
    public const EMPTY_GARBAGE_INFO = '情報がありません';
    /**
     * @var string ゴミの収集がない日のテキスト
     */
    public const NO_GARBAGE_INFO = '収集がありません';

    /**
     * @var Dom
     */
    private $dom;

    /**
     * にしのみやゴミカレンダー
     * @param $dom にしのみやゴミカレンダーのDom
     */
    public function __construct(Dom $dom)
    {
        $this->dom = $dom;
    }

    /**
     * にしのみやゴミカレンダーのURLをつくる
     * @param int $targetYear カレンダーの年
     * @param int $targetMonth カレンダーの月
     * @param int $targetAreaId 地区のid（3桁の数字）
     * @see https://www.nishi.or.jp/homepage/gomicalendar/index.html
     */
    public static function createUrl(int $targetYear, int $targetMonth, int $targetAreaId): string
    {
        return 'https://www.nishi.or.jp/homepage/gomicalendar/calendar.html?date=' . $targetYear
             . '-' . $targetMonth . '&id=' . $targetAreaId;
    }

    /**
     * にしのみやごみカレンダーのテーブルを返す
     * @return mixed|Collection|null
     */
    public function getCalendarTable()
    {
        return $this->dom->find("table.calendar");
    }

    /**
     * @param int $targetDate 対象日付（1-31）
     * @return array|null
     */
    public function getGarbageInfoArray(int $targetDate): array
    {
        // カレンダー情報がない場合は情報がないというメッセージを入れて返す
        $calendarCollection = $this->getCalendarTable();
        if ((string)$calendarCollection === '') {
            return array(self::EMPTY_GARBAGE_INFO);
        }

        $calendarDom = new Dom();
        $calendarDom->loadStr((string) $calendarCollection);
        $gabageInfoArray = array();
        $isTargetDate = false;
        // 日付の入っている場所を探してテキストを取得する
        foreach ($calendarDom->find("p") as $p) {
            if ($p->getAttribute('class') === 'date') {
                // 対象日の次の日でここに入った場合はそれ以降の日付はチェック不要なのでループを抜ける
                if ($isTargetDate) {
                    break;
                }
                $isTargetDate = self::isTargetDate($p->text, $targetDate);
                continue;
            }
            // 該当日付にゴミ情報が複数ある場合もある
            // ゴミの日じゃないときは空文字が取得できるのでゴミの日ではないテキスト情報に置き換え
            // NOTE: ゴミの日じゃない場合は実行終了でもいいかもしれない
            // NOTE: おれ、PHP8になったらstr_containsを使うんだ。
            if ($isTargetDate && strpos($p->getAttribute('class'), 'item') !== false) {
                $text = $p->text;
                if ($text === "") {
                    return array(self::NO_GARBAGE_INFO);
                }
                array_push($gabageInfoArray, $text);
            }
        }
        // ゴミ情報が取れていない場合は収集なしと判断する
        if (empty($gabageInfoArray)) {
            array_push($gabageInfoArray, self::NO_GARBAGE_INFO);
        }
        return $gabageInfoArray;
    }

    /**
     * カレンダー上のテキストの日付が対象日付かどうかを返す
     * @return 対象日付かどうか
     */
    public static function isTargetDate(string $textDate, int $targetDate): bool
    {
        if (is_numeric($textDate) && $textDate == $targetDate) {
            return true;
        }
        return false;
    }
}
