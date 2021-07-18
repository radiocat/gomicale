<?php

$LINE_CHANNEL_ACCESS_TOKEN = getenv('LINE_CHANNEL_ACCESS_TOKEN');
$LINE_CHANNEL_ID = getenv('LINE_CHANNEL_ID');
$LINE_MESSAGE_API_URL = "https://api.line.me/v2/bot/message/push";

echo "LINE_CHANNEL_ACCESS_TOKEN".$LINE_CHANNEL_ACCESS_TOKEN;
echo "LINE_CHANNEL_ID".$LINE_CHANNEL_ID;

$targetTimestamp = strtotime("+1 day");
$targetMonthParam = date("Y-n", $targetTimestamp);  // ごみカレンダーを参照するときの対象月
$targetDate = date("d", $targetTimestamp);  // 取得するカレンダー上の日付
// ごみカレンダーの対象地区はコマンドライン引数から取得する
// 引数が指定されていない場合は実行しない
$targetAreaId = $argv[1];
if (empty($targetAreaId)){
    exit('第1引数に地区のIDを指定してください');
}

date_default_timezone_set('Asia/Tokyo');
require_once("./phpQuery-onefile.php");  // FIXME: スクレイピングライブラリはPHP8非対応のため別のものに変える

// 対象となる地区と月を指定した西宮市のごみカレンダーのURL
$calendarUrl = "https://www.nishi.or.jp/homepage/gomicalendar/calendar.html?date=".$targetMonthParam."&id=".$targetAreaId;

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
        "text" => $targetDate . "日のごみは\n" . implode(",", $gabageArray) . "\n" . $calendarUrl
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
