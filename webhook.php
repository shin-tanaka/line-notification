<?php

	require_once('./MessagingAPI.php');
	require_once('./DbManager.php');


	/*
		あんまりよろしくないけどとりあえずアクセストークンをベタ書き
		(HerokuのPHP環境変数に移すべき)
	*/
	$channelAccessToken = '';
	$channelSecret = '';
	$dbm = new DbManager();
	$client = new MessagingAPI($channelAccessToken, $channelSecret);

	$event = $dbm->getEvent();

	if(!$event['type'] == 'message'){
		exit();
	}

	$lineId = $event['replyToken']['userId'];
	$message = $event['message'];
	$text = $message['text'];

	//ユーザーをDB問い合わせ
	$dbm->checkUser($lineId);




	/*
		タイトル、日時、詳細を分ける（改行区切り→各変数に格納）
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
					'detail'=> $detail;
				]
	}


	/*
		与えられた日時のテキストをISO8601のフォーマットに則って整形する
	*/
	function convertTime($str){

		/*
		※時間飲み、ISO8601のフォーマットに則って、予め整形して渡す
		2018年10月10日17時30分0秒日本時間:[2018-10-10T17:30:00+0900]
		*/

		//全角スペースさんにご退場いただく
		$str = preg_replace('/　/u', ' ', $str);
		$split = explode(' ', $str);

		//年は省略可（省略した場合はプログラム実行時の年とみなす）
		if(preg_match('/^\d{4}[年\/]/u', $split[0],$matches) == 1){
			$year = preg_replace('/[年\/]/u', '', $matches[0]);
		}
		else{
			$year = date('Y', $time());
		}

		//月（省略不可）
		preg_match('/[年\/^]\d{1,2}[月-\/]/u', $split[0], $matches);
		$month = preg_replace('/[月\/^]/u', '', $matches[0]);

		//日（省略不可）
		preg_match('/[月\/]\d{1,2}[日]/u', $split[0], $matches);
		$day = preg_replace('/[日]/u', '', $matches[0]);

		//時間 (省略不可)
		preg_match('/[時:]/u', $split[1], $matches);
		$time[0] = preg_replace('/[時:]/u');

		//分
		preg_match('/[時:]/u', $split[1], $matches);
		$time[1] = preg_replace('/[分:]/u', '', $matches);


		return  sprintf("%04d-%02d-%02dT%02d:%02d:00+0900", $year, $month, $day, $time[0], $time[1]);

	}

