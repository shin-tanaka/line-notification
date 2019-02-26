<?php

class DbManager{

	private $dbinfo;
	private $pdo;

	public function __construct(){
		$this->$dbinfo = parse_url(getenv('DATABASE_URL'));
		connectPostgreSQL(
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

	public function initDb(){

		$deleteTableSql = 'DROP TABLE IF EXISTS users, schedules;';

		$userTableSql = 'CREATE TABLE users (
			user_id  SERIAL PRIMARY KEY NOT NULL,
			line_id  TEXT   NOT NULL);';

		$scheduleTableSql = 'CREATE TABLE schedules (
			schedule_id CHAR(8)   NOT NULL,
			user_id     INTEGER   NOT NULL,
			title       TEXT      NOT NULL,
			todo_time   TIMESTAMP WITH TIME ZONE NOT NULL,
			detail      TEXT,
			posted_at   TIMESTAMP WITH TIME ZONE NOT NULL);';

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

		$findUserSql = 'SELECT * FROM user WHERE line_id= :lineId;';

		$stmt = $this->pdo->prepare($findUserSql);
		$stmt->bindValue(':lineId', $lineId);
		$stmt->fetch();

		//新規ユーザー登録
		if(empty($stmt)){
			addUser($line_id);
			$stmt = $this->pdo->prepare($findUserSql);
		$stmt->bindValue(':lineId', $lineId);
		$stmt->fetch();
		}

		return $stmt['line_id'];
	}

	/*
		DB上にユーザーを追加する
	*/
	public function addUser($lineId){
		$stmt = $this->pdo->prepare('INSERT INTO users (line_id) VALUES( :lineId );');
		$stmt->bindValue(':lineId', $lineId);
		$stmt->execute();
	}


	/*
		DBにスケジュールを追加する

		===MEMO===
		-問い合わせNo.(スケジュールID)は,何らかの文字列を
		'adler32'を使ってハッシュを求めて、それを使う(とにかく８文字に抑えたい)

		$scheduleId = hash('adler32', "何らかの文字列", false);
		を使う

		----------------------------------------------------------

		※時間飲み、ISO8601のフォーマットに則って、予め整形して渡す
		2018年10月10日17時30分0秒日本時間:[2018-10-10T17:30:00+0900]
		↑こんな感じ

	*/
	public function addSchedule($userId, $title, $time, $detail){
		$stmt = $this->pdo->prepare( 'INSERT INTO schedules
			(schedule_id, user_id, title todo_time, detail, posted_at)
			VALUES(:scheduleId, :userId, :title, :todoTime, :detail, :postedAt);');

		$plainText = $time . $title . $detail;

		$stmt->bindValue(':scheduleId', hash('adler32', $plainText, false));
		$stmt->bindValue(':userId', $userId);
		$stmt->bindValue(':title', $title);
		$stmt->bindValue(':todoTime', $time);
		$stmt->bindValue(':detail', $detail);
		$stmt->bindValue(':posted_at', date('Y-m-d H:i:s'));

		$stmt->execute();
	}

}