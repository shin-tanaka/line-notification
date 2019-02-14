<?php

class DbManager{

	private $dbinfo;
	private $pdo;

	public function __construct(){
		$this->$dbinfo = parse_url(getenv('DATABASE_URL'));
		$this->pdo = connectPostgreSQL(
			$this->dbinfo['host'],
			substr($this->dbinfo['path'], 1),
			$this->dbinfo['user'],
			$this->dbinfo['pass']);
	}

	private function connectPostgreSql($host, $dbname, $user, $pass){

		try{
			$dsn = "pgsql:host={$host};dbname={$dbname}";
			$this->pdo = new PDO($dsn, $user, $pass);
		}
		catch(PDOException $e){
			exit();
		}
	}

	private function initDb(){

		$deleteTableSql = 'DROP TABLE IF EXISTS user, schedule;';

		$userTableSql = 'CREATE TABLE user (
			userId  SERIAL PRIMARY KEY NOT NULL,
			lineId  TEXT   NOT NULL);';

		$scheduleTableSql = 'CREATE TABLE schedule (
			scheduleId CHAR(8)   NOT NULL,
			userId     INTEGER   PRIMARY KEY NOT NULL,
			title      TEXT      NOT NULL,
			todoTime   TIMESTAMP WITH TIME ZONE NOT NULL,
			detail     TEXT,
			postedAt   TIMESTAMP WITH TIME ZONE NOT NULL);';

		$this->pdo->query($deleteTableSql);
		$this->pdo->query($userTableSql);
		$this->pdo->query($scheduleTableSql);
	}

	/*
		ユーザーをDBに問い合わせて、
			ユーザーが存在  :そのままline上のユーザーIDを返す
			存在しない場合 :DBに登録した上で、ユーザIDを返す
	*/
	public function checkUser($lineId){

		$findUserSql = 'SELECT * FROM user WHERE lineId= :lineId;';

		$stmt = $this->pdo->prepare($findUserSql);
		$stmt->bindValue(':lineId', $lineId);
		$stmt->fetch();

		//新規ユーザー登録
		if(empty($stmt)){
			addUser($lineId);
			$stmt = $this->pdo->prepare($findUserSql);
		$stmt->bindValue(':lineId', $lineId);
		$stmt->fetch();
		}

		return $stmt['lineId'];
	}

	/*
		DB上にユーザーを追加する
	*/
	private function addUser($lineId){
		$stmt = $this->pdo->prepare('INSERT INTO user (lineId) VALUES( :lineId );');
		$stmt->bindValue(':lineId', $lineId);
		$stmt->execute();
	}


	/*
		DBにスケジュールを追加する

		===MEMO===
		-問い合わせNo.(スケジュールID)は,何らかの文字列を
		'adler32'を使ってハッシュを求めて、それを使う(とにかく８文字に抑えたい)

		$scheduleId = hash('adler32', "何らかの文字列"、false);
		を使う

		----------------------------------------------------------

		※時間飲み、ISO8601のフォーマットに則って、予め整形して渡す
		2018年10月10日17時30分0秒日本時間:[2018-10-10T17:30:00+0900]
		↑こんな感じ

	*/
	public function addSchedule($title, $time, $detail){
		$stmt = $this->pdo->prepare( 'INSERT INTO schedule
			(scheduleId, userId, title todoTime, detail)
			VALUES(:scheduleId, :userId, :title, :todoTime, :detail)');

		$stmt->bindValue(':scheduleId',);
		$stmt->bindValue(':userId',);
		$stmt->bindValue(':title', $title);
		$stmt->bindValue(':todotime', $time);
		$stmt->bindValue(':detail', $detail);
	}

}