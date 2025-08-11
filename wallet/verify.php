<?php
require_once '../lib/nusoap.php';
require_once '../config.php';
include_once '../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$woid = intval($_GET['woid']);
if($woid == 0) exit;
$res = $telegram->db->query("select * from fl_wallet where id='$woid'")->fetch(2);
$amount = $res['amount'];
$userid = $res['userid'];
if($res['status'] == 1) exit;
    $MerchantID = ZARINPALMID;
    $Amount = $amount; //Amount will be based on Toman
    $Authority = $_GET['Authority'];

    if ($_GET['Status'] == 'OK') {
        $client = new nusoap_client('https://www.zarinpal.com/pg/services/WebGate/wsdl', 'wsdl');
        $client->soap_defencoding = 'UTF-8';
        $result = $client->call('PaymentVerification', [
            [
                'MerchantID'     => $MerchantID,
                'Authority'      => $Authority,
                'Amount'         => $Amount,
            ],
        ]);

        if ($result['Status'] == 100) {
			$refid = $result['RefID'];
                echo '<h1 style="text-align:right;color:green;">کیف پول شما با موفقیت شارژ شد . کد پیگیری: </h1>' . $refid; 
                $telegram->db->query("update fl_user set wallet= wallet + $amount where userid='$userid'");
                $telegram->db->query("update fl_wallet set status=1 where id=$woid");
                $msg = "
✅ کیف پول شما به مبلغ $amount تومان شارژ شد .
📝 شماره تراکنش : $refid
			";
                $telegram->sendMessage($userid, $msg);
				$sndmsg = "
افزایش موجودی با درگاه زرین
مقدار : $amount تومان
آیدی کاربر : $userid
";
    $telegram->sendMessage($sendchnl,$sndmsg);
        }elseif($result['Status'] == '101'){
			echo '<h1 style="text-align:right;color:green;">تراکنش شما از قبل تایید شده. لطفا از این صفحه خارج بشید و به ربات برگردید :</h1>';
		} else {
            echo 'خطایی در هنگام پرداخت بوجود آمد . لطفا دوباره تلاش کنید . در صورت کسر وجه ، در عرض 24 ساعت به حساب شما برگشت داده می شود'.$result['Status'];
        }
    } else {
        echo 'پرداخت توسط کاربر کنسل شد و تایید شده نمی باشد';
    }
