# line-notification

LINEのBOTに対して
- タイトル
- 通知日時
- 内容

を送信すると、指定の通知日時のおおよそ10分前ぐらいににリマインドしてくれるbotを開発しています。  

## 使い方

[動作デモムービーはこちらから(Google Drive共有ページに飛びます)](https://drive.google.com/file/d/1h97PMX_6NUH3kkkUVMy4kAvol_h9EuCX/view?usp=sharing)  
画面キャプチャーには[GeForce ShadowPlay](https://www.nvidia.com/ja-jp/geforce/geforce-experience/shadowplay/)、
動画編集には[Shotcut](https://www.shotcut.org/)を利用させていただきました 。

BOTに対して
* 件名※
* 日時※
* 詳細
をそれぞれ行区切りで送ります(※がある項目は必須)

↓例

>ランチを食べに行く  
>2019年4月1日 12:30  
>札幌駅の近くで適当に探す  

また、日時に関して、
年は省略することで自動的に現在の年が適応されます  
年月日と時刻の間はスペースで区切る必要があります  
年月日は「年月日」,「Y/m/d」,「Y-m-d」での表記に対応  
時間は「時分（24時間表記）」,「hh:mm(24hour)」に対応しています

## 注意点
Heroku Schedulerを使用しているため、  
毎時00,10,20,30,40,50分のタイミングで直前10分以内の予定が通知されます

現在は問い合わせNoを利用する機能は実装されていません。   
今後、予定の取り消しや、予定の一覧表示などの機能を追加する予定です。

## 開発環境等

| | |
|:-:|:-:|
|使用言語|PHP7.2|
|実行環境|Heroku|
|DB|PostgresSQL(Heroku側で用意したAWS上で動作)|
|エディタ|SublimeText3|
|バージョン管理|Git|

## とても重要なこと
**このBOTを利用してデータベースに登録されたLINEのID及びスケジュールデータですが  
開発者はいつでも見れてしまうという点をご留意ください  
※DBのデータ閲覧は正常な動作の確認及び、機能のテスト時に限り行い、  
第三者に情報を提供することはありません**
