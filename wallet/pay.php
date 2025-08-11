<?php
require_once '../lib/nusoap.php';
require_once '../config.php';
include_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$amount = intval($_GET['amount']);
if($amount < $min_wallet_charge) exit;
$userid = intval($_GET['userid']);
$result = $telegram->db->query("select * from fl_user WHERE userid='$userid'")->rowCount();
if($result == 0) exit;
$telegram->db->query("INSERT INTO `fl_wallet` VALUES (NULL, $amount, $userid, '','0');");
$woid = $telegram->db->lastInsertId();

    $MerchantID = ZARINPALMID;  //Required
    $Amount = $amount; //Amount will be based on Toman  - Required
    $Description = DESC. " u$userid |w$woid";  // Required
    $Email = ''; // Optional
    $Mobile = ''; // Optional
    $CallbackURL = baseURI."/wallet/verify.php?woid=$woid";  // Required


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
        header('Location: https://www.zarinpal.com/pg/StartPay/'.$result['Authority']);
    } else {
        echo'تراکنش با خطا مواجه شد';
    }
