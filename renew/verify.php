<?php
require_once '../lib/nusoap.php';
require_once '../config.php';
include_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$token = base64_decode($_GET['token']);
$input = explode('#',$token);
$userid = intval($input[0]);
$fid = intval($input[1]);
$oid = intval($input[2]);
$order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
//$fid = $order['fileid'];
$remark = $order['remark'];
$server_id = $order['server_id'];
$inbound_id = $order['inbound_id'];
$expire_date = $order['expire_date'];
$expire_date = ($expire_date > $time) ? $expire_date : $time;
$respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
$name = $respd['title'];
$days = $respd['days'];
$volume = $respd['volume'];
$price = $respd['price'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
$time = time();

if(isset($input[3])){
    $dcode = htmlspecialchars(strip_tags($input[3]));
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

$Authority = $_GET['Authority'];
if ($_GET['Status'] == 'OK') {
    $client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
    $client->soap_defencoding = 'UTF-8';
    $result = $client->call('PaymentVerification', [
        [
            'MerchantID'     => ZARINPALMID,
            'Authority'      => $Authority,
            'Amount'         => $price,
        ],
    ]);

    if ($result['Status'] == 100) {
        $refid = ltrim($Authority, "0");
        echo 'تراکنش با موفقیت انجام شد. کد پیگیری شما: ' . $refid;
        $msg = "
✅ سفارش شما با موفقیت ثبت شد.
📝 شماره تراکنش: $refid
";
        $telegram->sendMessage($userid, $msg);

    require_once('../vray.php');
    if($inbound_id > 0)
        $response = renew_client($server_id, $inbound_id, $remark, $volume, $days);
    else
        $response = renew_inbound($server_id, $remark, $volume, $days);

    $telegram->db->query("update fl_order set expire_date= $expire_date + $days * 86400,notif=0,fileid=$fid where id='$oid'"); 
    $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
	$sndmsg = "
تمدید سرویس $remark با زرین
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
تعداد روز  $days
تعداد گیگ $volume
";
    $telegram->sendMessage($sendchnl,$sndmsg);
    $telegram->sendMessage($userid, "✅سرویس $remark با موفقیت تمدید شد");exit;


    }elseif($result['Status'] == '101'){
        echo '<h1 style="text-align:right;color:green;">تراکنش شما از قبل تایید شده. لطفا از این صفحه خارج بشید و به ربات برگردید :</h1>';
    } else {
        echo 'خطایی در هنگام پرداخت بوجود آمد. لطفا دوباره تلاش کنید. در صورت کسر وجه، در عرض 24 ساعت به حساب شما برگشت داده می شود'.$result['Status'];
    }
} else {
    echo 'پرداخت توسط کاربر کنسل شد و تایید شده نمی باشد';
}
