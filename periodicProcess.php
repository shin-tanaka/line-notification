<?php

require_once('./MessagingAPI.php');
require_once('./DbManager.php');

$channelAccessToken = getenv('ACCESS_TOKEN');
$channelSecret = getenv('CHENNEL_SECRET');

$dbm = new DbManager();
$client = new MessagingAPI($channelAccessToken, $channelSecret);

//DBから現在時刻に近い予定を抽出する
$schedules = $dbm->getSchedules(date('Y-m-d H:i:s'), 10);

//全件に対して通知のメッセージを送信する
$message = "お知らせです！！\n=====%s=====\n%s\n%s\n\n※このメッセージは自動送信です";
foreach ((array)$schedules as $i){
	$time = date('Y年m月d日 H時i分', strtotime('+9 hour', strtotime($i['todo_time'])));
	$text = sprintf($message, $i['title'], $time, $i['detail']);
	$client->pushMessage($i['line_id'], $text);

	//送信した予定はDBから削除する
	$dbm->deleteSchedule($i['schedule_id']);
}