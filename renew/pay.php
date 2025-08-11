<?php ob_start();
require_once '../lib/nusoap.php';
require_once '../config.php';
require_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
if(!isset($_GET['token'])){
    header('location:https://t.me/'.botid);
    exit;
}

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
$respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
$name = $respd['title'];
$days = $respd['days'];
$volume = $respd['volume'];
$price = $respd['price'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));

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

    $MerchantID = ZARINPALMID;  // Required
    $Amount = $price; // Amount will be based on Toman  - Required
    $Description = DESC;  // Required
    $token = base64_encode($token);
    $CallbackURL = baseURI."/renew/verify.php?token=".$token;  // Required


    $client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
    $client->soap_defencoding = 'UTF-8';
    $result = $client->call('PaymentRequest', [
        [
            'MerchantID'     => $MerchantID,
            'Amount'         => $Amount,
            'Description'    => $Description,
            'Email'          => '',
            'Mobile'         => '',
            'CallbackURL'    => $CallbackURL,
        ],
    ]);

    //Redirect to URL You can do it also by creating a form
    if ($result['Status'] == 100) {
        header('Location: https://www.zarinpal.com/pg/StartPay/'.$result['Authority']);
    } else {
        echo'تراکنش با خطا مواجه شد';
    }
