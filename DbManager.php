<?php

class DbManager{

	private $dbinfo;
	private $pdo;

	public function __construct(){
		$this->dbinfo = parse_url(getenv('DATABASE_URL'));
		$this->connectPostgreSql(
			$this->dbinfo['host'],
			substr($this->dbinfo['path'], 1),
			$this->dbinfo['user'],
			$this->dbinfo['pass']);
	}

	/**
	 * PostgresSQLにPDO接続を行う
	 *
	 * @param string $host   DBのホストURL
	 * @param string $dbname DBの名前
	 * @param string $user   DBでのユーザー名
	 * @param string $pass   ユーザーのパスワード
	 */
	private function connectPostgreSql($host, $dbname, $user, $pass){

		try{
			$dsn = "pgsql:host={$host};dbname={$dbname}";
			$this->pdo = new PDO($dsn, $user, $pass);
		}
		catch(PDOException $e){
			exit();
		}
	}

	/**
	 * DBの初期化を行う
	 * DB上のテーブルをすべて削除し、新しく作成する
	 */
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

	/**
	 * ユーザーをDBに問い合わせて、
	 * ユーザーが存在  :ユーザーID(通し番号)を返す
	 * 存在しない場合 :DBに登録した上で、ユーザIDを返す
	 *
	 * @param  string $lineId            LINEのユーザーID(ユーザーDとは別)
	 * @return int    $result['user_id'] DB側で連番で振られるID
	 */
	public function checkUser($lineId){

		$findUserSql = 'SELECT * FROM users WHERE line_id = :lineId;';

		$stmt = $this->pdo->prepare($findUserSql);
		$stmt->bindValue(':lineId', $lineId);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		//新規ユーザー登録
		if(empty($result)){
			$this->addUser($lineId);
			$stmt = $this->pdo->prepare($findUserSql);
		}
		$stmt->bindValue(':lineId', $lineId);
		$stmt->execute();
		$result = $stmt->fetch(PDO::FETCH_ASSOC);

		return $result['user_id'];
	}

	/**
	 * DB上にユーザーを追加する
	 *
	 * @param string $lineId LINEのID(ユーザーIDとは別)
	 */
	public function addUser($lineId){
		$stmt = $this->pdo->prepare('INSERT INTO users (line_id) VALUES( :lineId );');
		$stmt->bindValue(':lineId', $lineId);
		$stmt->execute();
	}

	/**
	 * DBにスケジュールを追加し、問い合わせNoを返す
     * 'adler32'を使ってハッシュを求めて、それをスケジュールIDとして使う(とにかく８文字に抑えたい)
     * スケジュールIDはその後のお問い合わせNoとして利用する
	 *
	 * @param  string $userId ユーザーID(LINEのIDではない点に注意)
	 * @param  string $title  予定のタイトル
	 * @param  string $time   ISO8601フォーマットの時刻データ
	 * @param  string $detail 予定の説明等(省略可能)
	 * @return string $hash   問い合わせ番号として返されるハッシュ値
	 */
	public function addSchedule($userId, $title, $time, $detail){
		$stmt = $this->pdo->prepare( 'INSERT INTO schedules
			(schedule_id, user_id, title, todo_time, detail, posted_at)
			VALUES(:scheduleId, :userId, :title, :todoTime, :detail, :postedAt);');

		$plainText = $time . $title . $detail . $userId;
		$hash = hash('adler32', $plainText, false);

		//ハッシュが重複する場合はfalseを返す
		if($this->scheduleExists($hash)){
			return false;
		}

		$stmt->bindValue(':scheduleId', $hash);
		$stmt->bindValue(':userId', $userId);
		$stmt->bindValue(':title', $title);
		$stmt->bindValue(':todoTime', $time);
		$stmt->bindValue(':detail', $detail);
		$stmt->bindValue(':postedAt', date('Y-m-d H:i:s'));

		if($stmt->execute()){
			return $hash;
		}

		return false;

	}

	/**
	 * 同じハッシュ値が登録されているかをみてスケジュールの重複を確認する
	 *
	 * @param  string $hash スケジュールID
	 * @return boolean      存在するならTrue,存在しなければfalseを返す
	 */
	public function scheduleExists($hash){
		$stmt = $this->pdo->prepare('SELECT * FROM schedules WHERE schedule_id = :hash');
		$stmt->bindValue(':hash', $hash);
		$stmt->execute();
		if(!empty($stmt->fetch(PDO::FETCH_ASSOC))){
			return true;
		}
		else{
			return false;
		}
	}

	/**
	 * DB上からユーザーデータを削除する
	 *
	 * @param string userId ユーザーID(LINEのIDではない)
	 */
	public function deleteUser($userId){
		$stmt = $this->pdo->prepare('DELETE FROM users WHERE user_id = :userId');
		$stmt->bindValue(':userId', $userId);
		$stmt->execute();
	}

	/**
	 * DB上からスケジュールデータを削除する
	 *
	 * @param int scheduleId スケジュールID
	 */
	public function deleteSchedule($scheduleId){
		$stmt = $this->pdo->prepare('DELETE FROM schedules WHERE schedule_id = :scheduleId');
		$stmt->bindValue(':scheduleId', $scheduleId);
		$stmt->execute();
	}

	/**
	 * 実行時から指定する分数までの範囲でスケジュールを検索し、結果を返す
	 *
	 * @param  int   $range  現在時から何分後までのスケジュールを検索するかを指定(分単位)
	 * @return array $result fetchAll()で取得した配列をそのまま返す
	 */
	public function getSchedules($currentTime, $range){
		$stmt = $this->pdo->prepare('SELECT * FROM schedules,users WHERE todo_time BETWEEN :currentTime AND :searchTime;');

		$endTime = strtotime("+{$range} minute", strtotime($currentTime));
		$endTime = date('Y-m-d H:i:s', $endTime);

		$stmt->bindValue(':currentTime', $currentTime);
		$stmt->bindValue(':searchTime', $endTime);
		$stmt->execute();
		$result = $stmt->fetchAll();

		return $result;
	}
}