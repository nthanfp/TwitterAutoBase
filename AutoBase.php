<?php
error_reporting(0);
date_default_timezone_set('Asia/Jakarta');
require('./config.php');
require('./lib/vendor/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

function saveData($saveFile, $data)
{
    $x = $data . "\n";
    $y = fopen($saveFile, 'a');
    fwrite($y, $x);
    fclose($y);
}

function saveCookie($saveFile, $data)
{
    $x = $data;
    $y = fopen($saveFile, 'w');
    fwrite($y, $x);
    fclose($y);
}

function banner()
{
    echo "
                _          ____                 
     /\        | |        |  _ \                
    /  \  _   _| |_ ___   | |_) | __ _ ___  ___ 
   / /\ \| | | | __/ _ \  |  _ < / _` / __|/ _ \
  / ____ \ |_| | || (_) | | |_) | (_| \__ \  __/
 /_/    \_\__,_|\__\___/  |____/ \__,_|___/\___|
                                                
                                                \n";
    echo "============= Twitter Auto Base 1.0 ==============\n";
    echo "================ Made by @nthanfp ================\n\n";
}
banner();
$connection = new TwitterOAuth(CONSUMER_KEY, CONSUMER_SECRET, access_token, access_token_secret);
$connection->setDecodeJsonAsArray(true);
do {
    $getDm  = $connection->get('direct_messages/events/list');
    if(count($getDm['events']) > 0) {
        $logs = file_get_contents('./data/dataDm.txt');
        echo "[~] Found ".count($getDm['events'])." message\n";
        foreach($getDm['events'] as $item){
            $dmText = $item['message_create']['message_data']['text'];
            $dmId   = $item['id'];
            $userId = $item['message_create']['sender_id'];
            $img    = $item['message_create']['message_data']['attachment']['media']['media_url'];
            if(strpos($logs, $dmId) == false){
                echo "[~][".$dmId."][".date('d-m-Y H:i:s')."] Message: ".$dmText."\n";
                if(strpos($dmText, KEYWORD) !== false){
                    echo "[~][".$dmId."][".date('d-m-Y H:i:s')."] Message with keyword\n";
                    if($img == null){
                        $sendTweet = $connection->post("statuses/update", ["status" => $dmText]);
                        if($sendTweet['id_str'] !== null){
                            echo "[~][".$dmId."][".date('d-m-Y H:i:s')."] Tweeted: ".$sendTweet['id_str']."\n";
                            $data = ['event' => ['type' => 'message_create', 'message_create' => ['target' => ['recipient_id' => $userId], 'message_data' => ['text' => 'Menfess kamu sudah terkirim yaa :) https://twitter.com/gabutmf/status/'.$sendTweet['id_str']]]]];
                            $sendDm = $connection->post('direct_messages/events/new', $data, true);
                        } else {
                            print_r($sendTweet);
                        }
                    } else {
                        echo "[~][".$dmId."][".date('d-m-Y H:i:s')."] Message with picture\n";
                        $pecah      = explode("https://", trim($dmText));
                        $tweet      = trim($pecah[0]);
                        $img        = $connection->file($item['message_create']['message_data']['attachment']['media']['media_url'].':large');
                        $filename   = './data/img/'.time().'.jpg';
                        $save_img   = file_put_contents($filename, $img);
                        $sendMedia  = $connection->upload('media/upload', ['media' => $filename]);
                        $sendTweet  = $connection->post('statuses/update', ['status' => $tweet, 'media_ids' => $sendMedia['media_id_string']]);
                        if($sendTweet['id_str'] !== null){
                            echo "[~][".$dmId."][".date('d-m-Y H:i:s')."] Tweeted: ".$sendTweet['id_str']."\n";
                            $data = ['event' => ['type' => 'message_create', 'message_create' => ['target' => ['recipient_id' => $userId], 'message_data' => ['text' => 'Menfess kamu sudah terkirim yaa :) https://twitter.com/gabutmf/status/'.$sendTweet['id_str']]]]];
                            $sendDm = $connection->post('direct_messages/events/new', $data, true);
                        } else {
                            print_r($sendTweet);
                        }
                    }
                } else {
                    echo "[~][".$dmId."][".date('d-m-Y H:i:s')."] Message without keyword: ".$dmText."\n";
                }
                saveData('./data/dataDm.txt', $dmId);
            } else {
                echo "[~][".$dmId."][".date('d-m-Y H:i:s')."] Message has already in log\n";
            }
        }
    } else {
        echo "[!] No message received\n";
    }
    echo "\n";
    sleep(SPEED);
} while (true);
