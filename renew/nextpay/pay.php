<?php ob_start();
require_once '../../lib/nusoap.php';
require_once '../../config.php';
require_once '../../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
if(!isset($_GET['token'])){
    header('location:https://t.me/'.botid);
}
$token = base64_decode($_GET['token']);
$token = explode('#',$token);
$userid = intval($token[0]);
$fid = intval($token[1]);
$oid = intval($token[2]);

$order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
$remark = $order['remark'];
$server_id = $order['server_id'];
$inbound_id = $order['inbound_id'];
$file_detail = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
$days = $file_detail['days'];
$expire_date = $date + (86400 * $days);
$protocol = $file_detail['protocol'];
$price = $file_detail['price'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
$server_id = $file_detail['server_id'];
$acount = $file_detail['acount'];
$inbound_id = $file_detail['inbound_id'];

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

    $MerchantID = NEXTPAYID;  // Required
    $Amount = $price; // Amount will be based on Toman  - Required
    $Description = DESC;  // Required
    $token = base64_encode("$userid#$fid#$oid");
    $CallbackURL = baseURI."/renew/nextpay/verify.php?token=$token";  // Required


    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://nextpay.org/nx/gateway/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'api_key='.$MerchantID.'&amount='.$Amount.'&order_id='.$order_id.'&currency=IRT&callback_uri='.$CallbackURL,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($response);

    //Redirect to URL You can do it also by creating a form
    if ($response->code == '-1'){
        $startGateWayUrl = "https://nextpay.org/nx/gateway/payment/".$response->trans_id;
        header('location: '.$startGateWayUrl); exit;
    } else {
        echo'تراکنش با خطا مواجه شد';
		var_dump($response);
    }
