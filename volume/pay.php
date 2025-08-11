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
$server_id = intval($input[0]);
$inbound_id = intval($input[1]);
$remark = htmlspecialchars(strip_tags($input[2]));
$planid = intval($input[3]);
$userid = intval($input[4]); 
$res = $telegram->db->query("select * from extra_plan where id=$planid")->fetch(2);  
$price = $res['price'];
$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
$volume = $res['volume'];

    $MerchantID = ZARINPALMID;  // Required
    $Amount = $price; // Amount will be based on Toman  - Required
    $Description = DESC;  // Required
    $token = base64_encode($token);
    $CallbackURL = baseURI."/volume/verify.php?token=".$token;  // Required


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
