<?php
require_once 'lib/nusoap.php';
require_once 'config.php';
include_once 'telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$order_id = intval(base64_decode($_GET['token']));
$orderDetails = $telegram->db->query("select * from fl_order where id={$order_id}")->fetch(2);
$userid = $orderDetails['userid'];
$fid = $orderDetails['fileid'];
$file_detail = $telegram->db->query("select * from fl_file WHERE id=$fid")->fetch(2);
$days = $file_detail['days'];
$date = time();
$expire_microdate = intval(floor(microtime(true) * 1000) + (864000 * $days * 100));
$expire_date = $date + (86400 * $days);
$type = $file_detail['type'];
$volume = $file_detail['volume'];
$protocol = $file_detail['protocol'];
$price = $file_detail['price'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
$server_id = $file_detail['server_id'];
$netType = $file_detail['type'];
$acount = $file_detail['acount'];
$inbound_id = $file_detail['inbound_id'];
$limitip = $file_detail['limitip'];

$price = $orderDetails['amount'];

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

    // V2ray Api
    require_once('vray.php');
    $uniqid = generateRandomString(42,$protocol); 

    $savedinfo = file_get_contents('savedinfo.txt');
    $savedinfo = explode('-',$savedinfo);
    $port = $savedinfo[0] + 1;
    $last_num = $savedinfo[1] + 1;

    $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
    $remark = "{$srv_remark}-{$last_num}";

    file_put_contents('savedinfo.txt',$port.'-'.$last_num);
    //$telegram->sendMessage($cuserid,"$server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume");exit;
    
    if($inbound_id == 0){    
        $response = add_inbound($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType); 
    }else {
        $response = add_inbount_client($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip); 
    }
    
    //$telegram->sendMessage($cuserid,json_encode($response));exit;
     
    $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
    
    $acc_text = "🔗 $remark \n <code>$vray_link</code>" . " \n  برای کپی کردن لینک روی آن کلیک کنید \n";
    
    
    include 'phpqrcode/qrlib.php';
    $path = 'images/';
    $file = $path.$userid.".png"; //unlink($file);
    $ecc = 'L';
    $pixel_Size = 10;
    $frame_Size = 5;
    QRcode::png($vray_link, $file, $ecc, $pixel_Size, $frame_Size);
    $msg = "
✅سفارش شما با موفقیت ثبت شد .
📝 شماره تراکنش : $refid
.";
        $telegram->sendMessage($userid, $msg);
		
	$telegram->sendPhoto($userid,$acc_text,$file);
$sndmsg = "
خرید درگاه زرین 
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
لینک دریافتی :

$vray_link
";
    $telegram->sendMessage($sendchnl,$sndmsg);
	$order = $telegram->db->query("update `fl_order` set link='$vray_link',remark='$remark',status=1 where id=$order_id");
	
	if($inbound_id == 0) {
        $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
        if($server_info['ucount'] != 0)
            $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
    }else{
        if($acount != 0) 
            $telegram->db->query("UPDATE `fl_file` SET `acount` = acount - 1 WHERE id=$fid");
    }
    
	echo '<h1 style="text-align:right;color:green;">تراکنش با موفقیت انجام شد . لطفا به ربات برگردید :</h1>';
        $telegram->db->query("update fl_order set status=1 where id='$order_id'");
        
		
    // pay referer
    $userReferer = $telegram->db->query("select * from fl_subuser where userid=$userid");
    if($userReferer->rowCount() ){
        $ures = $userReferer->fetch(2);
        $userToplevel = $ures['toplevel_userid'];
        $ufname = $ures['fname'];
        $amount = ($price) * ($pursant / 100);
        $telegram->db->query("update fl_user set wallet= wallet + $amount WHERE userid=$userToplevel");
        $telegram->sendMessage($userToplevel, "💟کاربر {$ufname} یک خرید به مبلغ  $price تومان انجام داد و $pursant درصد آن یعنی $amount تومان به کیف پول شما اضافه شد👍"); 
    }


    }elseif($result['Status'] == '101'){
        echo '<h1 style="text-align:right;color:green;">تراکنش شما از قبل تایید شده. لطفا از این صفحه خارج بشید و به ربات برگردید :</h1>';
    } else {
        echo 'خطایی در هنگام پرداخت بوجود آمد . لطفا دوباره تلاش کنید . در صورت کسر وجه ، در عرض 24 ساعت به حساب شما برگشت داده می شود'.$result['Status'];
    }
} else {
    echo 'پرداخت توسط کاربر کنسل شد و تایید شده نمی باشد';
}
