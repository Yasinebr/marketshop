<?php ob_start();
require_once 'lib/nusoap.php';
require_once 'config.php';
require_once 'telegram.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);

$TokenCode = BAHAMTAMD;

if(!isset($_GET['action'],$_GET['type'])) exit;


$action = $_GET['action'];
$type = $_GET['type'];
$callback_url = baseURI."/bahamta.php?type=$type&action=verify";

if($type == 'renew'){
    if($action == 'pay'){
        $token = base64_decode($_GET['token']);
        $input = explode('#',$token); 
        $userid = intval($input[0]);
        $oid = intval($input[1]);
        $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
        $fid = $order['fileid'];
        $remark = $order['remark'];
        $server_id = $order['server_id'];
        $inbound_id = $order['inbound_id'];
        $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
        $name = $respd['title'];
        $days = $respd['days'];
        $volume = $respd['volume'];
        $price = $respd['price'];
        bahamta_payment($price, $TokenCode, base64_encode($token), $callback_url);
    }elseif($action == 'verify'){
        $token = intval($_POST['clientrefid']);
        if(!$token) exit;
        
        $input = explode('#',$token);
        $userid = $input[0];
        $oid = $input[1];
        $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
        $fid = $order['fileid'];
        $remark = $order['remark'];
        $server_id = $order['server_id'];
        $inbound_id = $order['inbound_id'];
        $expire_date = $order['expire_date'];
        $expire_date = ($expire_date > $time) ? $expire_date : $time;
        $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
        $name = $respd['title'];
        $days = $respd['days'];
        $volume = $respd['volume'];
        $price = $respd['price'];
        $time = time();

        $pay_result = bahamta_verify($price, $TokenCode);
        if($pay_result == 'success'){
            echo 'تراکنش با موفقیت انجام شد';
            $msg = "✅ سفارش شما با موفقیت ثبت شد";
            $telegram->sendMessage($userid, $msg);
    
            require_once('vray.php');
            if($inbound_id > 0)
                $response = update_client_traffic($server_id, $inbound_id, $remark, $volume, $days);
            else
                $response = update_inbound_traffic($server_id, $remark, $volume, $days);
            if($response->success){
                $telegram->db->query("update fl_order set expire_date= $expire_date + $days * 86400,notif=0 where id='$oid'"); 
                $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
                $telegram->sendMessage($userid, "✅سرویس $remark با موفقیت تمدید شد");exit;
        
            }else {
                die("مشکل فنی در ارتباط با سرور. لطفا به مدیریت اطلاع بدید");
            }
        }
        
        
    }
}

if($type == 'day'){
    if($action == 'pay'){
        $token = base64_decode($_GET['token']);
        $input = explode('#',$token); 
        $server_id = intval($input[0]);
        $inbound_id = intval($input[1]);
        $remark = htmlspecialchars(strip_tags($input[2]));
        $planid = intval($input[3]);
        $userid = intval($input[4]); 
        $res = $telegram->db->query("select * from extra_day where id=$planid")->fetch(2);  
        $price = $res['price'];
        $volume = $res['volume'];
        bahamta_payment($price, $TokenCode, base64_encode($token), $callback_url);
    }elseif($action == 'verify'){
        $token = intval($_POST['clientrefid']);
        if(!$token) exit;
        
        $token = base64_decode($_GET['token']);
        $input = explode('#',$token);
        $server_id = $input[0];
        $inbound_id = $input[1];
        $remark = $input[2];
        $planid = $input[3];
        $userid = $input[4];
        $res = $telegram->db->query("select * from extra_day where id=$planid")->fetch(2);  
        $amount = $res['price'];
        $price = $res['volume'];
        $time = time();

        $pay_result = bahamta_verify($price, $TokenCode);
        if($pay_result == 'success'){
            echo 'تراکنش با موفقیت انجام شد';
            $msg = "✅ سفارش شما با موفقیت ثبت شد";
            $telegram->sendMessage($userid, $msg);
    
            require_once('vray.php');
            if($inbound_id > 0)
                $response = update_client_traffic($server_id, $inbound_id, $remark, 0, $volume);
            else
                $response = update_inbound_traffic($server_id, $remark, 0, $volume);
            if($response->success){
                $telegram->db->query("update fl_order set expire_date= expire_date + $volume * 86400,notif=0 where remark='$remark'");
                $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
                $telegram->sendMessage($userid, "✅$volume روز به مدت زمان سرویس شما اضافه شد");
        
            }else {
                die("مشکل فنی در ارتباط با سرور. لطفا به مدیریت اطلاع بدید");
            }
        }
        
        
    }
}

if($type == 'volume'){
    if($action == 'pay'){
        $token = base64_decode($_GET['token']);
        $input = explode('#',$token); 
        $server_id = intval($input[0]);
        $inbound_id = intval($input[1]);
        $remark = htmlspecialchars(strip_tags($input[2]));
        $planid = intval($input[3]);
        $userid = intval($input[4]); 
        $res = $telegram->db->query("select * from extra_plan where id=$planid")->fetch(2);  
        $price = $res['price'];
        $volume = $res['volume'];
        bahamta_payment($price, $TokenCode, base64_encode($token), $callback_url);
    }elseif($action == 'verify'){
        $token = intval($_POST['clientrefid']);
        if(!$token) exit;
        
        $token = base64_decode($token);
        $input = explode('#',$token);
        $server_id = $input[0];
        $inbound_id = $input[1];
        $remark = $input[2];
        $planid = $input[3];
        $userid = $input[4];
        $res = $telegram->db->query("select * from extra_plan where id=$planid")->fetch(2);  
        $price = $res['price'];
        $volume = $res['volume'];
        $time = time();

        $pay_result = bahamta_verify($price, $TokenCode);
        if($pay_result == 'success'){
            echo 'تراکنش با موفقیت انجام شد';
            $msg = "✅ سفارش شما با موفقیت ثبت شد";
            $telegram->sendMessage($userid, $msg);
    
            require_once('vray.php');
            if($inbound_id > 0)
                $response = update_client_traffic($server_id, $inbound_id, $remark, $volume, 0);
            else
                $response = update_inbound_traffic($server_id, $remark, $volume, 0);
            if($response->success){
                $telegram->db->query("update fl_order set notif=0 where remark='$remark'");
                $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
                $telegram->sendMessage($userid, "✅$volume گیگ به حجم سرویس شما اضافه شد");
            }else {
                die("مشکل فنی در ارتباط با سرور. لطفا به مدیریت اطلاع بدید");
            }
        }
        
        
    }
}

if($type == 'wallet'){
    if($action == 'pay'){
        $amount = intval($_GET['amount']);// echo $amount;die;
        //if($amount < $min_wallet_charge) die('مبلغ کمتر از 2هزار تومان است');
        $userid = intval($_GET['userid']);
        $result = $telegram->db->query("select * from fl_user WHERE userid='$userid'")->rowCount();
        if($result == 0) die('اطلاعات ارسال اشتباه است');
        $telegram->db->query("INSERT INTO `fl_wallet` VALUES (NULL, $amount, $userid, '','0');");
        $woid = $telegram->db->lastInsertId();
        bahamta_payment($amount, $TokenCode, $woid, $callback_url);
    }elseif($action == 'verify'){
        $woid = intval($_POST['clientrefid']);
        if(!$woid) exit;
        
        $res = $telegram->db->query("select * from fl_wallet where id='$woid'")->fetch(2);
        $amount = $res['amount'];
        $userid = $res['userid'];
        $pay_result = bahamta_verify($amount, $TokenCode);
        if($pay_result == 'success'){
            echo '<h1 style="text-align:right;color:green;">کیف پول شما با موفقیت شارژ شد</h1>' ; 
            $telegram->db->query("update fl_user set wallet= wallet + $amount where userid='$userid'");
            $telegram->db->query("update fl_wallet set status=1 where id=$woid");
            $msg = "✅ کیف پول شما به مبلغ $amount تومان شارژ شد ";
            $telegram->sendMessage($userid, $msg);
        }
        
        
    }
}

if($type == 'buy'){
    if($action == 'pay'){
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
        bahamta_payment($price, $TokenCode, $order_id, $callback_url);
    }elseif($action == 'verify'){
        
        $order_id = intval($_POST['clientrefid']);
        if(!$order_id) exit;
        
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
        $server_id = $file_detail['server_id'];
        $netType = $file_detail['type'];
        $acount = $file_detail['acount'];
        $inbound_id = $file_detail['inbound_id'];
        $limitip = $file_detail['limitip'];
        
        $price = $orderDetails['amount'];
        
        $pay_result = bahamta_verify($price, $TokenCode);
        if($pay_result == 'success'){
            
            $telegram->db->query("update fl_order set status=1 where id='$order_id'");
            $telegram->sendMessage($userid, "✅سفارش شما با موفقیت ثبت شد");
    
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
                if(! $response->success){
                    // run again
                    $response = add_inbound($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType);
                } 
            }else {
                $response = add_inbount_client($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip); 
                if(! $response->success){
                    // run again
                    $response = add_inbount_client($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip);
                } 
            }
            
            //$telegram->sendMessage($cuserid,json_encode($response));exit;
             
            $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
            
            $acc_text = "🔗 $remark \n <code>$vray_link</code>" . " \n  برای کپی کردن لینک روی آن کلیک کنید \n";
            
            
            include 'phpqrcode/qrlib.php';
            $path = 'images/';
            $file = $path.$userid.".png"; //unlink($file);
            $ecc = 'L';
            $pixel_Size = 10;
            $frame_Size = 10;
            QRcode::png($vray_link, $file, $ecc, $pixel_Size, $frame_size);
        	addBorderImage($file);
            //$fileLink = "<a href='".baseURI.$file."'>&#8194;</a>";
        	//$telegram->sendHTML($cuserid,$acc_text.$fileLink,$finalop);
        	$telegram->sendPhoto($userid,$acc_text,$file);
        
        	$order = $telegram->db->query("update `fl_order` set link='$vray_link',remark='$remark',status=1 where id=$order_id");
        	
        	if($inbound_id == 0) {
                $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
                if($server_info['ucount'] != 0)
                    $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
            }else{
                if($acount != 0) 
                    $telegram->db->query("UPDATE `fl_file` SET `acount` = acount - 1 WHERE id=$fid");
            }
            
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

            
        }
    } // verify
}



function bahamta_payment($price, $TokenCode, $oid, $callback_url){
    //--- The request url, which the request must be sent to. If connected to production server
    //--- use "https://webpay.bahamta.com/api/create_request" and if connected to test server
    //--- use "https://testwebpay.bahamta.com/api/create_request"
    //$url = "https://webpay.bahamta.com/api/create_request";
    $url = "https://webpay.bahamta.com/api/create_request";

    $reference = uniqid(md5(date("Y-m-d H:i:s")));
    $mobile = "";
    $amount_irr = $price;
    $api_key = $TokenCode;
    $create_req = "$url?api_key=$api_key&reference=$reference&amount_irr=$amount_irr&payer_mobile=$mobile&callback_url=$callback_url";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $create_req);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    $response = curl_exec($ch);
    
    // Close the curl handle.
    curl_close($ch);
    
    //--- To see the whole response, comment out the following line
    // echo "Response is: " . $response . "<br><br>";

    //--- Check the response result and do the right action
    $data = json_decode($response, true);
    if ($data["ok"]) {
        $url = $data["result"]["payment_url"];
        header("Location: " . $url);
        exit;
    } else {
        echo ("خطا " . $data["error"]);
    }
}

function bahamta_verify($price, $TokenCode){
    $reference = $_GET["reference"];
    $amount = get_amount($reference); die($amount);

    $verify_result = verifybhp($reference, $amount, $TokenCode);

    $data = json_decode($verify_result, true);

    if ($data["ok"]) {
        return "success";
    } else {
        return "<h3 class='error'>این پرداخت مورد تأیید نیست.</h3>";
    }
    
}

function verifybhp($reference, $amount, $TokenCode) {
    $url = "https://webpay.bahamta.com/api/confirm_payment";
    $api_key = $TokenCode;

    //--- Create the request to be sent.
    $create_req = "$url?api_key=$api_key&reference=$reference&amount_irr=$amount";


    // Initialize a curl handle
    $ch = curl_init();

    // Set the URL that you want to GET by using the CURLOPT_URL option.
    curl_setopt($ch, CURLOPT_URL, $create_req);
    
    // Set CURLOPT_RETURNTRANSFER so that the content is returned as a variable.
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // Set CURLOPT_FOLLOWLOCATION to true to follow redirects.
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    // Execute the request.
    $response = curl_exec($ch);
    
    // Close the curl handle.
    curl_close($ch);

    return $response;
}
function get_amount($reference) {
    $history = apc_fetch('request_history');
    return $history["$reference"];
}

