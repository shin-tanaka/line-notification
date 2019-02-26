<?php

require_once('./MessagingAPI.php');
require_once('./DbManager.php');

$channelAccessToken = getenv('ACCESS_TOKEN');
$channelSecret = getenv('CHENNEL_SECRET');

$dbm = new DbManager();
$client = new MessagingAPI($channelAccessToken, $channelSecret);

//DBから現在時刻に近い予定を抽出する


//予定の