<?php ob_start();
require_once 'lib/nusoap.php';
require_once 'config.php';
require_once 'telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
if(!isset($_GET['token'])){
    header('location:https://t.me/'.botid);
    exit;
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

    $MerchantID = ZARINPALMID;  // Required
    $Amount = $price; // Amount will be based on Toman  - Required
    $Description = DESC. " u$userid | o$order_id";  // Required
    $token = base64_encode($order_id);
    $CallbackURL = baseURI."/verify.php?token=$token";  // Required


    $client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
    $client->soap_defencoding = 'UTF-8';
    $result = $client->call('PaymentRequest', [
        [
            'MerchantID'     => $MerchantID,
            'Amount'         => $Amount,
            'Description'    => $Description,
            'Email'          => $Email,
            'Mobile'         => $Mobile,
            'CallbackURL'    => $CallbackURL,
        ],
    ]);

    //Redirect to URL You can do it also by creating a form
    if ($result['Status'] == 100) {
        $telegram->db->query("UPDATE `fl_order` SET `transid` = '{$result['Authority']}' where id= $order_id");
        header('Location: https://www.zarinpal.com/pg/StartPay/'.$result['Authority']);
    } else {
        echo'تراکنش با خطا مواجه شد';
    }
