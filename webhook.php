<?php

require_once('./MessagingAPI.php');
require_once('./DbManager.php');

$channelAccessToken = getenv('ACCESS_TOKEN');
$channelSecret = getenv('CHANNEL_SECRET');

$dbm = new DbManager();
$client = new MessagingAPI($channelAccessToken, $channelSecret);

//データ取得
$data = $client->getEvent();
$lineId = $data['source']['userId'];
$message = $data['message']['text'];

//ユーザーをDB問い合わせ
$result = $dbm->checkUser($lineId);

//テキストをパースする
$data = parseText($message);

//データベースに予定データを登録する
$dbm->addSchedule($lineId, $data['title'], $data['time'], $data['detail']);

//処理完了通知
$client->pushMessage($lineId, '>>>DONE');

/*
	タイトル、日時、詳細を分ける（改行区切り→各変数に格納)
*/
function parseText($text){
	$args = explode(PHP_EOL, $text);
	$title = $args[0];
	$time = convertTime($args[1]);
	$detail = '';

	if(2 < count($args)){
		for ($i=2; $i < count($args); $i++) {
			$detail .= $args[i] . ' ';
		}
	}

	$data = [
				'title' => $title,
				'time'  => $time,
				'detail'=> $detail
			];

	return $data;
}

/*
	※ISO8601のフォーマットに則って、予め下記のように整形して渡す
	2018年10月10日17時30分0秒日本時間:[2018-10-10T17:30:00+0900]
*/
function convertTime($str){

	//全角スペースさんにご退場いただく
	$str = preg_replace('/　/u', ' ', $str);
	$split = explode(' ', $str);

	//数字以外の文字で分割して、数字を取り出す。
	$date = preg_split('/[年月日\/\-]/u', $split[0]);
	$date = array_filter($date, 'strlen');

	switch(count($date)){
		case 2:
			$year = date('Y');
			break;

		case 3:
			$year = $date[0];
			break;

		default:
			exit(0);
			break;
	}

	$day = $date[count($date) - 1];
	$month = $date[count($date) - 2];

	$time = preg_split('/[時分:]/u', $split[1]);
	$time = array_filter($time, 'strlen');

	return  sprintf("%04d-%02d-%02dT%02d:%02d:00+0900", $year, $month, $day, $time[0], $time[1]);

}

