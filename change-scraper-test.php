<?php

//require
require './vendor/autoload.php';

//カレントにある.envを取得する。本番環境では.envを作らないので開発環境だけロードする。
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
if (file_exists(".env")) {
    $dotenv->load();
    echo ".env loaded.", PHP_EOL;
}

use Gomicale\DomLoader;
use Gomicale\NishinomiyaGarbageCalendar;

// ごみカレンダーの対象地区はコマンドライン引数から取得する
// 引数が指定されていない場合は実行しない
$targetAreaId = $argv[1];
if (empty($targetAreaId)) {
    exit('第1引数に地区のIDを指定してください');
}

date_default_timezone_set('Asia/Tokyo');
require_once("./phpQuery-onefile.php");

// NOTE: 2021年7月の1ヶ月間はphpQueryで実績があるので7/1〜7/31までテストしてみる
// 実行日付情報
$testDate = "2021-07-01";
for ($count = 0; $count < 31; $count++) {
    // ゴミカレンダーの日付設定
    $targetTimestamp = strtotime($testDate . "+" . strval($count) . " day");
    $targetMonthParam = date("Y-n", $targetTimestamp);  // ごみカレンダーを参照するときの対象月
    $targetDate = date("d", $targetTimestamp);  // 取得するカレンダー上の日付
    echo date("Y-m-d", $targetTimestamp), PHP_EOL;

    // 対象となる地区と月を指定した西宮市のごみカレンダーのURL
    $calendarUrl = "https://www.nishi.or.jp/homepage/gomicalendar/calendar.html?date=" . $targetMonthParam . "&id=" . $targetAreaId;

    // 取得するごみ情報
    $gabageArray = array();

    $html = file_get_contents($calendarUrl);

    $dom = phpQuery::newDocument($html);

    $calendarTable = $dom->find("table.calendar");

    // 日付の入っている場所を探してテキストを取得する
    foreach ($calendarTable->find("p.date") as $p) {
        $date = pq($p)->text();
        // 該当日付にゴミ情報が複数ある場合もある
        // ゴミの日じゃないときは空文字が取得できるのでゴミの日ではないテキスト情報に置き換え
        // NOTE: ゴミの日じゃない場合は実行終了でもいいかもしれない
        if ($date == $targetDate) {
            $text = pq($p)->parent()->find("p.item")->text();
            if ($text === "") {
                $text = "収集がありません";
            }
            // phpQueryはp.itemを改行つきでまとめて取ってしまっていたので改行を消す
            $text = str_replace(PHP_EOL, '', $text);
            array_push($gabageArray, $text);
        }
    }

    $phpQueryText = implode("", $gabageArray);
    echo "phpQueryのごみ情報: ", $phpQueryText, PHP_EOL;

    $domLoader = new DomLoader($calendarUrl);
    $nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($domLoader->getDom());
    $gabageArray = $nishinomiyaGabageCalenar->getGarbageInfoArray($targetDate);
    $PHPHTMLParserText = implode("", $gabageArray);

    echo "PHPHtmlParserのごみ情報: ", $PHPHTMLParserText, PHP_EOL;
    
    if($phpQueryText !== $PHPHTMLParserText){
        echo "＊＊＊＊＊＊＊＊＊＊＊＊＊＊ 不一致！！ ＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊＊", PHP_EOL;
    }

}
