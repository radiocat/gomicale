<?php

$LINE_CHANNEL_ACCESS_TOKEN = getenv('LINE_CHANNEL_ACCESS_TOKEN');
$LINE_CHANNEL_ID = getenv('LINE_CHANNEL_ID');
$LINE_MESSAGE_API_URL = "https://api.line.me/v2/bot/message/push";

echo "LINE_CHANNEL_ACCESS_TOKEN".$LINE_CHANNEL_ACCESS_TOKEN;
echo "LINE_CHANNEL_ID".$LINE_CHANNEL_ID;

$gabageArray = array();

date_default_timezone_set('Asia/Tokyo');
require_once("./phpQuery-onefile.php");  // NOTE: スクレイピングライブラリは別のものも試したい

// FIXME: 月と地域idごとにパラメータを動的にする
$calendarUrl = "https://www.nishi.or.jp/homepage/gomicalendar/calendar.html?date=2021-7&id=259";

$html = file_get_contents($calendarUrl);

$dom = phpQuery::newDocument($html);

$calendarTable = $dom->find("table.calendar");

$nextDate = date("d", strtotime("+1 day"));

// 日付の入っている場所を探してテキストを取得する
foreach ($calendarTable->find("p.date") as $p) {
    $date = pq($p)->text();
    // 該当日付にゴミ情報が複数ある場合もある
    // ゴミの日じゃないときは空文字が取得できるのでゴミの日ではないテキスト情報に置き換え
    // NOTE: ゴミの日じゃない場合は実行終了でもいいかもしれない
    if ($date == $nextDate) {
        $text = pq($p)->parent()->find("p.item")->text();
        if ($text === "") {
            $text = "収集がありません";
        }
        array_push($gabageArray, $text);
    }
}

echo "ごみ情報: " . implode(",", $gabageArray) . "\n";

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
        "text" => $nextDate . "日のごみは\n" . implode(",", $gabageArray) . "\n" . $calendarUrl
    )]
);
$body = json_encode($data);
echo $body;

$header = [
    'Authorization: Bearer ' .$LINE_CHANNEL_ACCESS_TOKEN,
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
