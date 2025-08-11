<?php
require_once '../../lib/nusoap.php';
require_once '../../config.php';
include_once '../../telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$woid = intval($_GET['woid']);
if($woid == 0) exit;
$res = $telegram->db->query("select * from fl_wallet where id='$woid'")->fetch(2);
$amount = $res['amount'];
$userid = $res['userid'];
$transid = $res['trans_id'];
if($res['status'] == 1) exit;
    $MerchantID = NEXTPAYID;
    $Amount = $amount; //Amount will be based on Toman
    $Authority = $_GET['Authority'];
    
    
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://nextpay.org/nx/gateway/verify',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'api_key='.$MerchantID.'&amount='.$amount.'&currency=IRT&trans_id='.$transid,
    ));

    $response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($response);

    if ($response->code=='0') {
		$refid = $result['RefID'];
            echo '<h1 style="text-align:right;color:green;">کیف پول شما با موفقیت شارژ شد . کد پیگیری: </h1>' . $refid; 
            $telegram->db->query("update fl_user set wallet= wallet + $amount where userid='$userid'");
            $telegram->db->query("update fl_wallet set status=1 where id=$woid");
            $msg = "✅ کیف پول شما به مبلغ $amount تومان شارژ شد .";
            $telegram->sendMessage($userid, $msg);
			$sndmsg = "
افزایش موجودی با درگاه نکست
مقدار : $amount تومان
آیدی کاربر : $userid
";
    $telegram->sendMessage($sendchnl,$sndmsg);
    } else {
        echo 'خطایی در هنگام پرداخت بوجود آمد . لطفا دوباره تلاش کنید . در صورت کسر وجه ، در عرض 24 ساعت به حساب شما برگشت داده می شود';
    }
