<?php
require_once '../../lib/nusoap.php';
require_once '../../config.php';
include_once '../../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$amount = intval($_GET['amount']);
if($amount < $min_wallet_charge) exit;
$userid = intval($_GET['userid']);
$result = $telegram->db->query("select * from fl_user WHERE userid='$userid'")->rowCount();
if($result == 0) exit;
$telegram->db->query("INSERT INTO `fl_wallet` VALUES (NULL, $amount, $userid, '','0');");
$woid = $telegram->db->lastInsertId();

    $MerchantID = NEXTPAYID;  //Required
    $Amount = $amount; //Amount will be based on Toman  - Required
    $Description = DESC. " u$userid |w$woid";  // Required
    $Email = ''; // Optional
    $Mobile = ''; // Optional
    $CallbackURL = baseURI."/wallet/next/verify.php?woid=$woid";  // Required


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
        CURLOPT_POSTFIELDS => 'api_key='.$MerchantID.'&amount='.$Amount.'&order_id='.$woid.'&currency=IRT&callback_uri='.$CallbackURL,
    ));
    $response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($response);

    //Redirect to URL You can do it also by creating a form
    if ($response->code == '-1'){
        $telegram->db->query("UPDATE `fl_wallet` SET `trans_id` = '{$response->trans_id}' where id= $woid");
        $startGateWayUrl = "https://nextpay.org/nx/gateway/payment/".$response->trans_id;
        header('location: '.$startGateWayUrl); exit;
    }else {
        echo'تراکنش با خطا مواجه شد';
		var_dump($response);
    }
