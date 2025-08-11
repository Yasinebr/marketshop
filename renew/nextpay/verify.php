<?php
require_once '../../lib/nusoap.php';
require_once '../../config.php';
require_once '../../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$token = base64_decode($_GET['token']);
$token = explode('#',$token);
$userid = intval($token[0]);
$fid = intval($token[1]);
$oid = intval($token[2]);
$orderDetails = $telegram->db->query("select * from fl_order where id={$oid}")->fetch(2);
$userid = $orderDetails['userid'];
$amount = $orderDetails['amount'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $amount = $amount - (($amount) * ($seller['percent'] / 100));
$transid = $orderDetails['transid'];
$remark = $orderDetails['remark'];
$file_detail = $telegram->db->query("select * from fl_file WHERE id=$fid")->fetch(2);
$days = $file_detail['days'];
$date = time();
$expire_microdate = intval(floor(microtime(true) * 1000) + (864000 * $days * 100));
$expire_date = $date + (86400 * $days);
$type = $file_detail['type'];
$volume = $file_detail['volume'];
$protocol = $file_detail['protocol'];
//$price = $file_detail['price'];
$server_id = $file_detail['server_id'];
$netType = $file_detail['type'];
$acount = $file_detail['acount'];
$inbound_id = $file_detail['inbound_id'];

$price = $orderDetails['amount'];

if(isset($token[3])){
    $dcode = htmlspecialchars(strip_tags($token[3]));
    $dcount = $telegram->db->query("select * from fl_discount WHERE code='$dcode' and active=1");
    if($dcount->rowCount() > 0){
        $amount = $dcount->fetch(2)['amount'];
        if($amount <= 100) {
            $price = $price * (100-$amount)/100;
        }else {
            $price = $price - $amount ;
        }
    }
}

$time = $date = time();

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => 'https://nextpay.org/nx/gateway/verify',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_POSTFIELDS => 'api_key='.NEXTPAYID.'&amount='.$amount.'&currency=IRT&trans_id='.$transid,
));

$response = curl_exec($curl);
curl_close($curl);
$response = json_decode($response);


if ($response->code=='0') {
    $refid = ltrim($Authority, "0");
        echo 'تراکنش با موفقیت انجام شد . لطفا به ربات برگردید : ';
// V2ray Api
    require_once('../../vray.php');
    if($inbound_id > 0)
        $response = renew_client($server_id, $inbound_id, $remark, $volume, $days);
    else
        $response = renew_inbound($server_id, $remark, $volume, $days);

    $telegram->db->query("update fl_order set expire_date= $expire_date + $days * 86400,notif=0 where id='$oid'"); 
    $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
	$sndmsg = "
تمدید سرویس $remark با نکست
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
تعداد روز  $days
تعداد گیگ $volume
";
    $telegram->sendMessage($sendchnl,$sndmsg);
    $telegram->sendMessage($userid, "✅سرویس $remark با موفقیت تمدید شد");exit;

    
} else {
    echo 'پرداخت ناموفق است. لطفا درگاه دیگری را تست کنید';
}
