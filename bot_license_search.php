<?php

include __DIR__.'/vendor/autoload.php';

$discord = new \Discord\Discord(['token' => '******************************************',]);

$discord->on('ready', function ($discord) {
	echo "Bot is ready.", PHP_EOL;

	$botUser = $discord->user;
	$discord->on('message', function($message) use ($botUser){	//テキストチャット着信イベント
		echo "Recieved a message from {$message->author->username}\n{$message->content}\n";
		
		if($message->author->user->id !== $botUser->id) {	//自分自身の発言を除外
			$msg = nmz($message->content);
			if (str_starts_with($msg, "/call ")){	//コールサイン検索コマンド判定
				$cmd_arr = explode(" ", $msg);
				$message->reply(mic_get($cmd_arr[1]));
			}
		}
	});

});

$discord->run();

exit;
////////// ////////// ////////// ////////// ////////// ////////// ////////// ////////// ////////// //////////

//無線局免許情報検索
function mic_get($callsign){
	$curl = curl_init("https://www.tele.soumu.go.jp/musen/list?ST=1&DA=1&SC=1&DC=1&OF=2&OW=AT&MA=" . $callsign);
	curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($curl, CURLOPT_HTTPHEADER, []);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$result = curl_exec($curl);
	$arr = json_decode($result,true); var_dump($arr);
	curl_close($curl);
	
	$res = "アマチュア無線局が見つかりました\n";
	$res = $res . "```json\n";
	$res = $res . "呼出符号: " . $arr["musen"][0]["detailInfo"]["identificationSignals"] ."\n";
	$res = $res . "　　名称: " . nmz($arr["musen"][0]["detailInfo"]["name"], "as", "utf8") . "\n";
	$arr["musen"][0]["detailInfo"]["validTerms"] = preg_replace('/[^a-zA-Z0-9_-]/', '', $arr["musen"][0]["detailInfo"]["validTerms"]);	//「まで」を除去
	$d1 = new DateTime("now");
	$d1 -> setTime(0,0,0);
	$d2 = new DateTime($arr["musen"][0]["detailInfo"]["validTerms"]);
	$interval = $d2->diff($d1);
	$res = $res . "免許期間: " . $arr["musen"][0]["detailInfo"]["licenseDate"] . " => " . preg_replace('/[^a-zA-Z0-9_-]/', '', $arr["musen"][0]["detailInfo"]["validTerms"]) . " (" . $interval->format("残り%y年%m月%d日") . ")" . "\n";
	$res = $res . "常置場所: " . nmz($arr["musen"][0]["detailInfo"]["radioEuipmentLocation"]) . "\n";
	$res = $res . "移動範囲: " . $arr["musen"][0]["detailInfo"]["movementArea"] ."\n";
	$res = $res . "　周波数: ";
	$radioSpec_arr = explode("\\n", $arr["musen"][0]["detailInfo"]["radioSpec1"]);
	foreach($radioSpec_arr as $index => $tmp1){
		$tmp1 = str_replace(" ", "", $tmp1);
		$tmp2 = explode("\\t", $tmp1);
		if($tmp2[1] != ""){
			$res = $res . nmz($tmp2[1] . "(" . $tmp2[2] . ")");
			if ($index != array_key_last($radioSpec_arr)){ $res = $res . ", "; }
		}
	}
	$res = $res . "```\n";
	return $res;
}

//文字列正規化
function nmz($text){
	$tmp = mb_convert_kana($text, "as", "utf8");	//英数スペースの半角変換
	$tmp = trim($tmp);	//前後スペース除去
	$tmp = preg_replace("/[\s]+/", " ", $tmp);	//連続スペースの除去
	$tmp = str_replace("\\n", " ", $tmp);	//改行らしき文字の除去
	return $tmp;
}
