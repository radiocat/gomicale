<?php

//require
require './vendor/autoload.php';

use Gomicale\DomLoader;
use Gomicale\NishinomiyaGarbageCalendar;

//カレントにある.envを取得する。本番環境では.envを作らないので開発環境だけロードする。
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
if (file_exists(".env")) {
    $dotenv->load();
    echo ".env loaded.", PHP_EOL;
}

// LINE botメッセージの関連情報
$LINE_CHANNEL_ACCESS_TOKEN = getenv('LINE_CHANNEL_ACCESS_TOKEN');
$LINE_CHANNEL_ID = getenv('LINE_CHANNEL_ID');
$LINE_MESSAGE_API_URL = "https://api.line.me/v2/bot/message/broadcast";

// 実行日付情報
$targetTimestamp = strtotime("+1 day");
$targetMonthParam = date("Y-n", $targetTimestamp);  // ごみカレンダーを参照するときの対象月
$targetDate = date("d", $targetTimestamp);  // 取得するカレンダー上の日付
// ごみカレンダーの対象地区はコマンドライン引数から取得する
// 引数が指定されていない場合は実行しない
$targetAreaId = $argv[1];
if (empty($targetAreaId)) {
    exit('第1引数に地区のIDを指定してください');
}

date_default_timezone_set('Asia/Tokyo');

// 対象となる地区と月を指定した西宮市のごみカレンダーのURL
$calendarUrl = "https://www.nishi.or.jp/homepage/gomicalendar/calendar.html?date=" . $targetMonthParam . "&id=" . $targetAreaId;

// 取得するごみ情報
$gabageArray = array();

$html = file_get_contents($calendarUrl);

$domLoader = new DomLoader($calendarUrl);
$nishinomiyaGabageCalenar = new NishinomiyaGarbageCalendar($domLoader->getDom());
$gabageArray = $nishinomiyaGabageCalenar->getGarbageInfoArray($targetDate);

echo "ごみ情報: ", implode(",", $gabageArray), PHP_EOL;

// for debug
//exit('デバッグ終了');

// LINE bot用のメッセージのJSONをつくる
// ↓Jsonサンプル
// $body = '{"to": "[ユーザーID]",
//     "messages":[
//         {
//             "type":"text",
//             "text":"Hello, world"
//         }
//     ]}';
$data = array(
    "to" => $LINE_CHANNEL_ID,
    "messages" => [array(
        "type" => "text",
        "text" => $targetDate . "日のごみは\n" . implode(",", $gabageArray) . "\n" . $calendarUrl
    )]
);
$body = json_encode($data);
echo $body, PHP_EOL;

$header = [
    'Authorization: Bearer ' . $LINE_CHANNEL_ACCESS_TOKEN,
    'Content-Type: application/json',
];

$curl = curl_init($LINE_MESSAGE_API_URL);
$options = [
    CURLOPT_HTTPHEADER => $header,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
];
curl_setopt_array($curl, $options);
$result = curl_exec($curl);

echo $result;
