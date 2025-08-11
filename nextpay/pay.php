<?php ob_start();
require_once '../lib/nusoap.php';
require_once '../config.php';
require_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
if(!isset($_GET['token'])){
    header('location:https://t.me/'.botid);
}
$token = base64_decode($_GET['token']);
$token = explode('.',$token);
$userid = intval($token[0]);
$fid = intval($token[1]);
if($token[2]) $dcode = htmlspecialchars(strip_tags($token[2]));

$file_detail = $telegram->db->query("select * from fl_file WHERE id=$fid")->fetch(2);
$date = time();
$days = $file_detail['days'];
$expire_date = $date + (86400 * $days);
$protocol = $file_detail['protocol'];
$price = $file_detail['price'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
$server_id = $file_detail['server_id'];
$acount = $file_detail['acount'];
$inbound_id = $file_detail['inbound_id'];
 
if($dcode){
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

if($acount == 0 and $inbound_id != 0){
    echo 'ظرفیت این کانکشن پر شده است';
    exit;
}

$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$userid}, '', {$fid}, $server_id, $inbound_id, '', '$protocol', $expire_date, '', $price,0, '$date', 0);");
$order_id = $telegram->db->lastInsertId();

    $MerchantID = NEXTPAYID;  // Required
    $Amount = $price; // Amount will be based on Toman  - Required
    $Description = DESC;  // Required
    $token = base64_encode($order_id);
    $CallbackURL = baseURI."/nextpay/verify.php?token=$token";  // Required


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
        $telegram->db->query("UPDATE `fl_order` SET `transid` = '{$response->trans_id}' where id= $order_id");
        $startGateWayUrl = "https://nextpay.org/nx/gateway/payment/".$response->trans_id;
        header('location: '.$startGateWayUrl); exit;
    } else {
        echo'تراکنش با خطا مواجه شد';
		var_dump($response);
    }
