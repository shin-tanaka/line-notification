<?php


class MessagingAPI{

	private $channelAccessToken;
	private $channelSecret;

	public function __construct($channelAccessToken, $channelSecret){
		$this->channelAccessToken = $channelAccessToken;
		$this->channelSecret = $channelSecret;
	}

	//Webhookメッセージの取得
	public function getEvent(){

		//HTTP POST application/json 形式のリクエストを受信
		if($_SERVER['REQUEST_METHOD'] == 'POST'
		 && $media $_SERVER['CONTENT_TYPE'] == 'application/json'){
			$message = file_get_contents('php://input');
		}

		//署名検証を行う 不正ならexit()
		if(!$this->checkHash($message, $_SERVER['HTTP_X_LINE_SIGNATURE'])){
			exit();
		}

		$data = json_decode($message, true);
		return $data['events'];
	}


	public function pushMessage($userId, $message){

		$body = [
			'to' => $userId,
			'messages' => [
				[
					'type' => 'text',
					'text' => $message
				]
			]
		];

		$header = [
			'Content-Type: application/json',
			'Authorization: Bearer ' . $this->channelAccessToken
		];

		$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'header' => implode("\r\n", $header),
					'content' => json_encode($body)
				]
			]
		);

		$url = 'https://api.line.me/v2/bot/message/push';

		file_get_contents($url, false, $context);
	}


	private function checkHash($body, $line_signature){
		$hash = hash_hmac('sha256', $body, $this->channelSecret, ture);
		$signature = base64_encode($hash);
		return hash_equals($signature, $line_signature);
	}

}