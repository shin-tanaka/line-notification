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
$userId = $dbm->checkUser($lineId);

//テキストをパースする
$data = parseText($message);

//データベースに予定データを登録する
$result = $dbm->addSchedule($userId, $data['title'], $data['time'], $data['detail']);

if(empty($result)){
	$reply = "スケジュールを正常に登録できませんでした\n書式や、すでに予定が登録されていないか確認の上\nもう一度登録し直してください";
}
else{
	$reply = "=======登録完了========\n以下の内容での登録が完了しました。\n\n";
	$reply.= "件名:" . $data['title']  . "\n";
	$reply.= "日時:" . $data['time']   . "\n";
	$reply.= "詳細:" . $data['detail'] . "\n\n";
	$reply.= "-----------------------------------\n";
	$reply.= "問い合わせ番号:" . $result;
}

//処理結果通知
$client->pushMessage($lineId, $reply);

/**
 * タイトル、日時、詳細を分ける（改行区切り→各変数に格納)
 *
 * @param text LINEで与えられる改行区切りの文章
 * @return タイトル、予定時間、詳細が格納された配列
 */
function parseText($text){
	$args = explode(PHP_EOL, $text);
	$title = $args[0];
	$time = convertTime($args[1]);
	$detail = '';

	//詳細部分(任意)の行を1行にまとめる
	if(3 <= count($args)){
		for ($i=2; $i < count($args); $i++) {
			$detail .= $args[$i] . ' ';
		}
	}

	$data = [
				'title' => $title,
				'time'  => $time,
				'detail'=> $detail
			];

	return $data;
}

/**
 * ※ISO8601のフォーマットに則って、予め下記のように整形して渡す
 * 2018年10月10日17時30分0秒日本時間:[2018-10-10T17:30:00+0900]
 *
 * @param  string $str 時刻情報のテキスト(年を省略すると自動的に現在の年になる)
 * @return string ISO8601フォーマットに整形された時刻情報
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

