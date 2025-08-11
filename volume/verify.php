<?php
require_once '../lib/nusoap.php';
require_once '../config.php';
include_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$token = base64_decode($_GET['token']);
$input = explode('#',$token);
$server_id = $input[0];
$inbound_id = $input[1];
$remark = $input[2];
$planid = $input[3];
$userid = $input[4];
$res = $telegram->db->query("select * from extra_plan where id=$planid")->fetch(2);  
$price = $res['price'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
$volume = $res['volume'];
$time = time();

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
        $response = update_client_traffic($server_id, $inbound_id, $remark, $volume, 0);
    else
        $response = update_inbound_traffic($server_id, $remark, $volume, 0);
    if($response->success){
        $telegram->db->query("update fl_order set notif=0 where remark='$remark'");
        $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
        $telegram->sendMessage($userid, "✅$volume گیگ به حجم سرویس شما اضافه شد");
		$sndmsg = "
خرید $volume گیگ با درگاه زرین
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
ریمارک : $remark
";
    $telegram->sendMessage($sendchnl,$sndmsg);
    }else {
        die("مشکل فنی در ارتباط با سرور. لطفا به مدیریت اطلاع بدید");
    }


    } elseif($result['Status'] == '101'){
        echo '<h1 style="text-align:right;color:green;">تراکنش شما از قبل تایید شده. لطفا از این صفحه خارج بشید و به ربات برگردید :</h1>';
    } else {
        echo 'خطایی در هنگام پرداخت بوجود آمد. لطفا دوباره تلاش کنید. در صورت کسر وجه، در عرض 24 ساعت به حساب شما برگشت داده می شود'.$result['Status'];
    }
} else {
    echo 'پرداخت توسط کاربر کنسل شد و تایید شده نمی باشد';
}
