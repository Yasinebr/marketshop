<?php
include_once 'config.php';
include_once 'telegram.php';
include_once 'class.php';
include_once 'jdf.php';
$telegram = new telegram(TOKEN,HOST,USERNAME,PASSWORD,DBNAME);
$telegram->db->exec("set names utf8mb4");
$class = new \netparadis\telegram(TOKEN);
// user
$result = $telegram->getTxt();
$userid = $result->message->from->id;
$text = $result->message->text;
$fname = $result->message->from->first_name;
$lname = $result->message->from->last_name;
$username = $result->message->from->username;
$time = time();
$msgid = $result->message->message_id;
$fwuid = $result->message->reply_to_message->forward_sender_name;
$fwuid2 = $result->message->reply_to_message->forward_from->id;
$fwtext = $result->message->reply_to_message->text;
$contact = $result->message->contact->phone_number;

// callback
$cid = $result->callback_query->id;
$cdata = $result->callback_query->data;
$cmsgid = $result->callback_query->message->message_id;
$chatid = $result->callback_query->message->chat->id;
$chatype = $result->callback_query->message->chat->type; // channel,private
$chatus = $result->callback_query->message->chat->username; // channelusername , normaluser-username
$cuserid = $result->callback_query->from->id;
$cfname = $result->callback_query->from->first_name;
if ($cdata) {$userid = $cuserid;}

// inline
$query = $result->inline_query->query;
$queryid = $result->inline_query->id;
$inlineUserId = $result->inline_query->from->id;
$inlinename = $result->inline_query->from->first_name;
$inlineusername = $result->inline_query->from->username;
$cancelop=array(array('❌ انصراف'));
function get_type($id){
    $url = "https://api.telegram.org/bot".TOKEN."/getFile?file_id=$id";
    $ch = curl_init();
    curl_setopt($ch,CURLOPT_URL,$url);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $res = curl_exec($ch);
    if(curl_error($ch)){
        var_dump(curl_error($ch));
    }else{
        return json_decode($res);
    }
}

// upload file
if ($result->message->document->file_id) {
    $fileid = $result->message->document->file_id;
} elseif ($result->message->audio->file_id) {
    $fileid = $result->message->audio->file_id;
} elseif ($result->message->photo[0]->file_id) {
    $fileid = $result->message->photo->file_id;
    if (isset($result->message->photo[2]->file_id)) {
        $fileid = $result->message->photo[2]->file_id;
    } elseif ($fileid = $result->message->photo[1]->file_id) {
        $fileid = $result->message->photo[1]->file_id;
    } else {
        $fileid = $result->message->photo[1]->file_id;
    }
} elseif ($result->message->voice->file_id) {
    $voiceid = $result->message->voice->file_id;
} elseif ($result->message->audio->file_id) {
    $fileid = $result->message->audio->file_id;
}
$caption = $result->message->caption;
//$telegram->sendMessage($userid,$fileid);
$startmsg = 'سلام .
به ربات مدیریت اکانت خوش آمدید.';


$userstate = 'state/' . $userid . '.txt';
if (!file_exists($userstate)) {
    $userfile = fopen('state/' . $userid . '.txt', "w");
    fclose($userfile);
    $userfile = fopen('state/' . $userid . '-free.txt', "w");
    fclose($userfile);
}
$state = file_get_contents('state/' . $userid . '.txt');

$finalop = array(
       array('🛒خرید تکی', '🔥 تست رایگان'),
    array( '🤝فروشنده هستم'),
    array('👤حساب کاربری', '📥 کسب درآمد'),
    //  array('💎دریافت نرم افزار یا اپلیکیشن'),
    array('💡راهنمای اتصال', '👤پشتیبانی'),
);
$cancelop = array(array('❌ انصراف'));
$imgop = array(array('رد کردن این مرحله'),array('❌ انصراف'));

if ($userid == ADMIN or isAdmin()) {
    $finalop[] = ['⚙️ مدیریت 1','⚙️ مدیریت 2'];
    $version1op = array(
        array('➕ثبت پلن 1','مدیریت پلن ها 1'),
        array('افزودن دسته بندی 1','مدیریت دسته بندی ها 1'),
        array('📈آمار1', '🏠 منوی اصلی'),
    );
    $adminop = array(
        array('➕ثبت پلن','مدیریت پلن ها'),
        array('افزودن دسته بندی','مدیریت دسته بندی ها'),
        array('افزودن سرور','مدیریت سرورها'),
		array('📨 فوروارد همگانی', '🗒 پیام همگانی'),
        array('موجودی کاربران','🔐ادمین ها'),
		array('👤 پیگیری افراد', 'کد تخفیف'),
		array('پلن حجمی', 'پلن زمانی'),
		array('📈آمار','📮 پیام به کاربر'),
		array('🤖درگاه و امکانات‌','⚙️ تنظیمات'),
		array('همکارها','افزودن همکار جدید'),
		array('🏠 منوی اصلی',"🔎جستجو سفارش"),
    );
}
$productop = [
    ['مدیریت پلن ها','افزودن پلن جدید'],
    ['🏠 منوی اصلی']
];
$catop = [
    ['مدیریت دسته بندی ها','افزودن دسته بندی جدید'],
    ['🏠 منوی اصلی']
];

$phonekeys = array(
    array(
        array('text'=>'📲 ارسال شماره تلفن','request_contact'=>true)
    )
);

$ban = $telegram->db->query("select * from fl_user where userid='$userid'");
if($ban){
    $ban = $ban->fetch(2);
    if($ban) {
        if($ban['status']=='0' and !($userid == ADMIN) ){$telegram->sendMessage($userid,'حساب کاربری شما از سمت مدیریت بن شده است');exit;}
    }
}
$botstatus = file_get_contents('botstatus');
if($botstatus=='close' and !($userid == ADMIN or isAdmin()) ){
	$telegram->sendMessage($userid,'در حال حاضر ربات غیرفعال است');exit;
}
$gateways = $telegram->db->query("select * from gateway where id=1")->fetch(2);
$channel = CHANNEL;
$status = bot('getChatMember', [
    'chat_id' => "$channel",
    'user_id' => $userid
])->result->status;

if ($status != 'kicked' && $status != 'left') {
    $status = bot('getChatMember', [
        'chat_id' => "@wolfv2vip",
        'user_id' => $userid
    ])->result->status;

    if ($status != 'kicked' && $status != 'left') {
        $status = bot('getChatMember', [
            'chat_id' => "@waslsho",
            'user_id' => $userid
        ])->result->status;
    }
}

if(preg_match('/sendpm/',$cdata)){
    
    $sid = str_replace('sendpm#','',$cdata);
    
    if($sid == 'all'){
        $dbresult = $telegram->db->query("select * from fl_user")->fetchAll(2);
    }else{
        $dbresult = $telegram->db->query("SELECT * FROM `fl_order` where server_id=$sid GROUP BY userid")->fetchAll(2);
    }
    
    $spm = $state;
    file_put_contents('state/' . $userid . '.txt', '');
	
    $telegram->sendMessageCURL($userid, '👍🏻✅ پیام شما با موفقیت برای کاربران ارسال شد ', $adminop);
    if($fileid !== null) {
		foreach ($dbresult as $user) {
			if ($user['userid'] != ADMIN) {
				$res = get_type($fileid);
				$gftype = $res->result->file_path;
				//$telegram->sendMessage(ADMIN, json_encode($user['userid']));
				if(preg_match('/music/',$gftype)){
					bot('sendaudio',[
						'chat_id' => $user['userid'],
						'audio' => $fileid,
						'caption' => $result->message->caption
					]);
				}elseif (preg_match('/video/',$gftype)){
					bot('sendvideo',[
						'chat_id' => $user['userid'],
						'video' => $fileid,
						'caption' => $result->message->caption
					]);
				}elseif (preg_match('/document/',$gftype)){
					bot('senddocument',[
						'chat_id' => $user['userid'],
						'document' => $fileid,
						'caption' => $result->message->caption
					]);
				}elseif (preg_match('/photo/',$gftype)) {
					bot('sendphoto', [
						'chat_id' => $user['userid'],
						'photo' => $fileid,
						'caption' => $result->message->caption
					]);
				}elseif($result->message->location){
					$latitude = $result->message->location->latitude;
					$longitude = $result->message->location->longitude;
					bot('sendLocation', [
						'chat_id' => $user['userid'],
						'latitude' => $latitude,
						'longitude' => $longitude
					]);
				}else {
					bot('senddocument',[
						'chat_id' => $user['userid'],
						'document' => $fileid,
						'caption' => $result->message->caption
					]);
				}
			}
		}
	}else{
		foreach ($dbresult as $user) {
			if ($user['userid'] != ADMIN) {
				$telegram->sendMessage($user['userid'], $spm);
			}
		}
	}
}
if( ($fwuid or $fwuid2) and ($userid==ADMIN or isAdmin())){
	$replymsg = $text;
    //$telegram->sendMessage($userid, $fwuid);
    if($fwuid2) $fuid = $fwuid2; else $fuid = $telegram->db->query("SELECT * FROM fl_user WHERE name='$fwuid'")->fetch(2)['userid'];
    //$telegram->sendMessage($userid, $fwuid);$telegram->sendMessage($userid, "SELECT * FROM fl_user WHERE name='$fwuid'");
    $text = " پیام شما : $fwtext

پاسخ مدیریت : $replymsg
.";
	$telegram->sendMessage($fuid,$text);
	$res = get_type($fileid);
	$gftype = $res->result->file_path;
	if(preg_match('/music/',$gftype)){
		bot('sendaudio',[
			'chat_id' => $fuid,
			'audio' => $fileid,
			'caption' => $caption
		]);
	}elseif (preg_match('/video/',$gftype)){
		bot('sendvideo',[
			'chat_id' => $fuid,
			'video' => $fileid,
			'caption' => $caption
		]);
	}elseif (preg_match('/document/',$gftype)){
		bot('senddocument',[
			'chat_id' => $fuid,
			'document' => $fileid,
			'caption' => $caption
		]);
	}elseif (preg_match('/photo/',$gftype)) {
		bot('sendphoto', [
			'chat_id' => $fuid,
			'photo' => $fileid,
			'caption' => $caption
		]);
	}elseif($result->message->location){
		$latitude = $result->message->location->latitude;
		$longitude = $result->message->location->longitude;
		bot('sendLocation', [
			'chat_id' => $fuid,
			'latitude' => $latitude,
			'longitude' => $longitude
		]);
	};
	$telegram->sendMessage($userid,'ریپلای با موفقیت برای کاربر ارسال شد');
	// update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅پاسخ داده شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
	
	$admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3 and $admid != $userid){
           $telegram->sendMessage($admid,"پیام زیر توسط یکی از همکاران پاسخ داده شد. لطفا از ارسال پاسخ جدید خودداری کنید:

متن پیام : $fwtext
پاسخ مدیریت‌ : $admtext");
        }
    }
    if(ADMIN != $userid) $telegram->sendMessage(ADMIN,"پیام زیر توسط یکی از همکاران پاسخ داده شد. لطفا از ارسال پاسخ جدید خودداری کنید:

متن پیام : $fwtext
پاسخ مدیریت‌ : $admtext");
}

if(!empty($contact)){
    /*if(strpos($contact, $valid_country_code) === false){
        $telegram->sendMessage($userid,"⚠️فقط پیش شماره های ($valid_country_code) مجاز است");
        exit;
    }*/
    $telegram->db->query("update fl_user set tel='$contact' where userid='$userid'");
    $msg = '✅شماره تلفن شما با موفقیت ثبت شد و می توانید از ربات استفاده کنید';
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    $state = file_put_contents('state/'.$userid.'.txt','');
    exit;
} 

if (preg_match('/^\/([Ss]tart)/', $text) or $text == '🏠 منوی اصلی' or $text == '🔙بازگشت به منوی اصلی' or $cdata == 'chnnlmmber') {
    file_put_contents('state/' . $userid . '.txt', '');
    $count = $telegram->db->query("select * from fl_user where userid='$userid'")->rowCount();
    if ($count == 0) {
		$fname = preg_replace('/\/|<|\\|>/','',$fname);
		$fname = str_replace(["\\",'/','+','-','^',"'"],'',$fname);
        $refcode = time();
        $sql = "INSERT INTO `fl_user` VALUES (NULL,'$userid','$fname','$username','','$refcode', 0,$time, 1, 0)";
        $telegram->db->query($sql);
    }
	if($cdata == 'chnnlmmber' && ($status == "kicked" || $status == "left")){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => "هنوز عضو کانال نشدید",
			'show_alert' => false
		]);exit;
	}
    if (preg_match('/start/', $text)) {
        $refrence_code = str_replace('/start ','',$text);
        $res = $telegram->db->query("select * from fl_user where refcode='$refrence_code' and userid != $userid");
         
        if( $res->rowCount() ){
            $res = $res->fetch(2);
            $toplevel_userid = $res['userid'];
            
            $subresCount = $telegram->db->query("select * from fl_subuser where userid = $userid")->rowCount();
           
            if($subresCount == 0){
                $telegram->db->query("INSERT INTO `fl_subuser` VALUES (NULL,'$userid','$fname','$refrence_code','$toplevel_userid')");
            }
            
        }
    } // start
    
	$user_detail = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    if($user_detail['tel'] == '' and $valid_country_code != ''){
        $telegram->sendMessageCURL($userid, 'جهت استفاده از خدمات ما شماره موبایل خود راه از طریق دکمه زیر با ما به اشتراک بگذارید🙏
 ( شماره شما فقط برای ارسال لینک های اشتراک و پشتیبانی استفاده خواهد شد . ما به حریم خصوصی شما اهمیت زیادی می دهیم و اطلاعات شما محفوظ میباشد❤️)', $phonekeys);
        exit;
    }
    $telegram->sendMessageCURL($userid, $startmsg, $finalop);
    
    if($user_detail['pinmsgid'] == '0'){
        bot('unpinAllChatMessages');
    	$pinres = bot('sendMessage', [
    		'chat_id' => $userid,
    		'text' => $pinmsg,
    		'disable_notification' => true,
    		'parse_mode' => 'HTML',
    		'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "ورود به کانال", 'url' => "https://t.me/".str_replace('@','',$channel)]]]
            ])
    	]);
        $pinmsgid = $pinres->result->message_id;
        $telegram->db->query("update fl_user set pinmsgid='$pinmsgid' where userid='$userid'");
        bot('pinchatmessage', [
            'chat_id' => $userid,
            'message_id' => $pinmsgid,
        ]);
    }
    
    $code = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2)['refcode'];
    $count = $telegram->db->query("select * from fl_subuser where refcode='$code'")->rowCount();
    $msg = "
لینک زیر را با دوستان خود به اشتراک بزارید و به ازای هر خرید %$pursant ٪ از مبلغ خرید به کیف پول شما اضافه خواهد شد تا بتوانید محصولات داخل فروشگاه را بدون پرداخت هزینه دریافت کنید

https://t.me/".botid."?start=$code
";

    $telegram->sendAction($userid,'typing');
    $telegram->sendHTML($userid,$msg,$finalop);
    
    exit;
}

$user_detail = $telegram->db->query("select * from fl_user where userid='$userid'")->fetch(2);
if($user_detail['pinmsgid'] == '0'){
    bot('unpinAllChatMessages');
	$pinres = bot('sendMessage', [
		'chat_id' => $userid,
		'text' => $pinmsg,
		'disable_notification' => true,
		'parse_mode' => 'HTML',
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => "ورود به کانال", 'url' => "https://t.me/".str_replace('@','',$channel)]]]
        ])
	]);
    $pinmsgid = $pinres->result->message_id;
    $telegram->db->query("update fl_user set pinmsgid='$pinmsgid' where userid='$userid'");
    bot('pinchatmessage', [
        'chat_id' => $userid,
        'message_id' => $pinmsgid,
    ]);
}

if ($status == "kicked" || $status == "left"){
    $keyboard = [
		[['text' => "عضویت در کانال", 'url' => "https://t.me/addlist/3Bxbd6U6z7kzMmQ0"]],
		[['text' => "✅عضو شدم", 'callback_data' => "chnnlmmber"]]
	];
    
    bot('sendmessage',[
        'chat_id' => $userid,
        'text' => "🔑 قبل از ادامه باید به کانال بپیوندید:",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
	exit;
}

if($cdata == 'besslr'){
    $rcount = $telegram->db->query("select * from fl_sellers where userid='$userid'")->rowCount();
    if($rcount > 0) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "شما نماینده فعال هستید و نیاز به ارسال درخواست مجدد نیست",
            'show_alert' => false
        ]);
        exit;
    }

    $telegram->sendMessage($userid,"✅✅درخواست شما با موففیت ارسال شد. بعد تایید از طریق ربات اطلاع رسانی می شود");


    $user = $telegram->db->query("select * from fl_user where userid='$userid'")->fetch(2);
    if(!$user){
        $telegram->sendMessage($userid,'اطلاعات شما در سیستم یافت نشد. لطفا مجدد /start بزنید');
        exit;
    }
    $orders_count = $telegram->db->query("select * from fl_order where userid='".$user['userid']."' and status=1")->rowcount();
    
    $uid = $user['userid'];
    $free = file_get_contents("state/{$uid}-free.txt");
    $free_count = ($free == '') ? 0 : $free - 1;

    $msg = "👤درخواست نمایندگی
➖name : <b>".$user['name']."</b>
➖username : @".$user['username']."
➖tel : <b>+".$user['tel']."</b>
➖Subs : $list_count /guslst".$user['userid']."
➖status : $status /banusr".$user['id']."
➖orders : <b>$orders_count</b> /getuord".$user['userid']."
➖free : $free_count /chfrcnt".$user['userid']."
➖wallet : <b>".number_format($user['wallet'])."</b> /waladd".$user['id']."
";

    bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => [[
                ['text' => '✅تایید', 'callback_data' => "bsllr#$userid"],
                ['text' => '❌رد', 'callback_data' => "disable#$userid#slr"]
            ]]
        ])
    ]);
}
if(preg_match('/bsllr/', $cdata)){

    $uid = str_replace('bsllr#', '', $cdata);

    $rcount = $telegram->db->query("select * from fl_sellers where userid='$uid'")->rowCount();
    if($rcount > 0) {
        bot('editMessageReplyMarkup',[
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
            ])
        ]);
        $telegram->sendMessage($userid, "این کاربر از قبل نماینده هست");
        exit;
    }

    $telegram->db->query("insert into fl_sellers VALUES (NULL,'$uid',0)");
    $id = $telegram->db->lastInsertId();
    //$telegram->sendMessage($userid,"✅همکار جدید با موفقیت اضافه شد");
    $telegram->sendMessage($uid,"✅درخواست همکاری شما پذیرفته شد و همینک میتوانید از نمایندگی و همکاری 🤝 خرید کنید");
    // update button
    bot('editMessageReplyMarkup',[
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
    
    $text = "/edithmkr$id";

}

if($text == '👤حساب کاربری'){
    
    $wallet = $telegram->db->query("SELECT * from `fl_user` WHERE userid='$userid'")->fetch(2)['wallet'];
    $wallet = number_format($wallet);
    $keyboard = [
		[['text' => "➕ موجودی $wallet تومان", 'callback_data' => "addwalet"]],
		[['text' => "لیست سرویس های من", 'callback_data' => "backto"]]
	];
	
	$orders_count = $telegram->db->query("select * from fl_order where userid='".$user['userid']."' and status=1")->rowCount();
	
	$seller = $telegram->db->query("select * from fl_sellers where userid='$userid' ")->fetch(2);
    $type = (!empty($seller)) ? 'همکار' : 'عادی';
    
    $msg = "👤اطلاعات حساب کاربری شما در ربات

#️⃣آیدی عددی: <code>$userid</code>
🥇سطح کاربری: $type [holder]
🛍تعداد کل سرویس ها: <b>$orders_count</b>
💰موجودی کیف پول: <b>$wallet</b> تومان

برای افزایش موجودی حسابتون میتونید دکمه شیشه ای پنل زیر را لمس کنید";

    if(empty($seller)){
        $keyboard[] = [['text' => "درخواست نمایندگی", 'callback_data' => "besslr"]];
        $msg = str_replace('[holder]', '', $msg);
    }else{
        $percent = $seller['percent'];
        $msg = str_replace('[holder]', "\n🎉درصد همکاری:  $percent%\n", $msg);
    }

    bot('sendmessage',[
        'chat_id' => $userid,
        'text' => $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if ($text == '➕ثبت لینک'){
    $msg = '🔻لطفا لینک کانفیگ را وارد کنید: ';
    $telegram->sendMessageCURL($userid,$msg,[[ '🔙بازگشت به منوی اصلی' ]]);
    file_put_contents('state/' . $userid . '.txt', 'inquiryln');
}

if($text != '🔙بازگشت به منوی اصلی' and $state == 'inquiryln'){
    
    include_once('vray.php');
    
    // marzban
    if(preg_match('/\/sub\//', $text)){
        $res = get_web_page($text);
        $link = explode(PHP_EOL, base64_decode($res));
        $link = array_values(array_slice($link, -2, 1, true))[0];
        if(preg_match('/vmess:\/\//',$link)){
            $link_info = json_decode(base64_decode(str_replace('vmess://','',$link)));
            $username = $link_info->ps;
        }elseif(preg_match('/^ss:\/\//',$link)){
    	    $link_info = str_replace("ss://",'',$link);
    	    $link_info = explode("#",$link_info);
    	    $username = $link_info[1];
    	}else{
            $link = urldecode($link);
            $link_info = parse_url($link);
            $username = $link_info['fragment'];
        }

        $username = explode(' ', $username)[0];
        
        $telegram->sendMessage($userid, "🔎در حال بررسی لینک موردنظر در سرورهای ما...");
        $servers = $telegram->db->query("select * from server_info where ptype='marzban' ")->fetchAll(2);
        include_once('marz.php');
        foreach($servers as $server){
            $server_id = $server['id'];
            $response = muser_detail($server_id, $username);
        	if(isset($response->subscription_url)) break;
        }  // $telegram->sendMessage($userid,json_encode($row));
        if(is_null($response) or $response == false) {
            $telegram->sendMessage($userid, "⛔️لینک ارسالی در سرورهای ربات یافت نشد");
            exit;
        }
        $expire_date = $response->expire;
        $telegram->sendMessageCURL($userid, "✅کانفیگ شما با موفقیت ثبت شد و میتوانید از بخش سرویس های من اطلاعات آن مثل تاریخ انقضا, حجم باقی مانده را ببینید",$finalop);
        file_put_contents('state/' . $userid . '.txt', '');
        $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  $userid, '', 010, $server_id, 0, '$username', 'vless', '$expire_date', '$text',2,1, '$time', 0);");
        
        exit;
    }

	preg_match('/(vless|vmess|trojan):\/\/.+/', $text, $matches);

    $text = $matches[0];
	
    if(!preg_match("/^(vless|vmess|trojan|ss):\/\/\w+/",$text)){
        $telegram->sendMessage($userid, "⚠️لطفا یک لینک کانفیگ صحیح ارسال کنید");
        exit;
    }
    //$telegram->sendMessage($userid,"22 $text");
    if(preg_match('/vmess:\/\//',$text)){
        $link_info = json_decode(base64_decode(str_replace('vmess://','',$text)));
        $uuid = $link_info->id;
        $panel_url = $link_info->add;
        $port = $link_info->port;
        $remark = $link_info->ps;
        $protocol = 'vmess';
        $netType = $link_info->net;
    }elseif(preg_match('/^ss:\/\//',$text)){
	    $link_info = str_replace("ss://",'',$text);
	    
	    $link_info = explode("#",$link_info);
	    $remark = $link_info[1];
	    $link_info = base64_decode($link_info[0]); 
	    $link_info = explode('@',$link_info);
	    
	    $uuid = explode(':',$link_info[0])[1];
	    $link_info = $link_info[1];
	    $link_info = explode(':',$link_info);
	    $panel_url =$link_info[0];
		$port = $link_info[1];
		$netType = '';
		$protocol = 'shadowsocks';
	}else{
        $link = urldecode($text);
        $link_info = parse_url($link);
        $panel_ip = $link_info['host'];
        $uuid = $link_info['user'];
        $remark = $link_info['fragment'];
        $protocol = $link_info['scheme'];
        $port = $link_info['port'];
        $netType = explode('type=',$link_info['query'])[1];
        $netType = explode('&',$netType)[0];

    }
    $telegram->sendMessage($userid, "🔎در حال بررسی لینک موردنظر در سرورهای ما...");
    $servers = $telegram->db->query("select * from server_info where ptype='xui' ")->fetchAll(2);
    foreach($servers as $server){
        $row = get_detail($uuid, $server['panel_url'], $server['cookie'] ); //$telegram->sendMessage($userid,json_encode($server));
        if($row) break;
    }  // $telegram->sendMessage($userid,json_encode($row));
    if(is_null($row) or $row == false) {
        $telegram->sendMessage($userid, "⛔️لینک ارسالی در سرورهای ربات یافت نشد");
        exit;
    }
    $response_type = $row['type'];
    $row = $row['data']; 
    $total = is_null($row->total) ? $row->totalGB : $row->total;
    $remark = ($row->email) ? $row->email : $row->remark;
	//if(isset($row->clientStats[0]->email)) $remark = $row->clientStats[0]->email;
    $leftgb = $total / 1073741824;
    $expire_date = $row->expiryTime == 0 ? $row->expiryTime : substr_replace($row->expiryTime, "", -3);
    $inbound_id = is_null($row->inboundId) ? 0 : $row->inboundId;
	//if(isset($row->clientStats[0]->inboundId)) $inbound_id = $row->clientStats[0]->inboundId;
    $server_id = $server['id'];
    $file_detail = $telegram->db->query("select * from fl_file WHERE server_id=$server_id and inbound_id = $inbound_id ORDER BY id DESC")->fetch(2);
    if($file_detail) $fid = $file_detail['id']; else $fid = 0;
    $telegram->sendMessageCURL($userid, "✅کانفیگ شما با موفقیت ثبت شد و میتوانید از بخش سرویس های من اطلاعات آن مثل تاریخ انقضا, حجم باقی مانده را ببینید",$finalop);
    file_put_contents('state/' . $userid . '.txt', '');
    $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  $userid, '', $fid, $server_id, $inbound_id, '$remark', '$protocol', $expire_date, '$text',2,1, '$time', 0);");
}
if($text == '🤝فروشنده هستم' ){
    $keyboard = [
        [['text' => "افزودن همکار", 'callback_data' => "besslr"]],
        [['text' => "درخواست نمایندگی", 'callback_data' => "bbbb"]]
    ];
    bot('sendmessage',[
        'chat_id' => $userid,
        'text'=> '',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
/*if ($text == '🤝فروشنده هستم' or $cdata=='retailsrvc'){
	if($gateways['buy'] == '0'){
        $telegram->sendMessage($userid, 'در حال حاضر امکان خرید نیست و بزودی فعال میکنیم');
        exit;
    }
    $respd = $telegram->db->query("select * from fl_file WHERE active=1 and (inbound_id > 0 and acount > 0 or inbound_id = 0)  and isvip > 0 order by id asc")->fetchAll(2);
    
    
    $keyboard = [];
    foreach($respd as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
    }
    
    $respd = $telegram->db->query("select * from fl_1cat WHERE parent=0")->fetchAll(2);
    foreach($respd as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $keyboard[] = ['text' => "$name", 'callback_data' => "li1st#$id"];
    }
    if(empty($keyboard)){
        $telegram->sendMessage($userid, 'در حال حاضر هیچ سرویس فعالی وجود ندارد');
        exit;
    }
    $keyboard = array_chunk($keyboard,1);
    if(isset($cdata) and $cdata=='retailsrvc') {
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text'=> ' 📍 لطفا یکی از سرویس ها را انتخاب کنید👇',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else {
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> ' 📍 لطفا یکی از سرویس ها را انتخاب کنید👇',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}*/
if ($text == '🛍 خرید کانفیگ' or $cdata=='servers' or $text == '🛍 خرید سرویس' or $text =='/buy' or $text == '🛒خرید تکی'){
	if($gateways['buy'] == '0'){
        $telegram->sendMessage($userid, 'در حال حاضر امکان خرید نیست و بزودی فعال میکنیم');
        exit;
    }
    $respd = $telegram->db->query("select * from fl_server WHERE active=1 and ucount > 0 ORDER BY id ASC")->fetchAll(2);
    if(empty($respd)){
        $telegram->sendMessage($userid, 'در حال حاضر هیچ سرور فعالی وجود ندارد');
        exit;
    }
    $keyboard = [
        [['text' => "(Subscription)اشتراک هوشمند", 'callback_data' => "multimrz"]],
       // [['text' => "تک لوکیشن", 'callback_data' => "srlvs#tunnel"]],//
        [['text' => "وایرگارد", 'callback_data' => "srlvs#wrg"]]
    ];
    if(isset($cdata) and $cdata=='servers') {
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text'=> '🐺اشتراک هوشمند چیست؟

با یک لینک اشتراک هوشمند ، به راحتی و بدون قطعی به چندین لوکیشن مختلف متصل شوید. لینک‌ها به‌صورت روزانه به‌روزرسانی می‌شوند تا همیشه به سرورهای جدید و پایداری بالا دسترسی داشته باشید. این سرویس به‌طور خودکار بهترین سرور را برای شما انتخاب می‌کند تا تجربه اینترنتی سریع و بدون مشکل داشته باشید.

ویژگی‌ها:
- اتصال به چندین لوکیشن مختلف
- بدون قطعی با به‌روزرسانی روزانه لینک‌ها
- انتخاب هوشمندانه سرور برای بهترین سرعت
- سازگاری کامل با تمام دستگاه‌ها
 (موبایل، تبلت، ویندوز و...)

اگر به سرعت بالا و اتصال پایدار نیاز دارید،
 اشتراک هوشمند  گزینه‌ای عالی برای شماست.',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else {
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> '🐺اشتراک هوشمند چیست؟

با یک لینک اشتراک هوشمند ، به راحتی و بدون قطعی به چندین لوکیشن مختلف متصل شوید. لینک‌ها به‌صورت روزانه به‌روزرسانی می‌شوند تا همیشه به سرورهای جدید و پایداری بالا دسترسی داشته باشید. این سرویس به‌طور خودکار بهترین سرور را برای شما انتخاب می‌کند تا تجربه اینترنتی سریع و بدون مشکل داشته باشید.

ویژگی‌ها:
- اتصال به چندین لوکیشن مختلف
- بدون قطعی با به‌روزرسانی روزانه لینک‌ها
- انتخاب هوشمندانه سرور برای بهترین سرعت
- سازگاری کامل با تمام دستگاه‌ها
 (موبایل، تبلت، ویندوز و...)

اگر به سرعت بالا و اتصال پایدار نیاز دارید،
 اشتراک هوشمند  گزینه‌ای عالی برای شماست.',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}
if($cdata == 'multimrz' ) { 
    $respd = $telegram->db->query("SELECT fl_server.*, server_info.ptype FROM `fl_server` INNER JOIN server_info WHERE server_info.id=fl_server.id and fl_server.active=1 and fl_server.ucount > 0 and server_info.ptype = 'marzban';")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' =>  'در حال حاضر هیچ سرور فعالی وجود ندارد',
            'show_alert' => false
        ]);
        exit;
    }
    $keyboard = [];
    foreach($respd as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $flag = $cat['flag'];
        $keyboard[] = ['text' => "$flag $name", 'callback_data' => "topcat#$id#marz"];
    }
    $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => "servers"];
    $keyboard = array_chunk($keyboard,1);
    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'text'=> '🐺 سرویس مد نظر خود را انتخاب کنید 👇

مولتی:  
این پلن به شما دسترسی به چندین لوکیشن با کیفیت خوب می‌ده و مناسب کسانیه که به دنبال هزینه کمتر و مصرف معمولی هستن. اگر می‌خواهید به چند کشور مختلف متصل بشید، این گزینه مناسب شماست.

مولتی پلاس:  
مثل مولتی، این پلن هم چندین لوکیشن رو شامل می‌شه، ولی کیفیت لینک‌ها و سرعت خیلی بالاتره. این گزینه برای کسانیه که به سرعت بالا و پایداری بیشتر نیاز دارن. اگر کیفیت برای شما اولویت داره، مولتی پلاس انتخاب بهتریه.

نتیجه:  
اگر کیفیت عالی و سرعت بیشتر می‌خواهید، مولتی پلاس بهترین انتخابه. ولی اگر به دنبال هزینه کمتر و کیفیت مناسب هستید، مولتی گزینه مناسبیه.',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/srlvs/',$cdata) ) {
    $input = explode('#', $cdata);
    $type = $input[1];
    $type_label = $type == 'tunnel' ? 'vip' : 'مستقیم';
    if($type == 'wrg') $type_label = 'وایرگارد';
    $respd = $telegram->db->query("SELECT fl_server.*, server_info.ptype FROM `fl_server` INNER JOIN server_info WHERE server_info.id=fl_server.id and fl_server.active=1 and fl_server.ucount > 0 and server_info.ptype = 'xui' and fl_server.title LIKE '%$type_label%' ORDER BY id ASC")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' =>  'در حال حاضر هیچ سرور فعالی وجود ندارد',
            'show_alert' => false
        ]);
        exit;
    }
    $keyboard = [];
    foreach($respd as $cat){
        $id = $cat['id'];
        $name = str_replace(['(',$type_label,')'],'',$cat['title']);
        $flag = $cat['flag'];
        $keyboard[] = ['text' => "$flag $name", 'callback_data' => "topcat#$id#$type"];
    }
    $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => "servers"];
    $keyboard = array_chunk($keyboard,1);
    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'text'=> '🛒سرویس مد نظر خود را انتخاب کنید👇

.مولتی : پروتکل های مستقیم
.مولتی پلاس : شامل پروتکل های پر سرعت تانل + پروتکل های مستقیم',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/topcat/',$cdata) ) {
    $input = explode('#', $cdata);
    $sid = $input[1];
    $type = $input[2];

    $respd = $telegram->db->query("select * from fl_cat WHERE parent=0 order by id asc")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "هیچ دسته بندی برای این سرور وجود ندارد",
            'show_alert' => false
        ]);
    }else{
        
        $keyboard = [];
        foreach($respd as $file){
            $id = $file['id'];
            $name = $file['title'];
            $rowcount = $telegram->db->query("select * from fl_file WHERE server_id='$sid' and price > 0 and catid=$id and (inbound_id > 0 and acount > 0 or inbound_id = 0) and active=1")->rowCount();
            if($rowcount) $keyboard[] = ['text' => "$name", 'callback_data' => "list#$id#$sid#$type"];
        }
        if(empty($keyboard)){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "پلن های سرور تکمیل ظرفیت شد",
                'show_alert' => false
            ]);exit;
        }
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "📍در حال دریافت دسته بندی ها",
            'show_alert' => false
        ]);
        $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => $type == 'marz' ? "multimrz" :"srlvs#$type"];
        $keyboard = array_chunk($keyboard,1);
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text' => "🔥 پلن یک‌ماهه  
این پلن برای کسانی که تازه با ما آشنا شدن و به اینترنت کوتاه‌مدت نیاز دارند و‌ میخان تست کنند کیفیت رو خیلی مناسبه. اگر فقط برای یک ماه به اینترنت احتیاج داری و نمی‌خواهی هزینه زیادی کنی، این گزینه به درد تو می‌خوره!

🎯 پلن سه‌ماهه  
اگر به دنبال یک اینترنت پایدار برای چند ماه هستی و می‌خواهی هزینه کمتری پرداخت کنی، این پلن گزینه خیلی خوبی برای شماست. مخصوصاً برای کسانی که کار یا تحصیل دارند یا برای خانواده‌ها و گروه‌هایی که مصرف اینترنت بیشتری دارن، به‌صرفه میشه.

🏆 پلن شش‌ماهه  
این پلن بهترین انتخاب برای کسانی هست که به اینترنت بلندمدت و با حجم بالا نیاز دارن. مخصوصاً برای کسب‌وکارها، دفاتر کاری یا خانواده‌ها که می‌خوان هزینه‌ها رو کاهش بدن و اینترنت با کیفیت و بدون قطعی داشته باشن، خیلی خیلی مناسب و به‌صرفه است!",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}

if (preg_match('/list/', $cdata)) {
    $input = explode('#', $cdata);
    $cid = $input[1];
    $sid = $input[2];
    $type = $input[3];
    $respd = $telegram->db->query("select * from fl_file WHERE server_id='$sid' and catid=$cid and (inbound_id > 0 and acount > 0 or inbound_id = 0) and active=1 order by id asc")->fetchAll(2);
    if (empty($respd)) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡پلنی در این دسته بندی وجود ندارد یا ظرفیت آن پر شده است ",
            'show_alert' => false
        ]);
    } else {
        $seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
        if(!empty($seller)){
            file_put_contents('state/' . $userid . '.txt', "setrmrk#$cid#$sid");
            $count = $telegram->db->query("select * from fl_remark where userid=$userid")->rowCount();
            if ($count == 0) $telegram->db->query("INSERT INTO `fl_remark` VALUES (NULL,'$userid','')");
            $telegram->sendMessageCURL($userid,'✅لطفا اسم انتخابی اشتراک خود را وارد کنید:

⚠️توجه کنید فقط اعداد لاتین یا حروف انگلیسی و بدون فاصله وارد کنید
.',$cancelop);
            exit;
        }else{
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "📍در حال دریافت لیست پلن ها",
                'show_alert' => false
            ]);
            $keyboard = [];
            foreach($respd as $file){
                $id = $file['id'];
                $name = $file['title'];
                $price = $file['price'];
				$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
				if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
			
                $price = ($price == 0) ? 'رایگان' : number_format($price).' تومان ';
                $keyboard[] = ['text' => "$name - $price", 'callback_data' => "file#$id#$cid"];
            }
            $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => "topcat#$sid#$type"];
            $keyboard = array_chunk($keyboard,1);
            bot('editMessageText', [
                'chat_id' => $cuserid,
                'message_id' => $cmsgid,
                'text' => "🔰حالا یکی از موارد زیر را انتخاب کنید
.تا جزییات پلن برای شما نمایش داده شود👈",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]);
        }
        
        
       
    }
}
if (preg_match('/setrmrk/', $state) and $text != '❌ انصراف') {
    
    if(!preg_match('/^[\w]+$/', $text)){
        $telegram->sendMessage($userid,'لطفا فقط حروف انگلیسی و اعداد لاتین بفرستید');die;
    }
    file_put_contents('state/' . $userid . '.txt', '');
    $telegram->db->query("update fl_remark set remark='$text' where userid='$userid'");
    $telegram->sendMessageCURL($userid,"اسم انتخابی اشتراک با موفقیت ذخیره شد",$finalop);
    
    $input = explode('#', $state);
    $cid = $input[1];
    $sid = $input[2];
    $respd = $telegram->db->query("select * from fl_file WHERE server_id='$sid' and catid=$cid and (inbound_id > 0 and acount > 0 or inbound_id = 0) and active=1 order by id asc")->fetchAll(2);

    $keyboard = [];
    foreach ($respd as $file) {
        $id = $file['id'];
        $name = $file['title'];
        $price = $file['price'];
        
        $seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
        if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
        
        $price = ($price == 0) ? 'رایگان' : number_format($price) . ' تومان ';
        $keyboard[] = ['text' => "$name - $price", 'callback_data' => "file#$id#$cid"];
    }
    $keyboard = array_chunk($keyboard, 1);
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => "🔰 حالا یکی از موارد زیر را انتخاب کنید تا جزییات پلن برای شما نمایش داده شود👈",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}

if($text == '🔥 اکانت تست' or $text == '🔥 تست رایگان' or $cdata == 'freesrvcs' or $text == '/freetest') {
    $free = file_get_contents("state/{$userid}-free.txt");
    if($free == '') $free = 2;
	if($free < 2  and !($userid == ADMIN or isAdmin() )){
		$telegram->sendMessage($userid, '⚠️شما قبلا هدیه رایگان خود را دریافت کردید');
		exit;
	}
    $query = $telegram->db->query("select * from fl_file WHERE active=1 and price = 0");
    if($query){
        $respd = $query->fetchAll(2);
        if(empty($respd)){
            $telegram->sendMessage($userid,'در حال حاضر اکانت تست وجود ندارد');
        }else{
            $keyboard = [];
            foreach($respd as $file){
                $id = $file['id'];
                $name = $file['title'];
                $keyboard[] = ['text' => "$name", 'callback_data' => "file#$id"];
            }
            $keyboard = array_chunk($keyboard,1);
			$msg = "🔰 لطفا یکی از گزینه ها را انتخاب کنید:";
			if(isset($cdata) and $cdata=='freesrvcs') {
				bot('editMessageText', [
					'chat_id' => $cuserid,
					'message_id' => $cmsgid,
					'text'=> $msg,
					'reply_markup' => json_encode([
						'inline_keyboard' => $keyboard
					])
				]);
			}else {
				bot('sendMessage', [
					'chat_id' => $userid,
					'text' => $msg,
					'reply_markup' => json_encode([
						'inline_keyboard' => $keyboard
					])
				]);
			}
            
        }
    }
}
if(preg_match('/file/',$cdata)){
    $input = explode('#', $cdata);
    $id = $input[1];
	$cid = $input[2];
    /*$rcount = $telegram->db->query("select * from fl_accounts WHERE fid={$id} and active=1 and sold=0")->rowCount();
    if($rcount == 0) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "در حال حاضر برای این پلن اکانت قابل فروشی وجود ندارد",
            'show_alert' => true
        ]);
        exit;
    }*/
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "♻️در حال دریافت جزییات ... ",
        'show_alert' => false
    ]);
    $respd = $telegram->db->query("select * from fl_file WHERE id='$id' and active=1")->fetch(2);
    $catname = $telegram->db->query("select * from fl_cat WHERE id=".$respd['catid'])->fetch(2)['title'];
    $name = $catname." ".$respd['title'];
    $price =  $respd['price'];
	
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
    if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
	
    $desc = $respd['descr'];
	$sid = $respd['server_id'];
    $fileImg = $respd['pic']."?".rand(0,999999999);
    $fileImg = "<a href='".baseURI."/$fileImg'>&#8194;</a>";
    
    $srvid = $respd['server_id'];
    $srv_type = $telegram->db->query("select * from server_info WHERE id='$srvid'")->fetch(2)['ptype'];
    if($price == 0 or ($userid == ADMIN or isAdmin() )){
        $keyboard = [ 
            [['text' => '📥 دریافت رایگان', 'callback_data' => $srv_type == 'xui' ? "download#$id" : "downMRZload#$id"]],

        ];
        if($userid == ADMIN or isAdmin() ){
            $keyboard = [
                [['text' => '📥 دریافت رایگان', 'callback_data' =>$srv_type == 'xui' ? "download#$id" : "downMRZload#$id"]],
                [['text' => 'ساخت کانفیگ برای کاربر', 'callback_data' => "createForUs#$id"]]
            ];
        }
    }else{
        $token = base64_encode("{$cuserid}.{$id}");
		if($gateways['bahamta']) $keyboard[] = [['text' => "پرداخت آنلاین - $price تومان", 'url' => baseURI."bahamta.php?type=buy&action=pay&token=$token"]];
 		if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $price تومان", 'url' => baseURI."pay.php?token=$token"]];
 		if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."nextpay/pay.php?token=$token"]];
		if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $price تومان",  'callback_data' => "offpay#$id"]];
        if($gateways['wallet']) $keyboard[] = [['text' => '🏦 پرداخت با کیف پول', 'callback_data' =>  $srv_type == 'marzban' ? "walMRZpay#$id" : "walpay#$id"]];
        
        
        $dcount = $telegram->db->query("select * from fl_discount WHERE active=1 and (sid = 0 or sid = $srvid)")->rowCount();
        if($dcount > 0){
            $keyboard[] = [['text' => '🔸کد تخفیف دارید؟ بزنید ', 'callback_data' => "submitdiscount#$id"]];
        }
    }
    $keyboard[] = [['text' => '🔙 بازگشت', 'callback_data' => (isset($input[2]) and $price !=0) ? ($price == 0 ? "freesrvcs" : "list#$cid#$sid") : "retailsrvc"]];
    $price = ($price == 0) ? 'رایگان' : number_format($price).' تومان ';
    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "
🔻$name
💰قیمت : $price
📃توضیحات :
$desc
$fileImg
",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/submitdiscount|submitRNdiscount/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendMessageCURL($userid,"کد تخفیف را وارد کنید :",$cancelop);
    exit;
}
if(preg_match('/submitdiscount|submitRNdiscount/',$state) and $text != '❌ انصراف'){
    $text = strtolower($text);
    $dcount = $telegram->db->query("select * from fl_discount WHERE code='$text' and active=1");
	if(!$dcount){
        $telegram->sendMessage($userid,"کد وارد شده اشتباه است❌");
    }else{
	  if($dcount->rowCount() > 0){
        if(preg_match('/submitRNdiscount/',$state)){
            $oid = str_replace('submitRNdiscount#','', $state);
            $respd = $telegram->db->query("select * from fl_order WHERE id='$oid'")->fetch(2);
            $fid = $respd['fileid'];
        }else{
            $fid = str_replace('submitdiscount#','', $state);
        }

        $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
        $catname = $telegram->db->query("select * from fl_cat WHERE id=".$respd['catid'])->fetch(2)['title'];
        $name = $catname." ".$respd['title'];
        $price = $respd['price'];
        $server_id = $respd['server_id'];
		
		$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
		if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
			
        $desc = $respd['descr'];
        $fileImg = $respd['pic']."?".rand(0,999999999);
        $fileImg = "<a href='".baseURI."/$fileImg'>&#8194;</a>";

        // discount
		$dres = $dcount->fetch(2);
		$min = $dres['min'];
		$max = $dres['max'];
		$amount = $dres['amount'];
		$ownerid = $dres['userid'];
		$expire_date = $dres['expire_date'];
		$srvid = $dres['sid'];

		if($ownerid != 0 && $ownerid != ''){
			if($ownerid != $userid){
				$telegram->sendMessage($userid,"شما امکان استفاده از این کد تخفیف را ندارید❌");
				exit;
			}
		}
		if($srvid != 0 && $srvid != $server_id){
			$telegram->sendMessage($userid,"امکان استفاده از این کد برای این سرویس وجود ندارد❌");
			exit;
		}
		if($expire_date !=0 and $expire_date < $time){
			$telegram->sendMessage($userid,"مدت زمان استفاده از این کد به پایان رسیده است❌");
			exit;
		}

		if( ($price < $min and $min !=0) or ($price > $max and $max !=0) ){
			$telegram->sendMessage($userid,"کد تخفیف وارد شده برای این سفارش معتبر نمی باشد❌");
			exit;
		}

		if($amount <= 100) {
			$price = number_format( $price * (100-$amount)/100 );
			$amount = "$amount %";
		}else {
			$price = number_format( $price - $amount );
			$amount = number_format($amount)." تومان ";
		}
        $telegram->sendMessageCURL($userid,"کد تخفیف به مقدار $amount اعمال شد :",$finalop);
		file_put_contents("state/$userid.txt",'');
		
		$srvid = $respd['server_id'];
		$srv_type = $telegram->db->query("select * from server_info WHERE id='$srvid'")->fetch(2)['ptype'];
		if(preg_match('/submitRNdiscount/',$state)){
		    $token = base64_encode("$userid#$fid#$oid#$text");
			if($gateways['bahamta']) $keyboard[] = [['text' => "پرداخت آنلاین - $price تومان", 'url' => baseURI."bahamta.php?type=renew&action=pay&token=$token"]];
            if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $price تومان", 'url' => baseURI."/renew/pay.php?token=$token"]];
            if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."/renew/nextpay/pay.php?token=$token"]];
            if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $price تومان",  'callback_data' => "offrnwpay#$oid#$text"]];
            if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "walrnwpay#$oid#$text"]];
           $aa = bot('SendMessage', [
                'chat_id' => $userid,
                'parse_mode' => "HTML",
                'text' => "لطفا با یکی از روش های زیر اکانت خود را تمدید کنید :",
                'reply_markup' => json_encode([ 
                    'inline_keyboard' => $keyboard
                ]) 
            ]); $telegram->sendMessageCURL($userid, json_encode($aa));
		}else{
			if($price == 0 or ($userid == ADMIN or isAdmin() )){
				$keyboard = [[['text' => '📥 دریافت رایگان', 'callback_data' => "download#$fid#code"]]];
			}else{
				$token = base64_encode("{$userid}.{$fid}.{$text}");
				if($gateways['bahamta']) $keyboard[] = [['text' => "پرداخت آنلاین - $price تومان", 'url' => baseURI."bahamta.php?type=buy&action=pay&token=$token"]];
				if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $price تومان", 'url' => baseURI."pay.php?token=$token"]];
				if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."nextpay/pay.php?token=$token"]];
				if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $price تومان",  'callback_data' => "offpay#$fid#$text"]];
				if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => $srv_type == 'marzban' ? "walMRZpay#$fid#$text" : "walpay#$fid#$text"]];
			}
			bot('SendMessage', [
            'chat_id' => $userid,
            'parse_mode' => "HTML",
			'text' => "🔻$name \n💰قیمت : $price تومان \n📃توضیحات : \n$desc \n$fileImg",
				'reply_markup' => json_encode([
					'inline_keyboard' => $keyboard
				])
			]);
		}
        

	   }else{
			$telegram->sendMessage($userid,"کد وارد شده اشتباه است❌");
	   }
	}
    
}
if(preg_match('/createForUs/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"لطفا آیدی عددی کاربر و تعداد اکانت را بصورت زیر وارد کنید:

1012656-1

مقدار اول ایدی عددی
مقدار دوم تعداد
",$cancelop);
    exit;
}

if(preg_match('/createForUs/',$state) and ($userid == ADMIN or isAdmin() ) and $text != '❌ انصراف'){
    $input = explode('-',$text);
    if(count($input) != 2){
        $telegram->sendMessage($userid,'لطفا فرمت صحیح و بصورت اعداد لاتین بفرستید');exit;
    }
    $uid = intval($input[0]);
    $ccount = intval($input[1]);
    $user = $telegram->db->query("select * from fl_user where userid=$uid")->fetch(2);
    if(!$user){
        $telegram->sendMessage($userid,'کاربر مورد نظر یافت نشد');
        exit;
    }
	if(!$ccount){
		$telegram->sendMessage($userid,'لطفا یک مقدار عددی صحیح و لاتین وارد کنید');
        exit;
	}
    file_put_contents("state/$userid.txt",'');
    $id = str_replace('createForUs#','',$state);
    $order = $telegram->db->query("select * from fl_file where id='$id'")->fetch(2);
    $server_id = $order['server_id'];
    $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
    
    $cdata = $panel_type == 'marzban' ? "down2MRZload#$id#$uid#$ccount" : "down2load#$id#$uid#$ccount";
     //$telegram->sendMessage($userid,$cdata);
}
if(preg_match('/offpay/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"<b>صورت حساب شما با موفقیت ایجاد شد😇
لطفا مبلغ مورد نظر را به حساب زیر واریز کنید🙏</b>

☘ $cardinfo ☘

<blockquote>این فاکتور فقط تا نیم ساعت اعتبار دارد</blockquote>
<blockquote>پس از ارسال رسید خرید ها توسط ادمین تایید میشود</blockquote>
<blockquote>با دقت خرید کنید امکان برداشت وجه نیست</blockquote>

پس از پرداخت موفق <b>تصویر فیش واریز</b> را ارسال کنید",$cancelop);
    exit;
}
if(preg_match('/offpay/',$state) and $text != '❌ انصراف'){
	bot('deleteMessage', ['chat_id' => $userid,'message_id' => $msgid -1]);
    $input = explode('#',$state);
    $fid = $input[1];
	$dcode = $input[2];
    file_put_contents("state/$userid.txt",'');
    $res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $uid = $res['userid'];
    $name = $res['name'];
    $tel = $res['tel'];
    $username = $res['username'];

    $res = $telegram->db->query("select * from fl_file where id=$fid")->fetch(2);
    $catname = $telegram->db->query("select * from fl_cat where id=".$res['catid'])->fetch(2)['title'];
    $filename = $catname." ".$res['title']; 
	$fileprice = $res['price'];
	
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
    if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));

	if($dcode){
        $dcount = $telegram->db->query("select * from fl_discount WHERE code='$dcode' and active=1");
        if($dcount->rowCount() > 0){
            $amount = $dcount->fetch(2)['amount'];
            if($amount <= 100) {
                $fileprice = $fileprice * (100-$amount)/100;
            }else {
                $fileprice = $fileprice - $amount ;
            }
        }
    }

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption (فیش واریزی در بالای این پیام هست)";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش, اطلاعات اکانت از طریق ربات برای شما ارسال می شود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷سفارش جدید خرید $filename ($fileprice تومان)
✖کد کاربری : $userid
👤نام و نام خانوادگی : $name
📧یوزرنیم : @$username
☎️شماره موبایل : $tel
📝اطلاعات پرداخت کارت به کارت: $infoc
.";
    $server_id = $res['server_id'];
    $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'تایید پرداخت', 'callback_data' => $panel_type == 'marzban' ? "enaMRZble#$uid#$fid" : "enable#$uid#$fid"],
                ['text' => 'عدم تایید', 'callback_data' => "disable#$uid"]
            ]
        ]
    ]);
    $uniqmsgid = time().rand(0,99999); 
    $admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3){
            if($fileid) bot('sendphoto',['chat_id' => $admid, 'caption'=> '','photo' => $fileid]);
            $msgres = bot('sendmessage',[
                'chat_id' => $admid,
                'text'=> $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
            $msgresid = $msgres->result->message_id;
            $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', $admid, '$msgresid', 0, $time)");
        }
    }
    if($fileid) bot('sendphoto',['chat_id' => ADMIN, 'caption'=> '','photo' => $fileid]);
    $msgres = bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
    $msgresid = $msgres->result->message_id;
    $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', ".ADMIN.", '$msgresid', 0, $time)");
}
if(preg_match('/enable/',$cdata) and $text != '❌ انصراف'){
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata);
    $uid = $input[1];
    $fid = $input[2];
    $acctxt = '';
    
    
    $file_detail = $telegram->db->query("select * from fl_file WHERE id=$fid")->fetch(2);
    $days = $file_detail['days'];
    $date = time();
    $expire_microdate = intval(floor(microtime(true) * 1000) + (864000 * $days * 100));
    $expire_date = $date + (86400 * $days);
    $type = $file_detail['type'];
    $volume = $file_detail['volume'];
    $protocol = $file_detail['protocol'];
    $price = $file_detail['price'];
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
    if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
	
    $server_id = $file_detail['server_id'];
    $netType = $file_detail['type'];
    $acount = $file_detail['acount'];
    $inbound_id = $file_detail['inbound_id'];
    $limitip = intval($file_detail['limitip']);
    $sendcount = $file_detail['sendcount'];
    
    if($acount == 0 and $inbound_id != 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'ظرفیت این کانکشن پر شده است',
            'show_alert' => false
        ]);
        exit;
    }
    if($inbound_id == 0) {
        $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
        if($server_info['ucount'] != 0) {
            $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
        } else {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'ظرفیت این سرور پر شده است',
                'show_alert' => false
            ]);
            exit;
        }
    }else{
        if($acount != 0) 
            $telegram->db->query("UPDATE `fl_file` SET `acount` = acount - 1 WHERE id=$fid");
    }

    // V2ray Api
    require_once('vray.php');
    include 'phpqrcode/qrlib.php';
	$path = 'images/';
    $file = $path.$userid.rand(0,9999999).".png"; //unlink($file);
    $ecc = 'L';
    $pixel_Size = 10;
    $frame_Size = 10;
    for($i=0;$i<$sendcount; $i++){
        $uniqid = generateRandomString(42,$protocol); 
        $savedinfo = file_get_contents('savedinfo.txt');
        $savedinfo = explode('-',$savedinfo);
        $port = $savedinfo[0] + 1;
        $last_num = $savedinfo[1] + 1;
    
        $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
        $uremark = $telegram->db->query("select * from fl_remark where userid=$uid")->fetch(2);
        if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
        $remark = "{$uremark}-{$last_num}";
    
        file_put_contents('savedinfo.txt',$port.'-'.$last_num);
        //$telegram->sendMessage($cuserid,"$server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume");exit;
        
        if($inbound_id == 0){    
            $response = add_inbound($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType); 
        }else {
            $response = add_inbount_client($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip); 
        }
        
        if(is_null($response)){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    	if($response == "inbound not Found"){
    		bot('answercallbackquery', [
    			'callback_query_id' => $cid,
    			'text' => "🔻سطر (inbound) با آیدی $inbound_id در این سرور یافت نشد یا کوکی منقضی شده. لطفا به مدیریت اطلاع بدید",
    			'show_alert' => true
    		]);
    		exit;
    	}
    	if(!$response->success){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);$telegram->sendMessage(ADMIN,"cardbuy = serverID: $server_id :".$response->msg);
            exit;
        }
    	bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '♻️در حال ارسال اکانت ...',
            'show_alert' => false
        ]);
        
        $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
        $acc_text = "🔗 $remark \n <code>$vray_link</code>" . "👆🏻برای کپی کردن لینک روی آن کلیک کنید

.1️⃣از بخش سرویس های من میتوانید
.سرویستون رو مدیریت کنید 
.2️⃣اگر مشکل اتصال داری و لینک وارد
.نمی‌شود «💡راهنمای اتصال » رو ببینید
.3⃣جهت دریافت اطلاعیه ها حتما در چنلمون عضو بشید و مارو معرفی کنید به دوستاتون🙏

.@wolfv2 @wolfv2 @wolfv2";

        QRcode::png($vray_link, $file, $ecc, $pixel_Size, $frame_Size);
	
	    $acc_text = "اطلاعات اکانت برای سفارش با کارت به کارت به شرح زیر است :
$acc_text";
    	$telegram->sendPhoto($uid,'',$file);
    	$keyboard = [
    	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
    	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
    		[
    		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$remark"],
    		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
		    ]
    	];
    	bot('sendmessage', [
    		'chat_id' => $uid,
    		'parse_mode' => 'HTML',
    		'text' => $acc_text,
    		'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
            ])
    	]);
        
    	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$uid}, '', {$fid}, $server_id, $inbound_id, '$remark', '$protocol', $expire_date, '$vray_link', $price,1, '$date', 0);");
        
    }

    
    $telegram->sendMessageCURL($userid,'اطلاعات اکانت با موفقیت برای کاربر ارسال شد',$finalop);
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid']; 
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        } 
    }

// pay referer
    $userReferer = $telegram->db->query("select * from fl_subuser where userid=$uid");
    if($userReferer->rowCount() ){
        $ures = $userReferer->fetch(2);
        $userToplevel = $ures['toplevel_userid'];
        $ufname = $ures['fname'];
        $amount = ($price) * ($pursant / 100);
        $telegram->db->query("update fl_user set wallet= wallet + $amount WHERE userid=$userToplevel");
        $telegram->sendMessage($userToplevel, "💟کاربر {$ufname} یک خرید به مبلغ  $price تومان انجام داد و $pursant درصد آن یعنی $amount تومان به کیف پول شما اضافه شد👍"); 
    }
    
    
}
if(preg_match('/disable/',$cdata) and ($userid==ADMIN or isAdmin()) ){
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid' and status=2")->fetch(2);
    if(!empty($res)){
        exit;
    } 
    
    file_put_contents("state/{$userid}.txt","$cdata#$cmsgid");
    $telegram->sendMessageCURL($userid,'لطفا دلیل عدم تایید تراکنش را وارد کنید (این متن برای مشتری ارسال می شود) ',$cancelop);
}
if(preg_match('/disable/',$state) and $text != '❌ انصراف'){
    
    
    
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$state);
    $uid = $input[1];
    $data = $input[2];
	if(isset($input[3])) $cmsgid = $input[3]; else $cmsgid = $input[2];
    // update button
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '❌ رد شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
	$res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid'];
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '❌ رد شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        }
    }
    $telegram->sendMessageCURL($userid,'متن پیام با موفقیت برای مشتری ارسال شد',$finalop);
    $telegram->sendMessage($uid,$text);

}
if(preg_match('/walpay/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
    $dcode = $input[2];
    $file_detail = $telegram->db->query("select * from fl_file WHERE id=$id")->fetch(2);
    $days = $file_detail['days'];
    $date = time();
    $expire_microdate = intval(floor(microtime(true) * 1000) + (864000 * $days * 100));
    $expire_date = $date + (86400 * $days);
    $type = $file_detail['type'];
    $volume = $file_detail['volume'];
    $protocol = $file_detail['protocol'];
    $price = $file_detail['price'];
	
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
    if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
	
    $server_id = $file_detail['server_id'];
    $netType = $file_detail['type'];
    $acount = $file_detail['acount'];
    $inbound_id = $file_detail['inbound_id'];
    $limitip = intval($file_detail['limitip']);


    if($dcode){
        $dcount = $telegram->db->query("select * from fl_discount WHERE code='$dcode' and active=1");
        if($dcount->rowCount() > 0){
            $dres = $dcount->fetch(2);
            $amount = $dres['amount'];
            if($amount <= 100) {
                $price = $price * (100-$amount)/100;
            }else {
                $price = $price - $amount ;
            }
            if($dres['count'] != '') $telegram->db->query("update fl_discount set count= count - 1 WHERE code='$dcode'");
        }
    }
    
    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }
    
    if($acount == 0 and $inbound_id != 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'ظرفیت این کانکشن پر شده است',
            'show_alert' => false
        ]);
        exit;
    }
    if($inbound_id == 0) {
        $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
        if($server_info['ucount'] != 0) {
            $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
        } else {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'ظرفیت این سرور پر شده است',
                'show_alert' => false
            ]);
            exit;
        }
    }else{
        if($acount != 0) 
            $telegram->db->query("UPDATE `fl_file` SET `acount` = acount - 1 WHERE id=$id");
    }
    

    // V2ray Api
    require_once('vray.php');
    $uniqid = generateRandomString(42,$protocol); 

    $savedinfo = file_get_contents('savedinfowg.txt');
    $savedinfo = explode('-',$savedinfo);
    $port = $savedinfo[0] + 1;
    $last_num = $savedinfo[1] + 1;

    $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
    $uremark = $telegram->db->query("select * from fl_remark where userid='$userid'")->fetch(2);
    if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
    $remark = "{$uremark}-{$last_num}";

    file_put_contents('savedinfowg.txt',$port.'-'.$last_num);
    //$telegram->sendMessage($cuserid,"$server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume");exit;
    
    if($protocol == 'wireguard'){
        $keys = generateKeyPair();
        if(!is_array($keys)){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خط در ساخت کانفیگ موردنظر....',
                'show_alert' => true
            ]);
            exit;
        }
        $secretKey = $keys['private_key'];

        $keys = generateKeyPair();
        $pubKey = $keys['public_key'];
        $prvKey = $keys['private_key'];
        $response = add_inbound_wg($server_id, $pubKey, $prvKey, $secretKey, $port, $expire_microdate, $remark, $volume);
    }else{
       if($inbound_id == 0){
            $response = add_inbound($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType); 
        }else {
            $response = add_inbount_client($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip); 
        } 
    }
    if(is_null($response)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }
	if($response == "inbound not Found"){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => "🔻سطر (inbound) با آیدی $inbound_id در این سرور یافت نشد یا کوکی منقضی شده. لطفا به مدیریت اطلاع بدید",
			'show_alert' => true
		]);
		exit;
	}
	if(!$response->success){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);$telegram->sendMessage($userid,"walletbuy = serverID: $server_id :".$response->msg);
        exit;
    }
	bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => '♻️در حال ارسال اکانت ...',
        'show_alert' => false
    ]);
    include 'phpqrcode/qrlib.php';
    $path = 'images/';
    $file = $path.$userid.rand(0,999999).time().".png"; //unlink($file);
    $ecc = 'L';
    $pixel_Size = 10;
    $frame_Size = 5;
    
    if($protocol == 'wireguard'){
        $peers = json_decode($response->obj->settings)->peers;
        $publicKey = $peers[0]->publicKey;
        $privateKey = $peers[0]->privateKey;
        $config_export = export_wgconfig($server_id, $port, $publicKey, $privateKey, $conf_arr[0], $remark);
        $wgfile = $config_export['file'];
        $wgtext = $config_export['text'];

        $server_info = $telegram->db->query("select * from fl_server WHERE id='$server_id' ")->fetch(2);
        $stitle = $server_info['title'];
        $date = jdate('d-m-Y', $expire_date);
        $vray_link = $wgtext;
        
        QRcode::png($wgtext, $file, $ecc, $pixel_Size, $frame_Size);
        
        $acc_text = "✅اکانت شما با موفقیت ایجاد شد \n\n👤کانفیگ: $remark \n\n🌐لوکیشن: $stitle \n\n🔋حجم: $volume گیگ \n\n📅 انقضا در $date ($days روز) \n\n .";
        $telegram->sendPhoto($userid,'',$file);
        
        $keyboard = [
    	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
    	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
    		[
    		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$remark"],
    		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
    	    ]
    	];
    	bot('sendmessage', [
    		'chat_id' => $userid,
    		'parse_mode' => 'HTML',
    		'text' => $acc_text,
    		'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
            ])
    	]);
        
        curlReq('sendDocument', $userid, $wgfile, '');
        unset($conf_arr[0]);
        foreach($conf_arr as $conftxt){
            $config_export = export_wgconfig($server_id, $port, $publicKey, $privateKey, $conftxt, $remark);
            $wgfile = $config_export['file'];
        	curlReq('sendDocument', $userid, $wgfile, '');
        }
        
    }else{
        $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
    
        $addsub = "";
        if($gateways['sublink'] == 1){
        	$subid = getsubid($server_id, $inbound_id, $remark);
        	if($subid != '' and !is_null($subid) and $subid != '0'){
        		$server_detail = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2);
        		$pnlurl = $server_detail['sublink'];
        		$sublink = $pnlurl."/sub/$subid";
        		$addsub = "\n\n (لینک سابسکریپشن با امکان نمایش حجم و تاریخ انقضا و بروز رسانی لینک ) \n $sublink \n";
        	}
        }
        $acc_text = "🔗 $remark \n <code>$vray_link</code> $addsub" . " \n  برای کپی کردن لینک روی آن کلیک کنید \n";
    	
    	
        QRcode::png($vray_link, $file, $ecc, $pixel_Size, $frame_Size);
    	$telegram->sendPhoto($userid,'',$file);
    	$keyboard = [
    	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
    	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
    		[
    		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$remark"],
    		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
		    ]
    	];
    	bot('sendmessage', [
    		'chat_id' => $userid,
    		'parse_mode' => 'HTML',
    		'text' => $acc_text,
    		'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard,
            ])
    	]);
    }
	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$userid}, '', {$id}, $server_id, $inbound_id, '$remark', '$protocol', $expire_date, '$vray_link', $price,1, '$date', 0);");
    $telegram->db->query("update fl_user set wallet = wallet - $price WHERE userid='$userid'");
    
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
    
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
$sndmsg = "
خرید با کیف پول 
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
لینک دریافتی :

$vray_link
";
    $telegram->sendMessage($sendchnl,$sndmsg);
}
 
if(preg_match('/down2load/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
    $ccount = 1;
    $uid = $userid;
    
    if(count($input) == 4){
        $uid = $input[2];
        $ccount = $input[3];
    }
	include_once 'phpqrcode/qrlib.php';
	$path = 'images/';
    $file = $path.$uid.time().rand(0,9999999).".png"; //unlink($file);
    $ecc = 'L';
    $pixel_Size = 10;
    $frame_Size = 5;
    for($i=0;$i<$ccount; $i++){
        $file_detail = $telegram->db->query("select * from fl_file WHERE id=$id")->fetch(2);
        $days = $file_detail['days'];
        $date = time();
        $expire_microdate = floor(microtime(true) * 1000) + (864000 * $days * 100);
        $expire_date = $date + (86400 * $days);
        $type = $file_detail['type'];
        $volume = $file_detail['volume'];
        $protocol = $file_detail['protocol'];
        $price = $file_detail['price'];
        $server_id = $file_detail['server_id'];
        $acount = $file_detail['acount'];
        $inbound_id = $file_detail['inbound_id'];
        $limitip = $file_detail['limitip'];
        $netType = $file_detail['type'];
        
        if($acount == 0 and $inbound_id != 0){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'ظرفیت این کانکشن پر شده است',
                'show_alert' => false
            ]);
            exit;
        }
        if($inbound_id == 0) {
            $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
            if($server_info['ucount'] != 0) {
                $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
            } else {
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => 'ظرفیت این سرور پر شده است',
                    'show_alert' => false
                ]);
                exit;
            }
        }else{
            if($acount != 0) 
                $telegram->db->query("UPDATE `fl_file` SET `acount` = acount - 1 WHERE id=$id");
        }
        
        //$telegram->sendMessage($userid,$expire_microdate);exit;
        // V2ray Api
        require_once('vray.php');
        $uniqid = generateRandomString(42,$protocol); 
    
        $savedinfo = file_get_contents('savedinfowg.txt');
        $savedinfo = explode('-',$savedinfo);
        $port = $savedinfo[0] + 1;
        $last_num = $savedinfo[1] + 1;
    
        $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
		$uremark = $telegram->db->query("select * from fl_remark where userid='$uid'")->fetch(2);
		if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
		$remark = "{$uremark}-{$last_num}";
    
        file_put_contents('savedinfowg.txt',$port.'-'.$last_num);
        if($protocol == 'wireguard'){
            $keys = generateKeyPair();
            if(!is_array($keys)){
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => '🔻خط در ساخت کانفیگ موردنظر....',
                    'show_alert' => true
                ]);
                exit;
            }
            $secretKey = $keys['private_key'];
    
            $keys = generateKeyPair();
            $pubKey = $keys['public_key'];
            $prvKey = $keys['private_key'];
            $response = add_inbound_wg($server_id, $pubKey, $prvKey, $secretKey, $port, $expire_microdate, $remark, $volume);
        }else{
           if($inbound_id == 0){
                $response = add_inbound($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType); 
            }else {
                $response = add_inbount_client($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip); 
            } 
        }
        if(is_null($response)){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    	if(!$response->success){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    	bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '♻️در حال ارسال اکانت ...',
            'show_alert' => false
        ]);
        $path = 'images/';
        $file = $path.$userid.rand(0,999999).time().".png"; //unlink($file);
        $ecc = 'L';
        $pixel_Size = 10;
        $frame_Size = 5;
        
        if($protocol == 'wireguard'){
            $peers = json_decode($response->obj->settings)->peers;
            $publicKey = $peers[0]->publicKey;
            $privateKey = $peers[0]->privateKey;
            $config_export = export_wgconfig($server_id, $port, $publicKey, $privateKey, $conf_arr[0], $remark);
            $wgfile = $config_export['file'];
            $wgtext = $config_export['text'];
    
            $server_info = $telegram->db->query("select * from fl_server WHERE id='$server_id' ")->fetch(2);
            $stitle = $server_info['title'];
            $date = jdate('d-m-Y', $expire_date);
            $vray_link = $wgtext;
            
            QRcode::png($wgtext, $file, $ecc, $pixel_Size, $frame_Size);
            
            $acc_text = "✅اکانت شما با موفقیت ایجاد شد \n\n👤کانفیگ: $remark \n\n🌐لوکیشن: $stitle \n\n🔋حجم: $volume گیگ \n\n📅 انقضا در $date ($days روز) \n\n .";
            $telegram->sendPhoto($userid,'',$file);
            
            $keyboard = [
        	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
        	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
        		[
        		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$remark"],
        		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
        	    ]
        	];
        	bot('sendmessage', [
        		'chat_id' => $userid,
        		'parse_mode' => 'HTML',
        		'text' => $acc_text,
        		'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard,
                ])
        	]);
            
            curlReq('sendDocument', $userid, $wgfile, '');
            unset($conf_arr[0]);
            foreach($conf_arr as $conftxt){
                $config_export = export_wgconfig($server_id, $port, $publicKey, $privateKey, $conftxt, $remark);
                $wgfile = $config_export['file'];
            	curlReq('sendDocument', $userid, $wgfile, '');
            }
            
        }else{
            $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
        
            $addsub = "";
            if($gateways['sublink'] == 1){
            	$subid = getsubid($server_id, $inbound_id, $remark);
            	if($subid != '' and !is_null($subid) and $subid != '0'){
            		$server_detail = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2);
            		$pnlurl = $server_detail['sublink'];
            		$sublink = $pnlurl."/sub/$subid";
            		$addsub = "\n\n (لینک سابسکریپشن با امکان نمایش حجم و تاریخ انقضا و بروز رسانی لینک ) \n $sublink \n";
            	}
            }
            $acc_text = "🔗 $remark \n <code>$vray_link</code> $addsub" . " \n  برای کپی کردن لینک روی آن کلیک کنید \n";
        	
        	
            QRcode::png($vray_link, $file, $ecc, $pixel_Size, $frame_Size);
        	$telegram->sendPhoto($uid,'',$file);
        	$keyboard = [
        	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
        	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
        		[
        		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$remark"],
        		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
    		    ]
        	];
        	bot('sendmessage', [
        		'chat_id' => $uid,
        		'parse_mode' => 'HTML',
        		'text' => $acc_text,
        		'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard,
                ])
        	]);
        }
    	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$uid}, '', {$id}, $server_id, $inbound_id, '$remark', '$protocol', $expire_date, '$vray_link', $price,1, '$date', 0);");
        
    	if(count($input) == 2) file_put_contents("state/{$uid}-free.txt",$free - 1);
    }
    
    $telegram->sendHTML($userid,"اکانت با موفقیت برای کاربر ارسال شد",$finalop);
    
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
}
if(preg_match('/download/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
    if(count($input) == 3) $code = $input[2];
	$ccount = 1;
	
	if(is_null($code)){
		$free = file_get_contents("state/{$userid}-free.txt");
		if($free == '') $free = 2;
		if($free < 2 and !($userid == ADMIN or isAdmin() )){
			bot('answercallbackquery', [
				'callback_query_id' => $cid,
				'text' => '⚠️شما قبلا هدیه رایگان خود را دریافت کردید',
				'show_alert' => false
			]); 
			exit;
		}
	}
	
	
    $file_detail = $telegram->db->query("select * from fl_file WHERE id=$id")->fetch(2);
    $days = $file_detail['days'];
    $date = time();
    $expire_microdate = intval(floor(microtime(true) * 1000) + (864000 * $days * 100));
    $expire_date = $date + (86400 * $days);
    $type = $file_detail['type'];
    $volume = $file_detail['volume'];
    $protocol = $file_detail['protocol'];
    $price = $file_detail['price'];
    $server_id = $file_detail['server_id'];
    $acount = $file_detail['acount'];
    $inbound_id = $file_detail['inbound_id'];
    $limitip = intval($file_detail['limitip']);
    $netType = $file_detail['type'];
    $sendcount = $file_detail['sendcount'];
    
    if($acount == 0 and $inbound_id != 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'ظرفیت این کانکشن پر شده است',
            'show_alert' => false
        ]);
        exit;
    }
    if($inbound_id == 0) {
        $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
        if($server_info['ucount'] != 0) {
            $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
        } else {
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => 'ظرفیت این سرور پر شده است',
                'show_alert' => false
            ]);
            exit;
        }
    }else{
        if($acount != 0) 
            $telegram->db->query("UPDATE `fl_file` SET `acount` = acount - 1 WHERE id=$id");
    }

    // V2ray Api
    require_once('vray.php');
    include_once 'phpqrcode/qrlib.php';
	$path = 'images/';
    $file = $path.$userid.rand(0,9999999).".png"; //unlink($file);
    $ecc = 'L';
    $pixel_Size = 10;
    $frame_Size = 5;
    for($i=0;$i<$sendcount; $i++){
        $uniqid = generateRandomString(42,$protocol); 
        $savedinfo = file_get_contents('savedinfowg.txt');
        $savedinfo = explode('-',$savedinfo);
        $port = $savedinfo[0] + 1;
        $last_num = $savedinfo[1] + 1;
    
        $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
        $uremark = $telegram->db->query("select * from fl_remark where userid=$userid")->fetch(2);
        if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
        $remark = "{$uremark}-{$last_num}";
    
        file_put_contents('savedinfowg.txt',$port.'-'.$last_num);
        if($protocol == 'wireguard'){
            $keys = generateKeyPair();
            if(!is_array($keys)){
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => '🔻خط در ساخت کانفیگ موردنظر....',
                    'show_alert' => true
                ]);
                exit;
            }
            $secretKey = $keys['private_key'];
    
            $keys = generateKeyPair();
            $pubKey = $keys['public_key'];
            $prvKey = $keys['private_key'];
            $response = add_inbound_wg($server_id, $pubKey, $prvKey, $secretKey, $port, $expire_microdate, $remark, $volume);
        }else{
           if($inbound_id == 0){
                $response = add_inbound($server_id, $uniqid, $protocol, $port, $expire_microdate, $remark, $volume, $netType); 
            }else {
                $response = add_inbount_client($server_id, $uniqid, $inbound_id, $expire_microdate, $remark, $volume, $limitip); 
            } 
        }
        if(is_null($response)){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    	if($response == "inbound not Found"){
    		bot('answercallbackquery', [
    			'callback_query_id' => $cid,
    			'text' => "🔻سطر (inbound) با آیدی $inbound_id در این سرور یافت نشد یا کوکی منقضی شده. لطفا به مدیریت اطلاع بدید",
    			'show_alert' => true
    		]);
    		exit;
    	}
    	if(!$response->success){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage(ADMIN,"free = serverID: $server_id :".$response->msg);
            exit;
        }
    	bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '♻️در حال ارسال اکانت ...',
            'show_alert' => false
        ]);
        
        $path = 'images/';
        $file = $path.$userid.rand(0,999999).time().".png"; //unlink($file);
        $ecc = 'L';
        $pixel_Size = 10;
        $frame_Size = 5;
        
        if($protocol == 'wireguard'){
            $peers = json_decode($response->obj->settings)->peers;
            $publicKey = $peers[0]->publicKey;
            $privateKey = $peers[0]->privateKey;
            $config_export = export_wgconfig($server_id, $port, $publicKey, $privateKey, $conf_arr[0], $remark);
            $wgfile = $config_export['file'];
            $wgtext = $config_export['text'];
    
            $server_info = $telegram->db->query("select * from fl_server WHERE id='$server_id' ")->fetch(2);
            $stitle = $server_info['title'];
            $date = jdate('d-m-Y', $expire_date);
            $vray_link = $wgtext;
            
            QRcode::png($wgtext, $file, $ecc, $pixel_Size, $frame_Size);
            
            $acc_text = "✅اکانت شما با موفقیت ایجاد شد \n\n👤کانفیگ: $remark \n\n🌐لوکیشن: $stitle \n\n🔋حجم: $volume گیگ \n\n📅 انقضا در $date ($days روز) \n\n .";
            $telegram->sendPhoto($userid,'',$file);
            
            $keyboard = [
        	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
        	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
        		[
        		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$remark"],
        		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
        	    ]
        	];
        	bot('sendmessage', [
        		'chat_id' => $userid,
        		'parse_mode' => 'HTML',
        		'text' => $acc_text,
        		'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard,
                ])
        	]);
            
            curlReq('sendDocument', $userid, $wgfile, '');
            unset($conf_arr[0]);
            foreach($conf_arr as $conftxt){
                $config_export = export_wgconfig($server_id, $port, $publicKey, $privateKey, $conftxt, $remark);
                $wgfile = $config_export['file'];
            	curlReq('sendDocument', $userid, $wgfile, '');
            }
            
        }else{
            $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
        
            $addsub = "";
            if($gateways['sublink'] == 1){
            	$subid = getsubid($server_id, $inbound_id, $remark);
            	if($subid != '' and !is_null($subid) and $subid != '0'){
            		$server_detail = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2);
            		$pnlurl = $server_detail['sublink'];
            		$sublink = $pnlurl."/sub/$subid";
            		$addsub = "\n\n (لینک سابسکریپشن با امکان نمایش حجم و تاریخ انقضا و بروز رسانی لینک ) \n $sublink \n";
            	}
            }
            $acc_text = "🔗 $remark \n <code>$vray_link</code> $addsub" . " \n  برای کپی کردن لینک روی آن کلیک کنید \n";
        	
        	
            QRcode::png($vray_link, $file, $ecc, $pixel_Size, $frame_Size);
        	$telegram->sendPhoto($userid,'',$file);
        	$keyboard = [
        	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
        	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
        		[
        		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$remark"],
        		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
    		    ]
        	];
        	bot('sendmessage', [
    			'chat_id' => $userid,
    			'parse_mode' => 'HTML',
    			'text' => $acc_text,
    			'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard,
                ])
    		]);
        }
    
    	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$userid}, '', {$id}, $server_id, $inbound_id, '$remark', '$protocol', $expire_date, '$vray_link', $price,1, '$date', 0);");
        
    }
	file_put_contents("state/{$userid}-free.txt",$free - 1);
    
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
}
if(preg_match('/connctedmsg/',$cdata)){
    $input = explode('#', $cdata);
    $remark = $input[1];
    $keyboard = [
        [
            ['text' => '⭐ 1', 'callback_data' => "rate#1#$remark"],
            ['text' => '⭐ 2', 'callback_data' => "rate#2#$remark"],
            ['text' => '⭐ 3', 'callback_data' => "rate#3#$remark"],
            ['text' => '⭐ 4', 'callback_data' => "rate#4#$remark"],
            ['text' => '⭐ 5', 'callback_data' => "rate#5#$remark"]
        ],
    ];
    
    bot('sendMessage', [
        'chat_id' => $userid,
        'text' => "خب تبریک میگم بهت☺️، خوشحال میشم تجربه اتصالت رو از این سرویس باهام در میون بزاری.\n<b>از یک تا پنج ستاره چه امتیازی بهش میدی؟</b>",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);

    exit;
}
if(preg_match('/rate#/',$cdata)){
    $input = explode('#', $cdata);
    $rate = $input[1];
    $remark = $input[2];
    
    bot('answercallbackquery', [
		'callback_query_id' => $cid,
		'text' => "امتیاز شما ثبت شد",
		'show_alert' => false
	]);
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
    $keyboard = [
        [['text' => 'نمایش اطاعات کاربر', 'callback_data' => "gusinf#$userid"]]
    ];
    
    bot('sendMessage', [
        'chat_id' => $sendchnl,
        'text' => "امتیاز $rate توسط $userid برای سرویس $remark ثبت شد",
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
    
    exit;
}
if($cdata == 'connctnotmsg'){
    
    $telegram->sendMessage($userid,"در صورتیکه آموزش های مورد نیاز رو دیدین و هنوز مشکلی در اتصال یا سوالی دارید به پشتیبانی پیام بدین تا در اسرع وقت راهنمایی دریافت کنید

آيدی پشتیبان : $supportus");

exit;

}
if ($text == '➕ثبت پلن' and ($userid == ADMIN or isAdmin() )){
    $state = file_put_contents('state/'.$userid.'.txt','addproduct');
    $telegram->db->query("delete from fl_file WHERE active=0");
    $sql = "INSERT INTO `fl_file` VALUES (NULL, '', 0,0,0,0, 1, '', '', 0, 0, '', 0, '', '',0,1, '$time',1,0);";
    $telegram->db->query($sql);
    $msg = '◀️ لطفا عنوان پلن را وارد کنید';
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
    exit;
}
// add product
if(preg_match('/addproduct/',$state) and $text!='❌ انصراف'){

    $catkey = [];
    $cats = $telegram->db->query("SELECT * FROM `fl_cat` WHERE parent =0 and active=1")->fetchAll(2);
    foreach ($cats as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $catkey[] = ["$id - $name"];
    }
    $catkey[] = ['❌ انصراف'];
    
    $step = $telegram->checkStep('fl_file');
    if($step==1 and $text!='❌ انصراف'){
        $msg = '✅عنوان پلن با موفقیت ثبت شد
◀️ لطفا قیمت پلن را به تومان وارد کنید
* عدد 0 به معنای رایگان بودن است.
';
        if(strlen($text)>1){
            $telegram->db->query("update fl_file set title='$text',step=32 where active=0 and step=1");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 1
    if($step==32 and $text!='❌ انصراف'){
        $msg = 'تعداد اکانت را به اعداد لاتین وارد کنید مثلا 1';
        if(is_numeric($text)){
            $telegram->db->query("update fl_file set price='$text',step=28 where active=0");
            $telegram->sendMessage($userid,$msg);
        }else{
            $msg = '‼️ لطفا یک مقدار عددی وارد کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } 
    if($step==28 and $text!='❌ انصراف'){
        $msg = '◀️ اگر این پلن برای خریداران عمده هست عدد 1 در غیراینصورت 0 را بزنید';
        if(is_numeric($text)){
            $telegram->db->query("update fl_file set sendcount=$text,step=30 where active=0");
            $telegram->sendMessage($userid,$msg);
        }else{
            $msg = '‼️ لطفا یک مقدار عددی وارد کنید';
            $telegram->sendMessage($userid,$msg);
        }
    } 
    if($step==30 and $text!='❌ انصراف'){
        $msg = '◀️ لطفا دسته بندی پلن را انتخاب کنید';
        if(is_numeric($text)){
            $telegram->db->query("update fl_file set isvip=$text,step=3 where active=0");
            $telegram->sendMessageCURL($userid,$msg,$catkey);
        }else{
            $msg = '‼️ لطفا یک مقدار عددی وارد کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    }
    if($step==3 and $text!='❌ انصراف'){
        
        $inarr = 0;
        foreach ($catkey as $op) {
            if (in_array($text, $op) and $text != '❌ انصراف') {
                $inarr = 1;
            }
        }
        if( $inarr==1 ){
            $input = explode(' - ',$text);
            $catid = $input[0];
            $telegram->db->query("update fl_file set catid='$catid',step=20 where active=0");
            $srvkey = [];
            $telegram->sendMessageCURL($userid,'✅دسته بندی پلن موفقیت ثبت شد. ',$cancelop);
            $srvs = $telegram->db->query("SELECT * FROM `fl_server` WHERE active=1")->fetchAll(2);
            foreach($srvs as $srv){
                $id = $srv['id'];
                $title = $srv['title'];
                $srvkey[] = ['text' => "$title", 'callback_data' => "slctsrv#$id"];
            }
            $srvkey = array_chunk($srvkey,2);
            bot('sendmessage', [
                'chat_id' => $userid,
                'parse_mode' => "HTML",
                'text' => "لطفا سرور را مشخص کنید :",
                'reply_markup' => json_encode([
                    'inline_keyboard' => $srvkey])
            ]);
        }else{
            $msg = '‼️ لطفا فقط یکی از گزینه های پیشنهادی زیر را انتخاب کنید';
            $telegram->sendMessageCURL($userid,$msg,$catkey);
        }
    } //step 3
    if($step==20 and $text!='❌ انصراف' and preg_match('/slctsrv/', $cdata)){
        $srvid = str_ireplace('slctsrv#','',$cdata);
        $srvdetail = $telegram->db->query("select * from server_info where id=$srvid")->fetch(2);
        $msg = '✅سرور پلن موفقیت ثبت شد. 
◀️ لطفا مشخصات سرویس را به اینصورت وارد کنید :

اگر میخواهید کانکشن یا پورت اختصاصی برای هر مشتری ایجاد شود لطفا بصورت زیر وارد کنید◀️
<code>vmess-30-50-ws</code>

مقدار اول (vmess | vless | trojan | shadowsocks | wireguard) پروتکل
مقدار دوم (30) تعداد روزهای اعتبار
مقدار سوم (50) حجم به گیگابایت
مقدار چهارم (ws | tcp) نوع

هر چهار مقدار با - از هم جدا می شوند

=======================================
اگر سرور با قابلیت چند کلاینت روی پورت هست لطفا بصورت زیر وارد کنید◀️
<code>vmess-30-100-1-20-1</code>

مقدار اول (vmess | vless | trojan) پروتکل
مقدار دوم (30) تعداد روزهای اعتبار
مقدار سوم (100) حجم به گیگابایت
مقدار چهارم (1) آیدی سطر کانکشن در پنل (inbound id)
مقدار پنجم (20) ظرفیت تعداد اکانت روی کانکشن یا همان پورت
مقدار ششم (1) چندکاربره (اگر 0 باشد نامحدود است)

نکته: برای حجم می توانید بصورت 0.5 بزنید یعنی 500 مگ
* برای دقیقه پسوند min و برای ساعت پسوند hr اضافه کنید. مثلا 45 دقیقه را 45min بزنید و 2ساعت را 2hr و برای روز هم که بدون پسوند بزنید مثلا 10روز را بزنید 10

.';
if($srvdetail['ptype'] == 'marzban'){
             $msg = '✅سرور پلن موفقیت ثبت شد. 
◀️ لطفا مشخصات سرویس را به اینصورت وارد کنید :

<code>(vmess|vless)-30-50</code>

مقدار اول (vmess|vless|trojan|shadowsocks) پروتکل. می توانید فقط یکی یا هر چهارتا را با | بزنید
مقدار دوم (30) تعداد روزهای اعتبار
مقدار سوم (50) حجم به گیگابایت

هر سه مقدار با - از هم جدا می شوند

نکته: برای حجم می توانید بصورت 0.5 بزنید یعنی 500 مگ
* برای دقیقه پسوند min و برای ساعت پسوند hr اضافه کنید. مثلا 45 دقیقه را 45min بزنید و 2ساعت را 2hr و برای روز هم که بدون پسوند بزنید مثلا 10روز را بزنید 10

.';
        }
       $telegram->db->query("update fl_file set server_id=$srvid,step=21 where active=0");
        $telegram->sendHTML($userid,$msg,$cancelop);
    } //step 20
    if($step==21 and $text!='❌ انصراف'){
        $input = explode('-',$text);
        $protocol = $input[0];
        
        $filedetail = $telegram->db->query("select * from fl_file where active=0 and step=21")->fetch(2);
        $srvid = $filedetail['server_id'];
        $srvdetail = $telegram->db->query("select * from server_info where id=$srvid")->fetch(2);
        if($srvdetail['ptype'] == 'marzban'){
            if(count($input) != 3) {$telegram->sendMessage($userid,"لطفا متن بالا را با دقت بخونید و فرمت درست را بفرستید مثل \n (vmess|vless)-30-50");exit; }
            $protocol = str_replace(['(',')'], '', $protocol);
            $days = $input[1];
			if(preg_match('/hr/',$days)) $days = str_replace('hr','',$days) / 24; elseif(preg_match('/min/',$days)) $days = (str_replace('min','',$days) / 60) / 24;
            $volume = $input[2];
            $type = $input[3];
            $telegram->db->query("update fl_file set protocol='$protocol',days='$days',volume='$volume',step=4 where active=0");
        }else{
           if(!in_array($protocol,['vmess','vless','trojan','shadowsocks','wireguard'])){
    			$telegram->sendMessage($userid,"مقدار وارد شده ($protocol) برای پروتکل صحیح نیست. لطفا پروتکل صحیح را وارد کنید"); exit;
    		}
            if(preg_match('/tcp|ws|kcp|grpc|http/',$text)){
    			if(count($input) != 4) {$telegram->sendMessage($userid,"لطفا متن بالا را با دقت بخونید و فرمت درست را بفرستید مثل \n vmess-30-30-tcp");exit; }
                $days = $input[1];
    			if(preg_match('/hr/',$days)) $days = str_replace('hr','',$days) / 24; elseif(preg_match('/min/',$days)) $days = (str_replace('min','',$days) / 60) / 24;
                $volume = $input[2];
                $type = $input[3];
                $telegram->db->query("update fl_file set protocol='$protocol',days='$days',volume='$volume',type='$type',step=4 where active=0");
            }else {
    			if(count($input) != 6) {$telegram->sendMessage($userid,"لطفا متن بالا را با دقت بخونید و فرمت درست را بفرستید مثل \n vmess-30-100-1-20-1 \n یا\n vmess-30-50-tcp");exit; }
                $days = $input[1];
    			if(preg_match('/hr/',$days)) $days = str_replace('hr','',$days) / 24; elseif(preg_match('/min/',$days)) $days = (str_replace('min','',$days) / 60) / 24;
                $volume = $input[2];
                $inbound_id = $input[3];
                $acount = $input[4];
                $limitip = $input[5];
                $telegram->db->query("update fl_file set protocol='$protocol',limitip=$limitip,inbound_id=$inbound_id,acount=$acount,days='$days',volume='$volume',step=4 where active=0");
            } 
        }
		

        $msg = '✅مشخصات سرویس با موفقیت ثبت شد . 
◀️ لطفا توضیحات را وارد کنید
.';
    $telegram->sendHTML($userid,$msg,$cancelop); 
    
    } //step 21
    if($step==4 and $text!='❌ انصراف'){
        $msg = '✅توضیحات با موفقیت ثبت شد . 
◀️ لطفا تصویر یا پیشنمایش را بصورت عکس ارسال کنید
.';
        if(strlen($text)>1 ){
            $telegram->db->query("update fl_file set descr='$text',step=5 where step=4");
            $telegram->sendMessageCURL($userid,$msg,$imgop);
        }

    } //step 4
    if($step==5 and $text!='❌ انصراف'){
        $imgtxt = '✅عملیات ثبت با موفقیت انجام شد ';
        //if($text != 'رد کردن این مرحله'){}
        $msg = $imgtxt.' 
◀ حالا️ اکانت های این پلن  را بصورت زیر ارسال کنید
دقت کنید که تمامی اطلاعات اکانت را با عبارت seprator از هم جدا کنید 

username: Test password: pwd...

seprator

username: Test
password: pwd
';
        if($text == 'رد کردن این مرحله'){
            $telegram->db->query("update fl_file set active=1,step=10 where step=5");
            $telegram->sendMessageCURL($userid,$imgtxt,$adminop);
			file_put_contents('state/'.$userid.'.txt','');
        }elseif($fileid){
            $photoURL = $telegram->FileURL($fileid);
            $photoext = pathinfo(basename($photoURL),PATHINFO_EXTENSION);
            $image = "images/".time().".$photoext";
            $somecontent = get_web_page($photoURL."?".rand(0,999999999));
            $handle = fopen($image,"x+");
            fwrite($handle,$somecontent);
            fclose($handle);

            $telegram->db->query("update fl_file set pic='$image',active=1,step=10 where step=5");
            $telegram->sendMessageCURL($userid,$imgtxt,$adminop);
			file_put_contents('state/'.$userid.'.txt','');
        }else{
            $msg = '‼️ لطفا تصویر را ارسال کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 5
    if($step==6 and $text!='❌ انصراف'){
        if(preg_match('/seprator/',strtolower($text))){
            $telegram->db->query("update fl_file set fileid='$fileid',active=1,step=10 where step=6");
            $id = $telegram->db->query("select * from fl_file where active=1 order by id DESC limit 1")->fetch(2)['id'];

            $accs = explode('seprator',$text);
            foreach ($accs as $acc){
                if(strlen($acc) > 5)
                    $telegram->db->query("INSERT INTO `fl_accounts` (`id`, `fid`, `text`, `sold`, `active`) VALUES (NULL, $id, '$acc', '0', '1');");
            }
            $msg = "✅️ اکانت های این پلن  با موفقیت ثبت شد";
            $telegram->sendMessageCURL($userid,$msg,$finalop);
            file_put_contents('state/'.$userid.'.txt','');
        }else{
            $msg = '‼️ لطفا اکانت ها را با جداکننده معتبر ارسال کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 6
}
// end add product
if($text=='مدیریت پلن ها' or $cdata == 'backplan' and ($userid==ADMIN or isAdmin() )){
    $res = $telegram->db->query("select * from fl_server where active=1")->fetchAll(2);
    if(empty($res)){
        $telegram->sendMessage($userid, 'لیست سرورها خالی است ');
        exit;
    }
    $keyboard = [];
    foreach($res as $cat){
        $id = $cat['id'];
        $title = $cat['title'];
        $keyboard[] = ['text' => "$title", 'callback_data' => "plalllan#$id"];
    }
    $keyboard = array_chunk($keyboard,2);
    
    $msg = ' 📍 برای دیدن لیست پلن ها روی سرور بزنید👇';
    
    if(isset($cdata) and $cdata=='backplan') {
        bot('editMessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else { $telegram->sendAction($userid, 'typing');
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
    
    
    exit;
}

if(preg_match('/plalllan/', $cdata)){
    $id = str_replace('plalllan#','', $cdata);
    $res = $telegram->db->query("SELECT * FROM `fl_file` WHERE server_id=$id order by id asc")->fetchAll(2);
    if(empty($res)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "هیچ پلنی برای این سرور وجود ندارد",
            'show_alert' => false
        ]);exit;
    }else {
        $keyboard = [];
        foreach($res as $cat){
            $id = $cat['id'];
            $title = $cat['title'];
            $keyboard[] = ['text' => "#$id $title", 'callback_data' => "pldetail#$id"];
        }
        $keyboard = array_chunk($keyboard,2);
        $keyboard[] = [['text' => "↪ برگشت", 'callback_data' => "backplan"],];
        $msg = ' 📍 یکی از پلن ها را انتخاب کنید تا جزییات آن را ببینید👇';
           $aa = bot('editmessageText', [
                'chat_id' => $userid,
                'message_id' => $cmsgid,
                'parse_mode' => "HTML",
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]);
        }
    
}
if(preg_match('/pldetail/', $cdata)){
    $id = str_replace('pldetail#','', $cdata);
    $pd = $telegram->db->query("SELECT * FROM `fl_file` WHERE id=$id")->fetch(2);
    if(empty($pd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "موردی یافت نشد",
            'show_alert' => false
        ]);exit;
    }else {
        $id=$pd['id'];
        $name=$pd['title'];
        $price=$pd['price'];
        $acount =$pd['acount'];
        //$forder = $telegram->db->query("select * from fl_order where status=1 and fileid=$id")->fetch(2);
        $accnum = $telegram->db->query("select * from fl_order where status=1 and fileid=$id")->rowCount();
        $srvid= $pd['server_id'];
        //$srvname = $telegram->db->query("select * from fl_server where id=$srvid")->fetch(2)['title'];
        $msg = "
▪️#$id
📡srv $srvid 
🔻نام: $name /chpnm$id
💶قیمت: $price تومان /chpp$id
✴️ویرایش توضیحات: /desc$id
©️کپی:  /copypl$id
❌حذف: /delpd$id
تعداد اکانت های فروخته شده: $accnum
⚡دریافت لیست اکانت ها: /getlistpd$id
";
       if($pd['inbound_id'] != 0) $msg .= "⚡تغییر ظرفیت:$acount /chnglimitsrv$id";
       $keyboard = [[['text' => "↪ برگشت", 'callback_data' =>"plalllan#$srvid"],]];
       $aa = bot('editmessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'parse_mode' => "HTML",
            'text' => $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
        }
    
}
if(preg_match('/copypl/',$text) and ($userid==ADMIN or isAdmin() )){
	$sid = str_replace('/copypl', '', $text);
    $srvs = $telegram->db->query("SELECT * FROM `server_info` WHERE id != $sid")->fetchAll(2);
    if(empty($srvs)) {
        $telegram->sendMessage($userid,'سرور دیگری برای کپی ندارید. لطفا ابتدا یک سرور بسازید و بعد پلن ها رو کپی کنید');
        exit;
    }
	
    file_put_contents("state/$userid.txt",$text);
    $srvkey = [];
    foreach($srvs as $srv){
        $id = $srv['id'];
        $panel_url = str_ireplace('http://','',str_ireplace('https://','',$srv['panel_url']));
        $srvkey[] = ['text' => $panel_url, 'callback_data' => "copypl#$id"];  
    } 
    $srvkey = array_chunk($srvkey,1);
    bot('sendmessage', [
        'chat_id' => $userid,
        'text' => "برای کپی پلن, سرور را انتخاب کنید :",
        'reply_markup' => json_encode(['inline_keyboard' => $srvkey])
    ]);
}
if(preg_match('/copypl/',$cdata) and preg_match('/copypl/',$state)){
    $fid = str_ireplace("/copypl",'', $state); 
    $sid=str_ireplace('copypl#','',$cdata);
    $file = $telegram->db->query("select * from fl_file where id=$fid")->fetch(2);
    $fileid = $file['fileid'];
    $catid = $file['catid'];
    $server_id = $file['server_id'];
    $inbound_id = $file['inbound_id'];
    $acount = $file['acount'];
    $limitip = $file['limitip'];
    $title = $file['title'];
    $protocol = $file['protocol'];
    $days = $file['days'];
    $volume = $file['volume'];
    $type = $file['type'];
    $price = $file['price'];
    $descr = $file['descr'];
    $pic = $file['pic'];
    $active = $file['active'];
    $step = $file['step'];
    $sendcount = $file['sendcount'];
    $isvip = $file['isvip'];
    $telegram->db->query("INSERT INTO `fl_file` VALUES (NULL, '$fileid', $catid,$sid,$inbound_id,$acount, $limitip, '$title', '$protocol', $days, $volume, '$type', $price, '$descr', '$pic',$active,$step, '$time','$sendcount','$isvip');");
	bot('editmessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text' => "✅پلن با سرور انتخابی ایجاد شد",
    ]);
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/getlistpd/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/getlistpd','',$text);
    $res = $telegram->db->query("select * from fl_order where status=1 and fileid=$fid order by id DESC limit 10")->fetchAll(2);
    if(empty($res)){
        $telegram->sendMessage($userid,'لیست خالی است');
        exit;
    }
    $txt = '';
    foreach ($res as $order){
		$suid = $order['userid'];
		$ures = $telegram->db->query("select * from fl_user where userid=$suid")->fetch(2);
        $date = $order['date'];
        $remark = $order['remark'];
        $date = jdate('Y-m-d H:i', $date);
        $uname = $ures['name'];
        $sold = "🔻".$uname. " ($date)";
        $accid = $order['id'];
        $txt = "$sold \n  $remark <code>".$order['link']."</code> \n  =========== \n";
        //$txt = $acc['text']." \n $sold | ❌ /delacc$accid \n =========== \n";
        bot('sendmessage', [
            'chat_id' => $userid,
            'parse_mode' => "HTML",
            'text' => $txt,
        ]); 
    }
    //$telegram->sendMessage($userid,$txt);
}
if(preg_match('/delacc/',$text) and ($userid==ADMIN or isAdmin() )){
    $aid=str_ireplace('/delacc','',$text);
    $telegram->db->query("delete from fl_accounts where id={$aid}");
    $telegram->sendMessage($userid,"اکانت موردنظر با موفقیت حذف شد");
}
if(preg_match('/addpd/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"اکانت ها  را بصورت زیر ارسال کنید
دقت کنید که تمامی اطلاعات اکانت را با عبارت seprator از هم جدا کنید 

username: Test password: pwd...

seprator

username: Test
password: pwd

",$cancelop);exit;
}
if(preg_match('/addpd/',$state)){
    $pid=str_ireplace('/addpd','',$state);
    if(preg_match('/seprator/',strtolower($text))){
        $accs = explode('seprator',$text);
        foreach ($accs as $acc){
            if(strlen($acc) > 5)
                $telegram->db->query("INSERT INTO `fl_accounts` (`id`, `fid`, `text`, `sold`, `active`) VALUES (NULL, $pid, '$acc', '0', '1');");
        }
        $telegram->sendMessageCURL($userid,"✅اکانت های جدید با موفقیت اضافه شد",$finalop);
        file_put_contents('state/'.$userid.'.txt','');
    }else{
        $msg = '‼️ لطفا اکانت ها را با جداکننده معتبر ارسال کنید';
        $telegram->sendMessageCURL($userid,$msg,$cancelop);
    }
}

if(preg_match('/delpd/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/delpd','',$text);
    $telegram->db->query("delete from fl_file where id={$fid}");
    $telegram->sendMessage($userid,"پلن موردنظر با موفقیت حذف شد");
}
if(preg_match('/chpnm/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"نام جدید پلن را وارد کنید:",$cancelop);exit;
}
if(preg_match('/chpnm/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chpnm','',$state);
    $telegram->db->query("update fl_file set title='$text' where id={$pid}");
    $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/chnglimitsrv/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"ظرفیت جدید پلن را بصورت عدد لاتین وارد کنید:",$cancelop);exit;
}
if(preg_match('/chnglimitsrv/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chnglimitsrv','',$state);
	if(is_numeric($text)){
        $telegram->db->query("update fl_file set acount='$text' where id={$pid}");
		$telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
		file_put_contents("state/$userid.txt",'');
    }else{
        $telegram->sendMessage($userid,"یک مقدار عددی و صحیح وارد کنید");
    }
}
if(preg_match('/desc/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"توضیحات جدید را وارد کنید:",$cancelop);exit;
}
if(preg_match('/desc/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/desc','',$state);
    $telegram->db->query("update fl_file set descr='$text' where id={$pid}");
    $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/chpp/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"قیمت جدید را وارد کنید:",$cancelop);exit;
}
if(preg_match('/chpp/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chpp','',$state);
    if(is_numeric($text)){
        $telegram->db->query("update fl_file set price='$text' where id={$pid}");
        $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
        file_put_contents("state/$userid.txt",'');
    }else{
        $telegram->sendMessage($userid,"یک مقدار عددی و صحیح وارد کنید");
    }
}
if($text=='🧑‍💻سرویس های من' or $cdata == 'backto' or preg_match('/ordpaginate/',$cdata) or $text =='/services'){
    $results_per_page = 50;  
    $number_of_result = $telegram->db->query("SELECT * FROM `fl_order` WHERE userid=$userid and status=1")->rowCount();  
    $number_of_page = ceil ($number_of_result / $results_per_page);
    $page = (preg_match('/ordpaginate/',$cdata)) ? str_replace('ordpaginate#','',$cdata) : 1;
    $page_first_result = ($page-1) * $results_per_page;  
    
    $orders = $telegram->db->query("SELECT * FROM `fl_order` WHERE userid=$userid and status=1 order by id DESC limit $page_first_result, $results_per_page")->fetchAll(2);
    if(empty($orders)){
        $telegram->sendMessage($userid, 'لیست سفارش ها خالی است. لطفا یک پلن جدید خریداری کنید.');
        exit;
    }
    $keyboard = [];
    foreach($orders as $cat){
        $id = $cat['id'];
        $remark = $cat['remark'];
        $server_id = $cat['server_id'];
        $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
        $keyboard[] = ['text' => "$remark", 'callback_data' => $panel_type == 'marzban' ? "ordMRZtail#$id" : "ordetail#$id"];
    }
    $keyboard = array_chunk($keyboard,2);
    
    /* Setup page vars for display. */
    $prev = $page - 1;      //previous page is page - 1
    $next = $page + 1;      //next page is page + 1
    $lastpage = ceil($number_of_page/$results_per_page);      //lastpage is = total pages / items per page, rounded up.
    $lpm1 = $lastpage - 1;                      //last page minus 1
    //$telegram->sendMessage($userid,"prev $prev next $next lastpage $lastpage page_first_result $page_first_result page $page number_of_page $number_of_page number_of_result $number_of_result");
    
    $buttons = [];
    //previous button
    if ($prev > 0) $buttons[] = ['text' => "◀", 'callback_data' => "ordpaginate#$prev"];

    //next button
    if ($next > 0 and $page != $number_of_page) $buttons[] = ['text' => "➡", 'callback_data' => "ordpaginate#$next"];   
    $keyboard[] = $buttons;
    
	$keyboard[] = [['text' => "🔎جستجو", 'callback_data' => "srchusrrmrk"]];
    $msg = ' 📍 برای دیدن مشخصات سرویس روی آن بزنید👇';
    
    if(isset($cdata)) {
        bot('editMessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else { $telegram->sendAction($userid, 'typing');
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
    exit;
}
if($cdata == 'srchusrrmrk') {
    file_put_contents('state/' . $userid . '.txt', 'srchusrrmrk');
    $telegram->sendMessageCURL($userid, "⏪ ریمارک را ارسال کنید ",$cancelop);exit;
}
if($state == 'srchusrrmrk' and $text != '❌ انصراف'){
    $result = $telegram->db->query("select * from fl_order where remark LIKE '%$text%' and status=1 and userid='$userid'")->fetch();
    if(empty($result)){
        $telegram->sendMessage($userid,"موردی یافت نشد");exit;
    }else{
        $id = $result['id'];
        $remark = $result['remark'];
        $uid = $result['userid'];
        $server_id = $result['server_id'];
        $telegram->sendMessageCURL($userid, "سفارش $remark یافت شد :",$finalop);
        
        $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
        $keyboard = [[['text' => "$remark", 'callback_data' => $panel_type == 'marzban' ? "ordMRZtail#$id" : "ordetail#$id"]]];
        $msg = ' 📍 برای دیدن مشخصات سرویس روی آن بزنید👇';
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
        file_put_contents('state/' . $userid . '.txt', '');
        exit;
    }
}
if(preg_match('/switchserv/', $cdata)){
	if($gateways['change_location'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر تغییر لوکیشن غیرفعال است و بزودی فعال می کنیم',
            'show_alert' => true
        ]);
        exit;
    }
    $input = explode('#',$cdata);
    $order_id = $input[1];
    $server_id = $input[2];
    $leftgp = $input[3];
    $expire = $input[4]; 
    /*if($expire < time() or $leftgp <= 0) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "سرویس شما غیرفعال است.لطفا ابتدا آن را تمدید کنید",
            'show_alert' => true
        ]);exit;
    }*/
	
	$srvip = $telegram->db->query("select * from server_info WHERE id = $server_id")->fetch(2); 
    if($srvip['vip'] == '2'){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻امکان تغییر لوکیشن برای این سرور وجود ندارد',
            'show_alert' => true
        ]);
        exit;
    }
	
    $respd = $telegram->db->query("select * from fl_server WHERE active=1 and ucount > 0 and id != $server_id")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'در حال حاضر هیچ سرور فعالی برای تغییر لوکیشن وجود ندارد',
            'show_alert' => true
        ]);exit;
    }
    $keyboard = [];
    foreach($respd as $cat){
        $sid = $cat['id'];
        $name = $cat['title'].$cat['flag'];
        $isvip = $telegram->db->query("select * from server_info where id = $sid")->fetch(2);
        if($srvip['vip'] == $isvip['vip'] or $isvip['vip'] == 0) $keyboard[] = ['text' => "$name", 'callback_data' => "chngsrrv#$sid#$order_id"];
    }
    $keyboard = array_chunk($keyboard,2);
    $keyboard[] = [['text' => '🔙 بازگشت', 'callback_data' => "ordetail#$order_id"]];
    bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
        'text'=> ' 📍 لطفا برای تغییر لوکیشن سرویس فعلی, یکی از سرورها را انتخاب کنید👇',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);

}
if(preg_match('/chngsrrv/',$cdata)){
	/*bot('answercallbackquery', [
		'callback_query_id' => $cid,
		'text' => '🔻فعلا امکان تغییر لوکیشن نیست',
		'show_alert' => true
	]);
	exit;*/
    $input = explode('#',$cdata);
    $sid = $input[1];
    $oid = $input[2];
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $inbound_id = $order['inbound_id'];
    $server_id = $order['server_id'];
    $fileid = $order['fileid'];
	
	$server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$sid")->fetch(2);
	if($inbound_id == 0){
	    if($server_info['ucount'] == 0) {
    		bot('answercallbackquery', [
    			'callback_query_id' => $cid,
    			'text' => 'ظرفیت این سرور پر شده است',
    			'show_alert' => true
    		]);
    		exit;
    	}
	}/*elseif($inbound_id > 0){
	    $file_detail = $telegram->db->query("select SUM(acount) as acount from fl_file WHERE server_id=$sid and inbound_id = $inbound_id and active=1")->fetch(2);
	    if($file_detail){ //$telegram->sendMessage($userid, json_encode($file_detail['acount']));die;
	        if($file_detail['acount'] == 0) {
        		bot('answercallbackquery', [
        			'callback_query_id' => $cid,
        			'text' => 'ظرفیت کانکشن مورد نظر در این سرور پر شده است',
        			'show_alert' => true
        		]);
        		exit;
        	}
	    }
	}*/
	
    $remark = $order['remark'];
    $protocol = $order['protocol'];
	$link = $order['link'];
    if($protocol == 'vmess'){ 
        $link_info = json_decode(base64_decode(str_replace('vmess://','',$link)));
        $uniqid = $link_info->id;
        $port = $link_info->port;
        $netType = $link_info->net;
    }else{
        $link_info = parse_url($link);
        $panel_ip = $link_info['host'];
        $uniqid = $link_info['user'];
        //$remark = $link_info['fragment'];
        $protocol = $link_info['scheme'];
        $port = $link_info['port'];
        $netType = explode('type=',$link_info['query'])[1]; 
        $netType = explode('&',$netType)[0];
    }
    
    require_once('vray.php');
    if($inbound_id > 0) {
        $remove_response = remove_client($server_id, $inbound_id, $remark);
		if(is_null($remove_response)){
			bot('answercallbackquery', [
				'callback_query_id' => $cid,
				'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
				'show_alert' => true
			]);
			exit;
		}
        if($remove_response){
            $total = $remove_response['total'];
            $up = $remove_response['up'];
            $down = $remove_response['down'];
			if(is_null($total) or is_null($remove_response['expiryTime'])){
                bot('answercallbackquery', [
    				'callback_query_id' => $cid,
    				'text' => '🔻امکان دریافت اطلاعات سرویس از سرور مبدا نیست. لطفا به مدیریت اطلاع بدید',
    				'show_alert' => true
    			]);
    			exit;
            }
			
			$savedinfo = file_get_contents('savedinfo.txt');
            $savedinfo = explode('-',$savedinfo);
            $port = $savedinfo[0] + 1;
            $last_num = $savedinfo[1] + 1;
        
            $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$sid")->fetch(2)['remark'];
			$uremark = $telegram->db->query("select * from fl_remark where userid=$userid")->fetch(2);
			if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
			$new_remark = "{$uremark}-{$last_num}-".rand(0,9999);
			
			$leftgb = $total - $up - $down;
			//if($server_id == 62 and $sid == 61) $leftgb = $leftgb * $to_multiplus;
			//elseif($server_id == 61 and $sid == 62)  $leftgb = $leftgb * $to_multi;
			
			if(in_array($server_id, $multi_srvs) and in_array($sid, $multiplus_srvs))  $leftgb = $leftgb * $to_multiplus;
             elseif(in_array($server_id, $multiplus_srvs) and in_array($sid, $multi_srvs))  $leftgb = $leftgb * $to_multi;
			
			$id_label = $protocol == 'trojan' ? 'password' : 'id';
			$uniqid = is_null($uniqid) ? (is_null($remove_response['id']) ? generateRandomString(42,$protocol) : $remove_response['id'] ) : $uniqid; 
			$newArr = [
				"$id_label" => $uniqid,
				"flow" => $remove_response['flow'],
				"email" => $new_remark,
				"limitIp" => intval($remove_response['limitIp']),
				"totalGB" => $leftgb,
				"expiryTime" => $remove_response['expiryTime'],
				"enable" => true, 
				"tgId" => "",  
				"subId" => rand(0,99999999999999999)
			];
            
			/*$response = getList($sid);
            if($inbound_id > 0 & !is_null($response)) { 
                foreach($response->obj as $row){ 
                    if($row->id == $inbound_id) {  
                        $des_procotol = $row->protocol;
                        $des_netType = json_decode($row->streamSettings)->network;
                        break;
                    }
                }
            }
            if(!is_null($des_procotol)) {
                if($des_procotol != $protocol || $des_netType != $netType){
                    bot('answercallbackquery', [
                        'callback_query_id' => $cid,
                        'text' => "🔻اینباند شماره $inbound_id در سرور مقصد پروتکل/نوع شبکه متفاوتی از سرور مبدا دارد. لطفا به مدیریت اطلاع بدید",
                        'show_alert' => true
                    ]);
                    exit;
                }
            }*/
			
            $response = add_inbount_client($sid, '', $inbound_id, 1, $new_remark, 0, 1, $newArr); 
            if(is_null($response)){
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                    'show_alert' => true
                ]);
                exit;
            }
			if($response == "inbound not Found"){
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => "🔻سطر (inbound) با آیدی $inbound_id در این سرور یافت نشد و یا کوکی منقضی شده. لطفا به مدیریت اطلاع بدید",
                    'show_alert' => true
                ]);
                exit;
            }
			if(!$response->success){
				bot('answercallbackquery', [
					'callback_query_id' => $cid,
					'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
					'show_alert' => true
				]);$telegram->sendMessage(ADMIN,"changeLoc > 0 = serverID: $sid :".$response->msg);
				exit;
			}
			$vray_link = genLink($sid, $uniqid, $protocol, $new_remark, $port, $netType, $inbound_id);
			remove_client($server_id, $inbound_id, $remark, 1);
			$telegram->db->query("UPDATE fl_order set remark='$new_remark' where id=$oid");
			//$telegram->db->query("UPDATE `fl_file` SET `acount` = acount + 1 WHERE server_id=$server_id and inbound_id = $inbound_id ");
			//$telegram->db->query("UPDATE `fl_file` SET `acount` = acount - 1 WHERE server_id=$sid and inbound_id = $inbound_id ");
        }
    }else{
        $response = remove_inbound($server_id, $remark);
		if(is_null($response)){
			bot('answercallbackquery', [
				'callback_query_id' => $cid,
				'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
				'show_alert' => true
			]);
			exit;
		}
        if($response){
			if(is_null($response['volume']) or is_null($response['expiryTime'])){
                bot('answercallbackquery', [
    				'callback_query_id' => $cid,
    				'text' => '🔻امکان دریافت اطلاعات سرویس از سرور مبدا نیست. لطفا به مدیریت اطلاع بدید',
    				'show_alert' => true
    			]);
    			exit;
            }
            $savedinfo = explode('-',file_get_contents('savedinfo.txt'));
            $port = $savedinfo[0] + 1;
            $last_num = $savedinfo[1] + 1;
			
			$srv_remark = $telegram->db->query("select * from fl_server WHERE id=$sid")->fetch(2)['remark'];
			$uremark = $telegram->db->query("select * from fl_remark where userid=$userid")->fetch(2);
			if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
			$new_remark = "{$uremark}-{$last_num}-".rand(0,9999);
            
            $leftgb = $response['volume'] / 1073741824;
            //if($server_id == 62 and $sid == 61) $leftgb = $leftgb * $to_multiplus;
			//elseif($server_id == 61 and $sid == 62)  $leftgb = $leftgb * $to_multi;
			
			if(in_array($server_id, $multi_srvs) and in_array($sid, $multiplus_srvs))  $leftgb = $leftgb * $to_multiplus;
             elseif(in_array($server_id, $multiplus_srvs) and in_array($sid, $multi_srvs))  $leftgb = $leftgb * $to_multi;
            
            $add_response = add_inbound($sid, $response['uniqid'], $response['protocol'], $port, $response['expiryTime'], $new_remark, $leftgb, $response['netType'], $response['security']);
			
			if(is_null($add_response)){
                bot('answercallbackquery', [
                    'callback_query_id' => $cid,
                    'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                    'show_alert' => true
                ]);
                exit;
            }
			if(!$add_response->success){
				bot('answercallbackquery', [
					'callback_query_id' => $cid,
					'text' => '🔻خطا در ساخت کانفیگ در سرور مقصد. لطفا به مدیریت اطلاع بدید',
					'show_alert' => true
				]);
				$telegram->sendMessage(ADMIN,"changeLoc 0 = serverID: $sid :".$add_response->msg);
				exit;
			}
			if(is_null($response['uniqid'])) {
			    bot('answercallbackquery', [
					'callback_query_id' => $cid,
					'text' => '🔻خطا در تغییر لوکیشن. لطفا بعدا مجدد تلاش کنید',
					'show_alert' => true
				]);
				exit;
			}
			$vray_link = genLink($sid, $response['uniqid'], $response['protocol'], $new_remark, $port, $response['netType'], $inbound_id);
            remove_inbound($server_id, $remark, 1);
            file_put_contents('savedinfo.txt',$port.'-'.$last_num);
        }
    }
    $telegram->db->query("UPDATE fl_order set server_id=$sid,link='$vray_link', remark='$new_remark' where id=$oid");
	$telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount + 1 WHERE id=$server_id");
	$telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$sid");
    
    $server_title = $telegram->db->query("select * from fl_server where id=$sid")->fetch(2)['title'];
	bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "✅سرویس مورد نظر با موفقیت به لوکیشن $server_title انتقال یافت",
        'show_alert' => true
    ]);
    /*$orders = $telegram->db->query("SELECT * FROM `fl_order` WHERE userid=$userid and status=1 order by id DESC")->fetchAll(2);
    $keyboard = [];
    foreach($orders as $cat){
        $id = $cat['id'];
        $cremark = $cat['remark'];
        $keyboard[] = ['text' => "$cremark", 'callback_data' => "ordetail#$id"];
    }
    $keyboard = array_chunk($keyboard,2);
    
    $msg = " 📍لوکیشن سرویس $remark به $server_title تغییر یافت.\n لطفا برای مشاهده مشخصات, روی آن بزنید👇";
    
    bot('editMessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text'=> $msg,
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);*/
	
	$cdata = "ordetail#$oid";
}
if(preg_match('/unewlink/',$cdata)){
    $input = explode('#',$cdata);
    $oid = $input[1];
	
	bot('answercallbackquery', [
		'callback_query_id' => $cid,
		'text' => "لینک کانفیگ بروز شد",
		'show_alert' => false
	]);
	$order = $telegram->db->query("SELECT * FROM `fl_order` WHERE userid=$userid and id=$oid")->fetch(2);
	$remark = $order['remark'];
	$protocol = $order['protocol'];
	$server_id = $order['server_id'];
	$inbound_id = $order['inbound_id']; 
	$link = $order['link'];
	if(preg_match('/vmess/',$link)){
		$link_info = json_decode(base64_decode(str_replace('vmess://','',$link)));
		$uniqid = $link_info->id;
		$panel_url = $link_info->add;
		$port = $link_info->port;
		$netType = $link_info->net;
	}else{
		$link = urldecode($link);
		$link_info = parse_url($link);
		$panel_ip = $link_info['host'];
		$uniqid = $link_info['user'];
		$port = $link_info['port'];
		$netType = explode('type=',$link_info['query'])[1];
		$netType = explode('&',$netType)[0];
	}
	include_once('vray.php');
	$vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
	$telegram->db->query("UPDATE fl_order set link='$vray_link' WHERE id='$oid'");

	$cdata = "ordetail#$oid";
}
if(preg_match('/chngnetType/', $cdata)){
	if($gateways['change_nettype'] != 1){
        bot('answercallbackquery', [
    		'callback_query_id' => $cid,
    		'text' => '🔻فعلا امکان تغییر نوع شبکه نیست',
    		'show_alert' => true
    	]);
    	exit;
    }
    $input = explode('#',$cdata);
    $fid = $input[1];
    $oid = $input[2];
    
	$respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2); 
	if($respd){
		$cadres = $telegram->db->query("select * from fl_cat WHERE id=".$respd['catid'])->fetch(2);
		if($cadres) {
			$catname = $cadres['title'];
			$name = $catname." ".$respd['title'];
		}else $name = "#$id";
	}else $name = "#$id";

    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$oid")->fetch(2);
    $date = jdate("Y-m-d H:i",$order['date']);
    $expire_date = jdate(" H:i d-m-Y",$order['expire_date']);
    $remark = $order['remark'];
    $acc_link = $order['link'];
    $protocol = $order['protocol'];
    $server_id = $order['server_id'];

    include_once('vray.php');
    $response = getList($server_id)->obj;
    foreach($response as $row){
        if($row->remark == $remark) {
            $total = $row->total;
            $up = $row->up;
            $down = $row->down;
            $port = $row->port;
            $uniqid = ($protocol == 'trojan') ? json_decode($row->settings)->clients[0]->password : json_decode($row->settings)->clients[0]->id;
            $netType = json_decode($row->streamSettings)->network; 
            $netType = ($netType == 'tcp') ? 'ws' : 'tcp';
            //$telegram->sendMessage($userid, "ne $netType");
        break;
        }
    }

    if($protocol == 'trojan') $netType = 'tcp';
    //$telegram->sendMessage($cuserid,"$protocol - $total - $port $uniqid $remark");
    $leftgb = round( ($total - $up - $down) / 1073741824, 2) . " GB";

    $update_response = update_inbound($server_id, $uniqid, $remark, $protocol, $netType); //$telegram->sendMessage($cuserid,$update_response->msg);
    $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType);
    // update order
    $telegram->db->query("UPDATE fl_order set protocol='$protocol',link='$vray_link' WHERE id='$oid'");
	$cdata = "ordetail#$oid";

}

if(preg_match('/chngprotocol/', $cdata)){
	if($gateways['change_protocol'] != 1){
        bot('answercallbackquery', [
    		'callback_query_id' => $cid,
    		'text' => '🔻فعلا امکان تغییر پروتکل نیست',
    		'show_alert' => true
    	]);
    	exit;
    }
    $input = explode('#',$cdata);
    $fid = $input[1];
    $oid = $input[2];
    $protocol = $input[3];
    
	$respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2); 
	if($respd){
		$cadres = $telegram->db->query("select * from fl_cat WHERE id=".$respd['catid'])->fetch(2);
		if($cadres) {
			$catname = $cadres['title'];
			$name = $catname." ".$respd['title'];
		}else $name = "#$id";
	}else $name = "#$id";

    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$oid")->fetch(2);
    $date = jdate("Y-m-d H:i",$order['date']);
    $expire_date = jdate(" H:i d-m-Y",$order['expire_date']);
    $remark = $order['remark'];
    $acc_link = $order['link'];
    $server_id = $order['server_id'];

    include_once('vray.php');
    $response = getList($server_id)->obj;
    foreach($response as $row){
        if($row->remark == $remark) {
            $total = $row->total;
            $up = $row->up;
            $down = $row->down;
            $port = $row->port;
            //$uniqid = ($protocol == 'trojan') ? json_decode($row->settings)->clients[0]->password : json_decode($row->settings)->clients[0]->id;
            $netType = json_decode($row->streamSettings)->network;
            break;
        }
    }
    if($protocol == 'trojan') $netType = 'tcp';
    $uniqid = generateRandomString(42,$protocol); 
    //$telegram->sendMessage($cuserid,"$protocol - $total - $port $uniqid $remark");
    $leftgb = round( ($total - $up - $down) / 1073741824, 2) . " GB";

    $update_response = update_inbound($server_id, $uniqid, $remark, $protocol, $netType, $security); //$telegram->sendMessage($cuserid,$update_response->msg);
    $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType);

    // update order
    $telegram->db->query("UPDATE fl_order set protocol='$protocol',link='$vray_link' WHERE id='$oid'");
	$cdata = "ordetail#$oid";

}
if(preg_match('/unrqewlink/', $cdata)){
    $id = str_replace('unrqewlink#','', $cdata);
    $keyboard = [[['text' => "☑انصراف", 'callback_data' => "ordetail#$id"],['text' => "✅تایید", 'callback_data' => "sunewuidlink#$id"]]];
            
    bot('editmessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => '⚠️بعد از انتخاب گزینه تایید, لینک شما بطور کامل قطع و جایگزین لینک جدید می شود',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]); 
}

if(preg_match('/sunewuidlink/',$cdata)){
    $input = explode('#',$cdata);
    $oid = $input[1];
	
	bot('answercallbackquery', [
		'callback_query_id' => $cid,
		'text' => "لینک کانفیگ تغییر کرد",
		'show_alert' => false
	]);
	
	$order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$oid")->fetch(2);
    $remark = $order['remark'];
    $server_id = $order['server_id'];
    $inbound_id = $order['inbound_id'];
    $protocol = $order['protocol'];
    $link = $order['link'];
	if(preg_match('/vmess/',$link)){
		$link_info = json_decode(base64_decode(str_replace('vmess://','',$link)));
		$port = $link_info->port;
		$netType = $link_info->net;
	}else{
		$link = urldecode($link);
		$link_info = parse_url($link);
		$uniqid = $link_info['user'];
		$port = $link_info['port'];
		$netType = explode('type=',$link_info['query'])[1];
		$netType = explode('&',$netType)[0];
	}

    require_once('vray.php');
    $uniqid = generateRandomString(42,$protocol); 
    
    if($inbound_id > 0)
        $update_response = update_client_uuid($server_id, $inbound_id, $remark, $uniqid);
    else
        $update_response = update_inbound_uuid($server_id, $inbound_id, $remark, $uniqid);
        
    if(is_null($update_response)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }
	if(!$update_response->success){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در بروزرسانی کانفیگ. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }
    
    $vray_link = genLink($server_id, $uniqid, $protocol, $remark, $port, $netType, $inbound_id);
	$telegram->db->query("UPDATE fl_order set link='$vray_link' WHERE id='$oid'");

	$cdata = "ordetail#$oid";
}
if(preg_match('/ordetail/', $cdata)){
    $id = str_replace('ordetail#','', $cdata);
    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE userid=$userid and id=$id")->fetch(2);
    if(empty($order)){
        $telegram->sendMessage($userid,"موردی یافت نشد");exit;
    }else {
        $fid = $order['fileid']; 
    	$respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2); 
    	if($respd){
    	    $cadres = $telegram->db->query("select * from fl_cat WHERE id=".$respd['catid'])->fetch(2);
    	    if($cadres) {
    	        $catname = $cadres['title'];
        	    $name = $catname." ".$respd['title'];
    	    }else $name = "#$id";
    	}else $name = "#$id";
    	
    	//$acc_id = $order['acc_id'];
    	//$acc_text = $telegram->db->query("select * from fl_accounts WHERE id=$acc_id")->fetch(2)['text'];
    	
        //$amount = number_format($order['amount'])." Toman";
        $date = jdate("Y-m-d H:i",$order['date']);
        $remark = $order['remark'];
        $acc_link = $order['link'];
        $protocol = $order['protocol'];
        $server_id = $order['server_id'];
        $inbound_id = $order['inbound_id'];
    
        include_once('vray.php');
        $response = getList($server_id)->obj;
        if($inbound_id == 0) {
            foreach($response as $row){
                if($row->remark == $remark) {
                    $total = $row->total;
                    $up = $row->up;
                    $down = $row->down; 
                    $netType = json_decode($row->streamSettings)->network;
					$expire_date = substr_replace($row->expiryTime, "", -3);
                    break;
                }
            }
        }else {
            foreach($response as $row){
                if($row->id == $inbound_id) {
                    $netType = json_decode($row->streamSettings)->network;
                    $clients = is_null($row->clientStats) ? $row->clientInfo : $row->clientStats;
                    $settings = json_decode($row->settings, true);
                    foreach($clients as $client) {
                        if($client->email == $remark) { 
							$up = $client->up;
                            $down = $client->down; 
                            $expire_date = substr_replace($client->expiryTime, "", -3);
                            $clients = $settings['clients'];
                            foreach($clients as $key => $client) {
                                if($client['email'] == $remark) {
                                    $total = $settings['clients'][$key]['totalGB'];
                                }
                            }
                            if($total == 0 || is_null($total)) $total = $client->total;
                            break;
                        }
                    }
					if(is_null($total)){
                        $clients = $settings['clients'];
                            foreach($clients as $key => $client) {
                                if($client['email'] == $remark) {
                                    $total = $settings['clients'][$key]['totalGB'];
                                }
                            }
                    }
                    break;
                }
            }
        }
        if(strlen($expire_date) > 0 and !is_null($expire_date)) $telegram->db->query("update fl_order set expire_date='$expire_date' where id=$id"); 
        $link_status = (($expire_date < time() and $expire_date !=0)  or $total - $up - $down < 0 ) ? 'غیرفعال' : 'فعال';
        $expire_date = $expire_date == 0 ? 'نامحدود' : jdate(" H:i d-m-Y",$expire_date);
        $leftgb = round( ($total - $up - $down) / 1073741824, 2) . " GB";
		
		if(is_null($total)){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "⚠️اطلاعات سرویس یافت نشد. احتمالا مشکل از ارتباط با سرور یا خطای مشابه است. لطفا تمدید, افزایش حجم و زمان, تغییر لوکیشن یا پروتکل را نزنید و به مدیریت اطلاع بدید تا وضعیت کانفیگ را در پنل چک کنند🙏",
                'show_alert' => true
            ]);
            $leftgb = $link_status = $netType = '⚠️';
        }
		
		$sres = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2);
        $stitle = $sres['title'];
        $flag = $sres['flag'];
        $msg = "✅ $name \n🌐 $stitle $flag \n📝 $date \n🌟 $link_status \n🔗 $remark \n <code>$acc_link</code>";

if($inbound_id == 0){
    if($protocol == 'trojan') {
        $keyboard = [
            [['text' => "🔄 تغییر لینک و قطع دسترسی دیگران", 'callback_data' => "unrqewlink#$id"]],
			[
                ['text' => "🧩 کیو آر کد", 'callback_data' => "qrcode#$id"],
				['text' => "⚡️ بروزرسانی لینک", 'callback_data' => "unewlink#$id"],
            ],
            [
                ['text' => " $leftgb حجم باقیمانده", 'callback_data' => "leftgb#$total#$up#$down"],
                ['text' => $netType. " نوع شبکه ", 'callback_data' => "trjntchange"],
            ],
            [
                ['text' => " انقضا ⏰ ". $expire_date, 'callback_data' => "n2othin43g"],
            ],
            [
                ['text' => "👇پروتکل📡", 'callback_data' => "not64hin3g"],
            ],
            [
                ['text' => $protocol == 'trojan' ? '✅trojan' : 'trojan', 'callback_data' => "chngprotocol#$fid#$id#trojan"],
                ['text' => $protocol == 'vmess' ? '✅vmess' : 'vmess', 'callback_data' => "chngprotocol#$fid#$id#vmess"],
                ['text' => $protocol == 'vless' ? '✅vless' : 'vless', 'callback_data' => "chngprotocol#$fid#$id#vless"],
            ],
            [
                ['text' => '♻ تمدید سرویس', 'callback_data' => "renewacc#$id" ],
                ['text' => '🔌تغییر لوکیشن', 'callback_data' => "switchserv#$id#$server_id#$leftgb#".$order['expire_date'] ],
            ],
            
        ];
    }else {
        $keyboard = [
            [['text' => "🔄 تغییر لینک و قطع دسترسی دیگران", 'callback_data' => "unrqewlink#$id"]],
			[
                ['text' => "🧩 کیو آر کد", 'callback_data' => "qrcode#$id"],
				['text' => "⚡️ بروزرسانی لینک", 'callback_data' => "unewlink#$id"],
            ],
            [
                
                ['text' => " $leftgb حجم باقیمانده", 'callback_data' => "leftgb#$total#$up#$down"],
                ['text' => $netType. " نوع شبکه ", 'callback_data' => "chngnetType#$fid#$id"],
            ],
            [
                ['text' => " انقضا ⏰ ". $expire_date, 'callback_data' => "not567856hing"],
            ],
            [
                ['text' => " پروتکل📡", 'callback_data' => "not2312hing"],
            ],
            [
                ['text' => $protocol == 'trojan' ? '✅trojan' : 'trojan', 'callback_data' => "chngprotocol#$fid#$id#trojan"],
                ['text' => $protocol == 'vmess' ? '✅vmess' : 'vmess', 'callback_data' => "chngprotocol#$fid#$id#vmess"],
                ['text' => $protocol == 'vless' ? '✅vless' : 'vless', 'callback_data' => "chngprotocol#$fid#$id#vless"],
            ],
            [
                ['text' => '♻ تمدید سرویس', 'callback_data' => "renewacc#$id" ],
                ['text' => '🔌تغییر لوکیشن', 'callback_data' => "switchserv#$id#$server_id#$leftgb#".$order['expire_date'] ],
            ],
            
        ];
    }
}else{ // inbound
    $keyboard = [
        [['text' => "🔄 تغییر لینک و قطع دسترسی دیگران", 'callback_data' => "unrqewlink#$id"]],
		[
			['text' => "🧩 کیو آر کد", 'callback_data' => "qrcode#$id"],
			['text' => "⚡️ بروزرسانی لینک", 'callback_data' => "unewlink#$id"],
		],
        [
            
            ['text' => " $leftgb حجم باقیمانده", 'callback_data' => "leftgb#$total#$up#$down"],
            ['text' => $netType. " نوع شبکه ", 'callback_data' => "4nothi5ng"],
        ],
        [
            ['text' => " انقضا ⏰ ". $expire_date, 'callback_data' => "n4oth4ing"],
        ],
        [
            ['text' => " $protocol پروتکل📡", 'callback_data' => "nroth6ing"],
        ],
		[
			['text' => '♻ تمدید سرویس', 'callback_data' => "renewacc#$id" ],
			['text' => '🔌تغییر لوکیشن', 'callback_data' => "switchserv#$id#$server_id#$leftgb#".$order['expire_date'] ],
		],
    ];
}

if($protocol == 'wireguard'){
    $keyboard = [
		[['text' => "🧩 کیو آر کد", 'callback_data' => "qrcode#$id"]],
        [['text' => " $leftgb حجم باقیمانده", 'callback_data' => "leftgb#$total#$up#$down"]],
        [['text' => " انقضا ⏰ ". $expire_date, 'callback_data' => "n2othin43g"]],
        [['text' => " $protocol پروتکل📡", 'callback_data' => "nroth6ing"]],
        [
            ['text' => '♻ تمدید سرویس', 'callback_data' => "renewacc#$id" ],
            //['text' => '🔌تغییر لوکیشن', 'callback_data' => "switchserv#$id#$server_id#$leftgb#".$order['expire_date'] ],
        ],
        
    ]; 
}
        $server_info = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2);
        $extrakey = [];
        if($gateways['buy_gb'] == 1) $extrakey[] = ['text' => "📥افزایش حجم سرویس", 'callback_data' => "upmysrvice#$server_id#$inbound_id#$remark"];
        if($gateways['buy_day'] == 1) $extrakey[] = ['text' => "افزایش زمان سرویس✨", 'callback_data' => "relinsrvc#$server_id#$inbound_id#$remark"];
        if($order['amount'] != 0 ) $keyboard[] = $extrakey;
        $keyboard[] = [['text' => "↪ برگشت", 'callback_data' => "backto"],['text' => "❌حذف سرویس", 'callback_data' => "dlusmysv#$id"]];
            
           $aa = bot('editmessageText', [
                'chat_id' => $userid,
                'message_id' => $cmsgid,
                'parse_mode' => "HTML",
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]);
        }
    
}
if(preg_match('/leftgb/', $cdata)){
    $input = explode('#',$cdata);
    $total = $input[1];
    $up = $input[2];
    $down = $input[3];
    $leftgb = format_volume($total - $up - $down, 2);
    $totalvolume = format_volume($total);
    $up =  format_volume($up);
    $down = format_volume($down);
    bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "🔹 $leftgb حجم باقیمانده \n 🔺 $down دانلود \n 🔻 $up آپلود ",
            'show_alert' => true
        ]);
        exit;
}
if(preg_match('/qrcode/',$cdata)){
    $input = explode('#',$cdata);
    $oid = $input[1];
	$order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$oid")->fetch(2);
	$acc_link = $order['link'];
	
	include 'phpqrcode/qrlib.php';
    $path = 'images/';
    $file = $path.$userid.".png"; //unlink($file);
    $ecc = 'L';
    $pixel_Size = 10;
    $frame_Size = 5;
    QRcode::png($acc_link, $file, $ecc, $pixel_Size, $frame_Size);
	
	$telegram->sendPhoto($userid,'QrCode',$file);
}
/* end here */
/* up my service */
if(preg_match('/upmysrvice/', $cdata)){
	if($gateways['buy_gb'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر امکان افزایش حجم نیست',
            'show_alert' => true
        ]);
        exit;
    }
    $input = explode('#',$cdata);
    $cdata = str_replace('upmysrvice#','',$cdata);
    $res = $telegram->db->query("select * from extra_plan")->fetchAll(2);
    if(empty($res)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "در حال حاضر هیچ پلن حجمی وجود ندارد",
            'show_alert' => false
        ]);
        exit;
    }
    $keyboard = [];
    foreach($res as $cat){
        $id = $cat['id'];
        $title = $cat['volume'];
        $price = number_format($cat['price']);
        $keyboard[] = ['text' => "$title گیگ $price تومان", 'callback_data' => "buuygbplan#$cdata#$id"];
    }
    $keyboard = array_chunk($keyboard,2);
    //$keyboard[] = [['text' => "↪ برگشت", 'callback_data' => "ordetail#$oid"]];
    bot('sendmessage', [ // editmessageText
        'chat_id' => $userid,
        //'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "لطفا یکی از پلن های حجمی را انتخاب کنید :",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/buuygbplan/',$cdata)){
    $input = explode('#', $cdata);
    $cdata = str_replace('buuygbplan#','',$cdata);
    $pid = $input[4];
    $res = $telegram->db->query("select * from extra_plan where id=$pid")->fetch(2);
    $planprice = $res['price'];
    $plangb = $res['volume'];
    
    //$deldate = $time + $timewaitdel;
    //$telegram->db->query("insert into pay_messages values (null, '$cmsgid', '$userid', '$deldate')");

    $token = base64_encode($cdata."#$userid");
	if($gateways['bahamta']) $keyboard[] = [['text' => "پرداخت آنلاین - $price تومان", 'url' => baseURI."bahamta.php?type=volume&action=pay&token=$token"]];
    if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $planprice تومان", 'url' => baseURI."/volume/pay.php?token=$token"]];
    //if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."/volume/nextpay/pay.php?token=$token"]];
    if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "walvpay#$cdata"]];
    if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $planprice تومان",  'callback_data' => "offvpay#$cdata"]];

    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid, 
        'parse_mode' => "HTML",
        'text' => "لطفا با یکی از روش های زیر پرداخت خود را تکمیل کنید :",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]); 
}
if(preg_match('/offvpay/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"<b>صورت حساب شما با موفقیت ایجاد شد😇
لطفا مبلغ مورد نظر را به حساب زیر واریز کنید🙏</b>

☘ $cardinfo ☘

<blockquote>این فاکتور فقط تا نیم ساعت اعتبار دارد</blockquote>
<blockquote>پس از ارسال رسید خرید ها توسط ادمین تایید میشود</blockquote>
<blockquote>با دقت خرید کنید امکان برداشت وجه نیست</blockquote>

پس از پرداخت موفق <b>تصویر فیش واریز</b> را ارسال کنید",$cancelop);
    exit;
}
if(preg_match('/offvpay/',$state) and $text != '❌ انصراف'){
	bot('deleteMessage', ['chat_id' => $userid,'message_id' => $msgid -1]);
    file_put_contents("state/$userid.txt","");
    $input = explode('#',$state); 
    $server_id = $input[1];
    $inbound_id = $input[2];
    $remark = $input[3];
    $planid = $input[4]; //$telegram->sendMessage($userid,"se\");die;
    $res = $telegram->db->query("select * from extra_plan where id=$planid")->fetch(2); 
    $price = $res['price'];
	
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
	if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
	
    $volume = $res['volume'];
    $state = str_replace('offvpay#','',$state);
	
	$res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $name = $res['name'];
    $username = $res['username'];
    $tel = $res['tel'];

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption (فیش واریزی در بالای این پیام هست)";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش, سرویس شما شارژ می شود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "#$remark
🏷 $volume گیگ حجم سرویس ($price تومان)
✖کد کاربری: $userid
👤نام و نام خانوادگی: $name
📧یوزرنیم: @$username
☎️شماره موبایل : $tel
📝اطلاعات پرداخت کارت به کارت: $infoc
 ";
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'تایید پرداخت', 'callback_data' => "enaupble#$state#$userid"],
                ['text' => 'عدم تایید', 'callback_data' => "disable#$uid"]
            ]
        ]
    ]);
	$uniqmsgid = time().rand(0,99999); 
    $admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3){
            if($fileid) bot('sendphoto',['chat_id' => $admid, 'caption'=> '','photo' => $fileid]);
            $msgres = bot('sendmessage',[
                'chat_id' => $admid,
                'text'=> $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
            $msgresid = $msgres->result->message_id;
            $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', $admid, '$msgresid', 0, $time)");
        }
    }
    if($fileid) bot('sendphoto',['chat_id' => ADMIN, 'caption'=> '','photo' => $fileid]);
    $msgres = bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
    $msgresid = $msgres->result->message_id;
    $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', ".ADMIN.", '$msgresid', 0, $time)");
}
if(preg_match('/enaupble/',$cdata) and $text != '❌ انصراف'){
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid' and status=2")->fetch(2);
    if(!empty($res)){
        exit;
    }
    
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata); //$telegram->sendMessage($userid,$cdata);die;
    $server_id = $input[1];
    $inbound_id = $input[2];
    $remark = $input[3];
    $planid = $input[4];
    $uid = $input[5];
    $res = $telegram->db->query("select * from extra_plan where id=$planid")->fetch(2);
    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    $price = $res['price'];
    $volume = $res['volume'];
	
	$seller = $telegram->db->query("select * from fl_sellers where userid=$uid")->fetch(2);
	if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));

    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    if($srv_type == 'marzban'){
        $username = $remark;
        require_once('marz.php');
        $response = muser_detail($server_id, $username);
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if(!$response->subscription_url){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
       
        $response->data_limit += $volume * 1073741824;
        $response->status = 'active';
        $response = add_mtraffic($server_id, $username, $response); 
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if($response->detail){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در عملیات. مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
    }else{
        require_once('vray.php');
        if($inbound_id > 0)
            $response = update_client_traffic($server_id, $inbound_id, $remark, $volume, 0);
        else
            $response = update_inbound_traffic($server_id, $remark, $volume, 0);
            
        if(!$response->success){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "مشکل فنی در ارتباط با سرور. لطفا سلامت سرور را بررسی کنید",
                'show_alert' => true
            ]);
            exit;
    		
        }
    }
    
    $telegram->db->query("update fl_order set notif=0 where remark='$remark'");
    
	// update button
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
	
	$res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid']; 
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        } 
    }
    
	$telegram->sendMessageCURL($userid,"حجم سرویس کاربر به مقدار $volume گیگ شارژ شد",$finalop);
    $telegram->sendMessage($uid, "✅$volume گیگ به حجم سرویس شما اضافه شد");
    
}
if(preg_match('/walvpay/', $cdata)){
    $input = explode('#',$cdata);
    $server_id = $input[1];
    $inbound_id = $input[2];
    $remark = $input[3];
    $planid = $input[4];

    $res = $telegram->db->query("select * from extra_plan where id=$planid")->fetch(2);
    
    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    $price = $res['price'];
    
    $volume = $res['volume'];
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
	if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
    
    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }

    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    if($srv_type == 'marzban'){
        $username = $remark;
        require_once('marz.php');
        $response = muser_detail($server_id, $username); //$telegram->sendMessage($userid, json_encode($input));exit;
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if(!$response->subscription_url){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
        $response->data_limit += $volume * 1073741824;
        $response->status = 'active';
        $response = add_mtraffic($server_id, $username, $response); 
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if($response->detail){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در عملیات. مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
    }else{
        require_once('vray.php');
        if($inbound_id > 0)
            $response = update_client_traffic($server_id, $inbound_id, $remark, $volume, 0);
        else
            $response = update_inbound_traffic($server_id, $remark, $volume, 0);
        
        if(!$response->success){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "به دلیل مشکل فنی امکان افزایش حجم نیست. لطفا به مدیریت اطلاع بدید یا 5دقیقه دیگر دوباره تست کنید",
                'show_alert' => true
            ]);
            exit;
        }
    }
    
    $telegram->db->query("update fl_user set wallet = wallet - $price WHERE userid='$userid'");
	$telegram->db->query("update fl_order set notif=0 where remark='$remark'");
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
	$sndmsg = "
خرید $volume گیگ با کیف پول
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
ریمارک : $remark
";
    $telegram->sendMessage($sendchnl,$sndmsg);
    $telegram->sendMessage($userid, "✅$volume گیگ به حجم سرویس شما اضافه شد");exit;
   // $telegram->sendMessage($userid, json_encode($response));exit;
}
/* end up my service */

/* up day */
if(preg_match('/relinsrvc/', $cdata)){
	if($gateways['buy_day'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر امکان خرید روز اضافی نیست',
            'show_alert' => true
        ]);
        exit;
    }
    $input = explode('#',$cdata);
    $cdata = str_replace('relinsrvc#','',$cdata);
    $res = $telegram->db->query("select * from extra_day")->fetchAll(2);
    if(empty($res)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "در حال حاضر هیچ پلنی برای افزایش مدت زمان سرویس وجود ندارد",
            'show_alert' => false
        ]);
        exit;
    }
    $keyboard = [];
    foreach($res as $cat){
        $id = $cat['id'];
        $title = $cat['volume'];
		
		$price = $cat['price'];
		$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
			
        $price = number_format($price);
        $keyboard[] = ['text' => "$title روز $price تومان", 'callback_data' => "buuydayplan#$cdata#$id"];
    }
    $keyboard = array_chunk($keyboard,2);
    //$keyboard[] = [['text' => "↪ برگشت", 'callback_data' => "ordetail#$oid"]];
    bot('sendmessage', [ // editmessageText
        'chat_id' => $userid,
        //'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "لطفا یکی از پلن های افزایشی را انتخاب کنید :",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/buuydayplan/',$cdata)){
    $input = explode('#', $cdata);
    $cdata = str_replace('buuydayplan#','',$cdata);
    $pid = $input[4];
    $res = $telegram->db->query("select * from extra_day where id=$pid")->fetch(2);
    $planprice = $res['price'];
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $planprice = $planprice - (($planprice) * ($seller['percent'] / 100));
    
    //$deldate = $time + $timewaitdel;
    //$telegram->db->query("insert into pay_messages values (null, '$cmsgid', '$userid', '$deldate')");

    $token = base64_encode($cdata."#$userid");
	if($gateways['bahamta']) $keyboard[] = [['text' => "پرداخت آنلاین - $price تومان", 'url' => baseURI."bahamta.php?type=day&action=pay&token=$token"]];
    if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $planprice تومان", 'url' => baseURI."/day/pay.php?token=$token"]];
    //if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."/day/nextpay/pay.php?token=$token"]];
    if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "waldaypay#$cdata"]];
    if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $planprice تومان",  'callback_data' => "offdaypay#$cdata"]];

    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "لطفا با یکی از روش های زیر پرداخت خود را تکمیل کنید :",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/offdaypay/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"<b>صورت حساب شما با موفقیت ایجاد شد😇
لطفا مبلغ مورد نظر را به حساب زیر واریز کنید🙏</b>

☘ $cardinfo ☘

<blockquote>این فاکتور فقط تا نیم ساعت اعتبار دارد</blockquote>
<blockquote>پس از ارسال رسید خرید ها توسط ادمین تایید میشود</blockquote>
<blockquote>با دقت خرید کنید امکان برداشت وجه نیست</blockquote>

پس از پرداخت موفق <b>تصویر فیش واریز</b> را ارسال کنید",$cancelop);
    exit;
}
if(preg_match('/offdaypay/',$state) and $text != '❌ انصراف'){
	bot('deleteMessage', ['chat_id' => $userid,'message_id' => $msgid -1]);
    file_put_contents("state/$userid.txt","");
    $input = explode('#',$state); 
    $server_id = $input[1];
    $inbound_id = $input[2];
    $remark = $input[3];
    $planid = $input[4]; //$telegram->sendMessage($userid,"se\");die;
    $res = $telegram->db->query("select * from extra_day where id=$planid")->fetch(2); 
    $price = $res['price'];
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
    $volume = $res['volume'];
    $state = str_replace('offdaypay#','',$state);

	$res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $name = $res['name'];
    $username = $res['username'];
    $tel = $res['tel'];
    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption (فیش واریزی در بالای این پیام هست)";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش, از طریق ربات اطلاع رسانی می شود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "#$remark
🏷 $volume روز افزایشی سرویس ($price تومان)
✖کد کاربری: $userid
👤نام و نام خانوادگی: $name
📧یوزرنیم: @$username
☎️شماره موبایل : $tel
📝اطلاعات پرداخت کارت به کارت: $infoc
 ";
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'تایید پرداخت', 'callback_data' => "enadayble#$state#$userid"],
                ['text' => 'عدم تایید', 'callback_data' => "disable#$userid"]
            ]
        ]
    ]);
	$uniqmsgid = time().rand(0,99999); 
    $admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3){
            if($fileid) bot('sendphoto',['chat_id' => $admid, 'caption'=> '','photo' => $fileid]);
            $msgres = bot('sendmessage',[
                'chat_id' => $admid,
                'text'=> $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
            $msgresid = $msgres->result->message_id;
            $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', $admid, '$msgresid', 0, $time)");
        }
    }
    if($fileid) bot('sendphoto',['chat_id' => ADMIN, 'caption'=> '','photo' => $fileid]);
    $msgres = bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
    $msgresid = $msgres->result->message_id;
    $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', ".ADMIN.", '$msgresid', 0, $time)");
}
if(preg_match('/enadayble/',$cdata) and $text != '❌ انصراف'){
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid' and status=2")->fetch(2);
    if(!empty($res)){
        exit;
    }
    
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata); //$telegram->sendMessage($userid,$cdata);die;
    $server_id = $input[1];
    $inbound_id = $input[2];
    $remark = $input[3];
    $planid = $input[4];
    $uid = $input[5];
    $res = $telegram->db->query("select * from extra_day where id=$planid")->fetch(2);
    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    $price = $res['price'];
    $volume = $res['volume'];
	$seller = $telegram->db->query("select * from fl_sellers where userid=$uid")->fetch(2);
    if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));

    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    if($srv_type == 'marzban'){
        $username = $remark;
        require_once('marz.php');
        $response = muser_detail($server_id, $username);
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if(!$response->subscription_url){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
        $expire = $response->expire;
        if($expire < $time) $expire = $time + ($volume * 86400);
        else $expire += $volume * 86400;
        
        $response->expire = $expire;
        $response->status = 'active';
        $response = add_mtraffic($server_id, $username, $response); 
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if($response->detail){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در عملیات. مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
    }else{
        require_once('vray.php');
        if($inbound_id > 0)
            $response = update_client_traffic($server_id, $inbound_id, $remark, 0, $volume);
        else
            $response = update_inbound_traffic($server_id, $remark, 0, $volume);
            
        if(!$response->success){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "مشکل فنی در ارتباط با سرور. لطفا سلامت سرور را بررسی کنید",
                'show_alert' => true
            ]);
            exit;
        }
    }
    
    $telegram->db->query("update fl_order set notif=0 where remark='$remark'");
    $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
	// update button
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
	$res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid']; 
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        } 
    }
    $telegram->sendMessage($uid, "✅$volume روز به مدت زمان سرویس شما اضافه شد");
    $telegram->sendMessageCURL($userid,"مدت زمان سرویس کاربر به مقدار $volume روز شارژ شد",$finalop);
}
if(preg_match('/waldaypay/', $cdata)){
    $input = explode('#',$cdata);
    $server_id = $input[1];
    $inbound_id = $input[2];
    $remark = $input[3];
    $planid = $input[4];


    $res = $telegram->db->query("select * from extra_day where id=$planid")->fetch(2);
    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    $price = $res['price'];
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
            if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
    $volume = $res['volume'];
    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }

    $srv_type = $telegram->db->query("select * from server_info WHERE id='$server_id'")->fetch(2)['ptype'];
    if($srv_type == 'marzban'){
        $username = $remark;
        require_once('marz.php');
        $response = muser_detail($server_id, $username);
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if(!$response->subscription_url){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
        $expire = $response->expire;
        if($expire < $time) $expire = $time + ($volume * 86400);
        else $expire += $volume * 86400;
        
        $response->expire = $expire;
        $response->status = 'active';
        $response = add_mtraffic($server_id, $username, $response); 
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
    
    	if($response->detail){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در عملیات. مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
            exit;
        }
    }else{
        require_once('vray.php');
        if($inbound_id > 0)
            $response = update_client_traffic($server_id, $inbound_id, $remark, 0, $volume);
        else
            $response = update_inbound_traffic($server_id, $remark, 0, $volume);
        
        if(!$response->success){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => "به دلیل مشکل فنی امکان افزایش حجم نیست. لطفا به مدیریت اطلاع بدید یا 5دقیقه دیگر دوباره تست کنید",
                'show_alert' => true
            ]);
            exit;
        }
    }
    
    $telegram->db->query("update fl_order set notif=0 where remark='$remark'");
    $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
    $telegram->db->query("update fl_user set wallet = wallet - $price WHERE userid='$userid'");
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
	$sndmsg = "
خرید $volume روز با کیف پول
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
ریمارک : $remark
";
    $telegram->sendMessage($sendchnl,$sndmsg);
    $telegram->sendMessage($userid, "✅$volume روز به مدت زمان سرویس شما اضافه شد");exit;
}
/* end up day */


if($cdata == 'trjntchange'){
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "پروتکل تروجان فقط نوع شبکه TCP را دارد",
        'show_alert' => false
    ]);exit;
}
if(preg_match('/renewacc/',$cdata)){
	if($gateways['renew'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر تمدید غیرفعال است و بزودی فعال می کنیم',
            'show_alert' => true
        ]);
        exit;
    }
    $oid = str_replace('renewacc#','',$cdata);
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $sid = $order['server_id'];
    $respd = $telegram->db->query("select * from fl_file WHERE server_id='$sid' and price > 0 and active=1 order by id asc")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡پلنی برای تمدید وجود ندارد ",
            'show_alert' => false
        ]);
    }else{
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "📍در حال دریافت لیست پلن ها",
            'show_alert' => false
        ]);
        $keyboard = [];
        foreach($respd as $file){
            $id = $file['id'];
            $name = $file['title'];
            $price = $file['price'];
            $price = number_format($price).' تومان ';
            $keyboard[] = ['text' => "$name - $price", 'callback_data' => "re2newacc#$id#$oid"];
        }
        $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => "ordetail#$oid"];
        $keyboard = array_chunk($keyboard,1);
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text' => "🔰 یکی از پلن ها را برای تمدید انتخاب کنید👈
⚠️ با تمدید اکانت حجم و زمان انقضای باقیمانده از اول محاسبه می شود و امکان جمع آن با سرویس تمدید نیست.
✔️اگر فقط حجم یا زمان سرویس به پایان رسیده می توانید از دکمه های افزایش زمان/حجم سرویس هر یک را جداگانه شارژ کنید
",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
}
if(preg_match('/re2newacc/',$cdata)){
    if($gateways['renew'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر تمدید غیرفعال است و بزودی فعال می کنیم',
            'show_alert' => true
        ]);
        exit;
    }
    $input = explode('#',$cdata);
    $fid = $input[1];
    $oid = $input[2];
    $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
    $price = $respd['price'];
    $srvid = $respd['server_id'];
	
	if($price == 0){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => "امکان تمدید کانفیگ تست وجود ندارد",
			'show_alert' => false
		]);exit;
	}
    
    $telegram->db->query("update fl_order set fileid=$fid where id=$oid");

    $token = base64_encode("$userid#$fid#$oid");
	if($gateways['bahamta']) $keyboard[] = [['text' => "پرداخت آنلاین - $price تومان", 'url' => baseURI."bahamta.php?type=renew&action=pay&token=$token"]];
    if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $price تومان", 'url' => baseURI."/renew/pay.php?token=$token"]];
    if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."/renew/nextpay/pay.php?token=$token"]];
    if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $price تومان",  'callback_data' => "offrnwpay#$fid#$oid"]];
    if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "walrnwpay#$fid#$oid"]];
    
	$dcount = $telegram->db->query("select * from fl_discount WHERE active=1 and (sid = 0 or sid = $srvid)")->rowCount();
    if($dcount > 0){
        $keyboard[] = [['text' => '🔸کد تخفیف دارید؟ بزنید ', 'callback_data' => "submitRNdiscount#$oid"]];
    }
	
    $keyboard[] = [['text' => '🔙 بازگشت', 'callback_data' => "renewacc#$oid"]];


    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "لطفا با یکی از روش های زیر اکانت خود را تمدید کنید :",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/offrnwpay/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"<b>صورت حساب شما با موفقیت ایجاد شد😇
لطفا مبلغ مورد نظر را به حساب زیر واریز کنید🙏</b>

☘ $cardinfo ☘

<blockquote>این فاکتور فقط تا نیم ساعت اعتبار دارد</blockquote>
<blockquote>پس از ارسال رسید خرید ها توسط ادمین تایید میشود</blockquote>
<blockquote>با دقت خرید کنید امکان برداشت وجه نیست</blockquote>

پس از پرداخت موفق <b>تصویر فیش واریز</b> را ارسال کنید",$cancelop);
    exit;
}
if(preg_match('/offrnwpay/',$state) and $text != '❌ انصراف'){
	bot('deleteMessage', ['chat_id' => $userid,'message_id' => $msgid -1]);
    file_put_contents("state/$userid.txt","");
    $input = explode('#',$state);
    $fid = $input[1];
    $oid = $input[2];
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $remark = $order['remark'];
    $uid = $order['userid'];
    $userinfo = $telegram->db->query("select * from fl_user WHERE userid='$userid'")->fetch(2);
    $userName = $userinfo['username'];
    $tel = $userinfo['tel'];
    $uname = $userinfo['name'];
    
    $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
    $price = $respd['price'];

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption (فیش واریزی در بالای این پیام هست)";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد بررسی و تایید فیش, سرویس شما تمدید و از طریق ربات اطلاع می دهیم.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷 تمدید سرویس $remark ($price تومان)
✖کد کاربری: $uid
👤نام و نام خانوادگی: $uname
📧یوزرنیم: @$userName
☎️شماره موبایل : $tel
📝اطلاعات پرداخت کارت به کارت: $infoc
 ";
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'تایید پرداخت', 'callback_data' => "enarenwble#$userid#$fid#$oid"],
                ['text' => 'عدم تایید', 'callback_data' => "disable#$uid"]
            ]
        ]
    ]);
	$uniqmsgid = time().rand(0,99999); 
    $admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3){
            if($fileid) bot('sendphoto',['chat_id' => $admid, 'caption'=> '','photo' => $fileid]);
            $msgres = bot('sendmessage',[
                'chat_id' => $admid,
                'text'=> $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
            $msgresid = $msgres->result->message_id;
            $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', $admid, '$msgresid', 0, $time)");
        }
    }
    if($fileid) bot('sendphoto',['chat_id' => ADMIN, 'caption'=> '','photo' => $fileid]);
    $msgres = bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
    $msgresid = $msgres->result->message_id;
    $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', ".ADMIN.", '$msgresid', 0, $time)");
}
if(preg_match('/enarenwble/',$cdata) and $text != '❌ انصراف'){
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid' and status=2")->fetch(2);
    if(!empty($res)){
        exit;
    }
    
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata); //$telegram->sendMessage($userid,$cdata);die;
    $uid = $input[1];
    $fid = $input[2];
    $oid = $input[3];
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $remark = $order['remark'];
    $server_id = $order['server_id'];
    $inbound_id = $order['inbound_id'];
    $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
    $name = $respd['title'];
    $days = $respd['days'];
    $volume = $respd['volume'];
    $price = $respd['price'];

    require_once('vray.php');
    if($inbound_id > 0)
        $response = renew_client($server_id, $inbound_id, $remark, $volume, $days);
    else
        $response = renew_inbound($server_id, $remark, $volume, $days);
    
	if(is_null($response)){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => '🔻مشکل فنی در اتصال به سرور. لطفا به مدیریت اطلاع بدید',
			'show_alert' => true
		]);
		exit;
	}
	
	if($response->success){
        $telegram->db->query("update fl_order set expire_date= $expire_date + $days * 86400,notif=0 where id='$oid'");
        $telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$uid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
		$telegram->sendMessageCURL($userid,"سرویس $remark با موفقیت تمدید شد",$finalop);
		// update button
		bot('editMessageReplyMarkup',[
			'chat_id' => $userid,
			'message_id' => $cmsgid,
			'reply_markup' => json_encode([
				'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
			])
		]);
        $telegram->sendMessage($uid, "✅سرویس $remark با موفقیت تمدید شد");exit;
    }else {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "مشکل فنی در ارتباط با سرور. لطفا سلامت سرور را بررسی کنید",
            'show_alert' => true
        ]);
        exit;
    }
}
if(preg_match('/walrnwpay/', $cdata)){
    $input = explode('#',$cdata);
    $fid = $input[1];
    $oid = $input[2];
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $remark = $order['remark'];
    $server_id = $order['server_id'];
    $inbound_id = $order['inbound_id'];
    $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
    $name = $respd['title'];
    $days = $respd['days'];
    $volume = $respd['volume'];
    $price = $respd['price'];
 
    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }

    require_once('vray.php');
    if($inbound_id > 0)
        $response = renew_client($server_id, $inbound_id, $remark, $volume, $days);
    else
        $response = renew_inbound($server_id, $remark, $volume, $days);

	if(is_null($response)){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => '🔻مشکل فنی در اتصال به سرور. لطفا به مدیریت اطلاع بدید',
			'show_alert' => true
		]);
		exit;
	}
	if($response->success){
		$telegram->db->query("update fl_order set expire_date= $time + $days * 86400,notif=0 where id='$oid'");
		$telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
		$telegram->db->query("update fl_user set wallet = wallet - $price WHERE userid='$userid'");
		$sndmsg = "
تمدید سرویس $remark
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
تعداد روز  $days
تعداد گیگ $volume
";
		$telegram->sendMessage($sendchnl,$sndmsg);
		// update button
		bot('editMessageReplyMarkup',[
			'chat_id' => $userid,
			'message_id' => $cmsgid,
			'reply_markup' => json_encode([
				'inline_keyboard' => [[['text' => '✅تمدید شد', 'callback_data' => "dontsendanymore"]]],
			])
		]);
		$res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
        if(!empty($res)){
            $uniqmsgid = $res['uniqid']; 
            $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
            foreach($res2 as $rsmsg){
                $rid = $rsmsg['id'];
                $mownerid = $rsmsg['userid'];
                $mmsgid = $rsmsg['message_id'];
                $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
                bot('editMessageReplyMarkup',[
            		'chat_id' => $mownerid,
            		'message_id' => $mmsgid,
            		'reply_markup' => json_encode([
            			'inline_keyboard' => [[['text' => '✅تمدید شد', 'callback_data' => "dontsendanymore"]]],
            		])
            	]);
            } 
        }
		$telegram->sendMessage($userid, "✅سرویس $remark با موفقیت تمدید شد");exit;
	}else{
		
	}
    
   // $telegram->sendMessage($userid, json_encode($response));exit;
}

if($text=='موجودی کاربران' and ($userid==ADMIN or isAdmin() )){
    $users = $telegram->db->query("SELECT * FROM `fl_user` where wallet > 0 order by wallet DESC")->fetchAll(2);
    if(empty($users)){
        $msg = "موردی یافت نشد";
    }else {
        $msg = '';
        foreach ($users as $cty) {
            $id = $cty['id'];
            $usid = $cty['userid'];
            $usname = $cty['username'];
            $uname = $cty['name'];
            $wallet = number_format($cty['wallet']);
            $msg .= "
UserID: $usid
Username: @$usname
Name: $uname
💰 $wallet 
📝 /waladd$id
====";
			if(strlen($msg) > 3950){
                $telegram->sendMessage($userid,$msg);
                $msg = '';
            }
        }
    }
    $telegram->sendMessage($userid,$msg);
}
if(preg_match('/waladd/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"لطفا موجودی جدید کاربر را با اعداد لاتین و به تومان وارد کنید:", $cancelop);
	exit;
}
if(preg_match('/waladd/',$state) and $text != '❌ انصراف') {
    $id=str_ireplace('/waladd','',$state);
	$telegram->db->query("update fl_user set wallet='$text' where id={$id}");
	$telegram->sendMessageCURL($userid,"✅موجودی کاربر به مقدار $text تومان تغییر کرد", $adminop);
	file_put_contents("state/$userid.txt",'');
}

if(preg_match('/chfrcnt/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"لطفا تعداد دریافت رایگان را با اعداد لاتین وارد کنید:", $cancelop);
	exit;
}
if(preg_match('/chfrcnt/',$state) and $text != '❌ انصراف') {
    $uid=str_ireplace('/chfrcnt','',$state);
    if($text == 0) $add = 1; else $add = intval($text) + 1;
	file_put_contents("state/{$uid}-free.txt","$add");
	$telegram->sendMessageCURL($userid,"✅تعداد دریافت رایگان به مقدار $text تغییر کرد", $adminop);
	file_put_contents("state/$userid.txt",'');
}

if($text=='مدیریت دسته بندی ها' and ($userid==ADMIN or isAdmin() )){
    $cats = $telegram->db->query("SELECT * FROM `fl_cat` where active=1 and parent=0")->fetchAll(2);
    if(empty($cats)){
        $msg = "موردی یافت نشد";
    }else {
        $msg = '';
        foreach ($cats as $cty) {
            $id = $cty['id'];
            $cname = $cty['title'];
            $msg .= "
✅نام : $cname
♻️ویرایش : /editc$id
❌حذف : /delcat$id
====";
			if(strlen($msg) > 3950){
                $telegram->sendMessage($userid,$msg);
                $msg = '';
            }
        }
    }
    $telegram->sendMessage($userid,$msg);
}
if($text=='افزودن دسته بندی' and ($userid == ADMIN or isAdmin() )){
    $state = file_put_contents('state/'.$userid.'.txt','addnewcat');
    $telegram->db->query("delete from fl_cat WHERE active=0");
    $sql = "INSERT INTO `fl_cat` VALUES (NULL, 0, '', 0,2,0);";
    $telegram->db->query($sql);
    $msg = '◀️ لطفا عنوان دسته بندی را وارد کنید';
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
    exit;
}
// add category
if(preg_match('/addnewcat/',$state) and $text!='❌ انصراف'){
    $step = $telegram->checkStep('fl_cat');
    if($step==2 and $text!='❌ انصراف' ){
        
        $telegram->db->query("update fl_cat set title='$text',step=4,active=1 where active=0");
        $msg = '✅دسته بندی جدید با موفقیت ثبت شد';
        $telegram->sendMessageCURL($userid,$msg,$adminop);
    }
}
// end add category
if(preg_match('/delcat/',$text) and ($userid==ADMIN or isAdmin() )){
    $pid=str_ireplace('/delcat','',$text);
    $telegram->db->query("delete from fl_cat where id={$pid}");
    $telegram->sendMessage($userid,"دسته بندی موردنظر با موفقیت حذف شد");
}
if(preg_match('/editc/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"نام جدید دسته بندی را وارد کنید:");exit;
}
if(preg_match('/editc/',$state)){
    $pid=str_ireplace('/editc','',$state);
    $telegram->db->query("update fl_cat set title='$text' where id={$pid}");
    $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
    file_put_contents("state/$userid.txt",'');
}

if($text=='افزودن همکار جدید' and ($userid == ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",'addhamkar');
    $telegram->sendMessageCURL($userid,"لطفا ایدی عددی و درصد همکاری را وارد کنید:
مثال
2355434-10

مقدار اول 23555434 ایدی عددی
مقدار دوم درصد بین 0 تا 100

توجه کنید اعداد بصورت لاتین باشند",$cancelop);exit;
}
if(preg_match('/addhamkar/',$state) and $text != '❌ انصراف') {
    $input = explode('-',$text); 
	if(count($input) !=2) {$telegram->sendMessage($userid,'فرمت ارسال صحیح نیست');exit;}
    $uid = intval($input[0]); 
    if($uid == '0'){$telegram->sendMessage($userid,'فرمت ارسال صحیح نیست');exit;}
    $percent = intval($input[1]);
    if($percent > 100) {$telegram->sendMessage($userid,'فرمت ارسال صحیح نیست');exit;}
    $telegram->db->query("insert into fl_sellers VALUES (NULL,'$uid',$percent)");
    $telegram->sendMessageCURL($userid,"✅همکار جدید با موفقیت اضافه شد",$adminop);
    file_put_contents("state/$userid.txt",'');
}

if($text=='همکارها' and ($userid==ADMIN or isAdmin() )){
    $cats = $telegram->db->query("SELECT * FROM `fl_sellers`")->fetchAll(2);
    if(empty($cats)){
        $msg = "موردی یافت نشد";
    }else {
        $msg = '';
        foreach ($cats as $cty) {
            $id = $cty['id'];
            $uid = $cty['userid'];
            $uname = $telegram->db->query("SELECT * FROM `fl_user` where userid= '$uid'")->fetch(2)['name'];
            $percent = $cty['percent'];
            $msg .= "
#⃣  $uname ($uid)
♻ همکاری {$percent}٪ /edithmkr$id
❌ حذف /delhmkr$id
===============";
			if(strlen($msg) > 3950){
                $telegram->sendMessage($userid,$msg);
                $msg = '';
            }
        }
    }
    $telegram->sendMessage($userid,$msg);
}
if(preg_match('/edithmkr/',$text) and ($userid==ADMIN or isAdmin() )){ 
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"درصد جدید همکاری را بصورت اعداد لاتین و بین 0 تا 100 وارد کنید:", $cancelop);exit;
}
if(preg_match('/edithmkr/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/edithmkr','',$state);
    if(intval($text) > 100) {
        $telegram->sendMessage($userid,'لطفا عدد لاتین بین 0 تا 100 وارد کنید. خود 0 قابل قبول نیست');
        exit;
    }
    $telegram->db->query("update fl_sellers set percent='$text' where id={$pid}");
    $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
    file_put_contents("state/$userid.txt",'');
}

if(preg_match('/delhmkr/',$text) and ($userid==ADMIN or isAdmin() )){
    $pid=str_ireplace('/delhmkr','',$text);
    $telegram->db->query("delete from fl_sellers where id={$pid}");
    $telegram->sendMessage($userid,"همکار مورد نظر با موفقیت حذف شد");
}

if($text=='مدیریت سرورها' and ($userid==ADMIN or isAdmin() )){
    $cats = $telegram->db->query("SELECT * FROM `fl_server` where active=1")->fetchAll(2);
    if(empty($cats)){
        $msg = "موردی یافت نشد";
    }else {
        $msg = '';
        foreach ($cats as $cty) {
            $id = $cty['id'];
            $cname = $cty['title']." ".$cty['flag']." (".$cty['remark'].")";
            $ucount = $cty['ucount'];
            $msg .= "
#⃣آیدی : $id
✅نام : $cname 
➕تعداد : $ucount /chslmt$id
©کپی: /copysvpl$id
♻️ویرایش : /editsrv$id
❌حذف : /delsrv$id
===============";
			if(strlen($msg) > 3950){
                $telegram->sendMessage($userid,$msg);
                $msg = '';
            }
        }
    }
    $telegram->sendMessage($userid,$msg);
}
if(preg_match('/chslmt/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"ظرفیت جدید را وارد کنید(عدد لاتین): اگر 0 بزارید سرور از لیست :خرید کانفیگ: مخفی می شود",$cancelop);exit;
}
if(preg_match('/chslmt/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chslmt','',$state);
    if(is_numeric($text)){
        $telegram->db->query("update fl_server set ucount='$text' where id={$pid}");
        $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد",$adminop);
        file_put_contents("state/$userid.txt",'');
    }else{
        $telegram->sendMessage($userid,"یک مقدار عددی و صحیح وارد کنید");
    }
}
if(preg_match('/copysvpl/',$text) and ($userid==ADMIN or isAdmin() )){
    $sid = str_replace('/copysvpl', '', $text);
    $srvs = $telegram->db->query("SELECT * FROM `fl_server` WHERE id != $sid")->fetchAll(2);
    if(empty($srvs)) {
        $telegram->sendMessage($userid,'سرور دیگری برای کپی ندارید. لطفا ابتدا یک سرور بسازید و بعد پلن ها رو کپی کنید');
        exit;
    }

    file_put_contents("state/$userid.txt",$text);
    $srvkey = [];
    foreach($srvs as $srv){
        $id = $srv['id'];
        $title = $telegram->db->query("SELECT * FROM `fl_server` where id=$id")->fetch(2)['title'];
        $srvkey[] = ['text' => $title, 'callback_data' => "copysvpl#$id"];
    }
    $srvkey = array_chunk($srvkey,1);
    bot('sendmessage', [
        'chat_id' => $userid,
        'text' => "برای کپی همه پلن ها, سرور مقصد را انتخاب کنید :",
        'reply_markup' => json_encode(['inline_keyboard' => $srvkey])
    ]);
}
if(preg_match('/copysvpl/',$cdata) and preg_match('/copysvpl/',$state)){
    $sid_from = str_ireplace("/copysvpl",'', $state);
    $sid_to = str_ireplace('copysvpl#','',$cdata);
    $files = $telegram->db->query("select * from fl_file where server_id=$sid_from and active=1 order by id asc")->fetchAll(2);
    foreach ($files as $file){
        $fid = $file['id'];
        $catid = $file['catid'];
        $server_id = $file['server_id'];
        $inbound_id = $file['inbound_id'];
        $acount = $file['acount'];
        $limitip = $file['limitip'];
        $title = $file['title'];
        $protocol = $file['protocol'];
        $days = $file['days'];
        $volume = $file['volume'];
        $type = $file['type'];
        $price = $file['price'];
        $descr = $file['descr'];
        $pic = $file['pic'];
        $active = $file['active'];
        $step = $file['step'];
        $sendcount = $file['sendcount'];
        $isvip = $file['isvip'];
        $telegram->db->query("INSERT INTO `fl_file` VALUES (NULL, '', $catid,$sid_to,$inbound_id,$acount, $limitip, '$title', '$protocol', $days, $volume, '$type', $price, '$descr', '$pic',$active,$step, '$time','$sendcount','$isvip');");
    }
    bot('editmessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text' => "✅همه پلن های سرور با موفقیت کپی شدند",
    ]);
    file_put_contents("state/$userid.txt",'');
}
if($text=='افزودن سرور' and ($userid == ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",'addserver');
    $telegram->sendMessageCURL($userid,"نام سرور, ظرفیت آن, ریمارک و پرچم را هر کدام در یک خط جداگانه وارد کنید:
مثال :
سرور المان
250
srvDE
🇩🇪

توجه کنید که ریمارک باید بصورت انگلیسی و بدون فاصله باشد",$cancelop);exit;
}
if(preg_match('/addserver/',$state) and $text != '❌ انصراف') {
    $input = explode(PHP_EOL,$text); 
	if(count($input) !=4) {$telegram->sendMessage($userid,'فرمت ارسال صحیح نیست. لطفا خط اول اسم نمایشی, خط دوم ظرفیت, خط سوم ریمارک, خط چهارم پرچم را به اعداد لاتین بفرستید');exit;}
    $title = $input[0];
    $ucount = $input[1];
    $remark = $input[2];
	if(!preg_match('/^[\w]+$/', $remark)){
        $telegram->sendMessage($userid,'لطفا فقط حروف انگلیسی و اعداد لاتین بفرستید');die;
    }
    $flag = $input[3];
    $telegram->db->query("insert into fl_server VALUES (NULL,'$title',$ucount,'$remark','$flag',1)");
    $telegram->sendMessageCURL($userid,"✅سرور جدید با موفقیت اضافه شد",$adminop);
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/delsrv/',$text) and ($userid==ADMIN or isAdmin() )){
    $pid=str_ireplace('/delsrv','',$text);
    $telegram->db->query("delete from fl_server where id={$pid}");
    $telegram->sendMessage($userid,"سرور موردنظر با موفقیت حذف شد");
}
if(preg_match('/editsrv/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"نام سرور و ریمارک را هر کدام در یک خط جداگانه وارد کنید:
مثال :
سرور المان
srvDE

توجه کنید که ریمارک باید بصورت انگلیسی و بدون فاصله باشد",$cancelop);exit;

}
if(preg_match('/editsrv/',$state) and $text != '❌ انصراف') {
    $id = str_ireplace("/editsrv",'', $state); 
    $input = explode(PHP_EOL,$text);
	if(count($input) !=2) {$telegram->sendMessage($userid,'فرمت ارسالی صحیح نیست. لطفا خط اول اسم نمایشی, خط دوم ریمارک را بفرستید');exit;}
	$title = $input[0];
    $remark = $input[1];
	if(!preg_match('/^[\w]+$/', $remark)){
        $telegram->sendMessage($userid,'لطفا فقط حروف انگلیسی و اعداد لاتین بفرستید');die;
    }
    $telegram->db->query("update fl_server set title='$title',remark='$remark' where id=$id");
    $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد",$adminop);
    file_put_contents("state/$userid.txt",'');
}

if ($text == '🗒 پیام همگانی' and ($userid == ADMIN or isAdmin() )){
    $state = file_put_contents('state/' . $userid . '.txt', 's2a');
    $msg = "لطفا پیام خود ارسال کنید. ";
    $telegram->sendAction($userid, 'typing');
    $telegram->sendHTML($userid, $msg, $cancelop);
    exit;
}
if ($state == 's2a' and $text != '❌ انصراف') {
    
    file_put_contents('state/' . $userid . '.txt', $text);
    $respd = $telegram->db->query("select * from fl_server ORDER BY id ASC")->fetchAll(2);
    $keyboard = [];
    foreach($respd as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $flag = $cat['flag'];
        $keyboard[] = ['text' => "$flag $name", 'callback_data' => "sendpm#$id"];
    }
    $keyboard[] = ['text' => "همه کاربران ربات", 'callback_data' => "sendpm#all"];
    $keyboard = array_chunk($keyboard,1);
    bot('sendmessage',[
        'chat_id' => $userid,
        'text'=> ' 📍 لطفا یکی از سرورها را برای ارسال پیام به کاربران آن, انتخاب کنید👇',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}

/*if ($text == '🗒 پیام همگانی' and ($userid==ADMIN or isAdmin() )){
    $cronjob = file_get_contents('cronjob.txt');
    $msg = file_get_contents('pm.txt');
    if(strlen($msg) > 1 and $cronjob != 0) {
        $users = $telegram->db->query("select * from fl_user")->rowCount();
        $leftm = $cronjob == 1 ? $users - $cronjob +1 : $users - $cronjob;
        $cronjob = $cronjob == 1 ? $cronjob-1 : $cronjob;
		//$leftm = $users - $cronjob;
        $telegram->sendMessage($userid, "📨یک پیام همگانی در حال ارسال می باشد و باید تا زمان اتمام آن منتظر بمانید
👤جمع کل کاربران : $users
✅تعداد ارسال شده : $cronjob
♻️تعداد باقی مانده : $leftm
.");exit;
    }
    $state = file_put_contents('state/' . $userid . '.txt', 's2a');
    $msg = "لطفا پیام خود ارسال کنید. ";
    $telegram->sendAction($userid, 'typing');
    $telegram->sendHTML($userid, $msg, $cancelop);
    exit;
}
if ($state == 's2a' and $text !='❌ انصراف') {
    file_put_contents('state/' . $userid . '.txt', '');
    $dbresult = $telegram->db->query("select * from fl_user")->fetchAll(2);
    $telegram->sendMessageCURL($userid, '👍🏻✅ پیام شما در صف ارسال به تمام کاربران ربات قرار گرفت ... ', $adminop);
    file_put_contents('cronjob.txt', 1);
    if($fileid !== null) {
        $value = ['fileid'=>$fileid,'caption'=>$caption];
        $type = $filetype;

    }else {$type = 'text';$value = $text;}
    $pmvalue = json_encode(['type'=>$type,'value'=> $value]);
    file_put_contents('pm.txt', $pmvalue);
}*/

if ($text == '📈آمار' and  ($userid == ADMIN or isAdmin() ) ) {
    file_put_contents('state/' . $userid . '.txt', '');
    $users = $telegram->db->query("select * from fl_user")->rowCount();
    $product = $telegram->db->query("select * from fl_file WHERE active=1")->rowCount();
    $fault = $telegram->db->query("select * from fl_order where status=0")->rowCount();
    $success = $telegram->db->query("select * from fl_order where status=1")->rowCount();
    $income = $telegram->db->query("select sum(amount) as amount from fl_order where status=1")->fetch(2)['amount'];
    $todaydate = strtotime(date('Y-m-d 00:00'));
    $todaydate2 = strtotime('+1 day',$todaydate); 
    $income_day = $telegram->db->query("select sum(amount) as amount FROM `fl_order` WHERE (date BETWEEN $todaydate and $todaydate2) and status=1")->fetch(2)['amount'];
    $income_day = is_null($income_day) ? 0 : $income_day;
    $fault_day = $telegram->db->query("select * from fl_order where (date BETWEEN $todaydate and $todaydate2) and status=0")->rowCount();
    $success_day = $telegram->db->query("select * from fl_order where (date BETWEEN $todaydate and $todaydate2) and status=1")->rowCount();
    $income_month = $telegram->db->query("select sum(amount) as amount FROM `fl_order` WHERE date >= $time - 86400*30 and status=1")->fetch(2)['amount'];
    $fault_month = $telegram->db->query("select * from fl_order where date >= $time - 86400*30 and status=0")->rowCount();
    $success_month = $telegram->db->query("select * from fl_order where date >= $time - 86400*30 and status=1")->rowCount();

    $income = number_format($income);
    $msg = "
✅تعداد کل کاربران ربات :$users 

✅تعداد کل محصولات :$product 

⏩تعداد تراکنش های ناموفق :$fault 

✅تعداد تراکنش های موفق :$success

✅درآمد کل  :$income تومان

======================
✅درآمد امروز  :".number_format($income_day)." تومان

⏩تعداد تراکنش های ناموفق امروز :$fault_day

✅تعداد تراکنش های موفق امروز :$success_day
======================
✅درآمد یک ماه اخیر (30 روز گذشته) :".number_format($income_month)." تومان

⏩تعداد تراکنش های ناموفق یک ماه اخیر (30 روز گذشته) :$fault_month

✅تعداد تراکنش های موفق یک ماه اخیر (30 روز گذشته) :$success_month
.";
    $telegram->sendMessage($userid, $msg);
}

/* add version 1*/
if(($text == '⚙️ مدیریت 1') and ($userid == ADMIN or isAdmin() )){
    file_put_contents('state/' . $userid . '.txt', '');
    $msg = 'مدیریت عزیز خوش آمدید';
    $telegram->sendHTML($userid, $msg, $version1op);
}

if(($text == '⚙️ مدیریت 2'  or $text == '↪️بازگشت' ) and ($userid == ADMIN or isAdmin() )){
    file_put_contents('state/' . $userid . '.txt', '');
    $msg = 'مدیریت عزیز خوش آمدید';
    $telegram->sendHTML($userid, $msg, $adminop);
}
/*end  add version 1*/

if ($text == '💡راهنما' or $text == '💡راهنمای اتصال' or $cdata == 'backhelp' or $text =='help') {
    $state = file_put_contents('state/' . $userid . '.txt', '');
    $keyboard = [
        [['text' => "آموزش اتصال", 'callback_data' => "help1center"]],
        [['text' => "آموزش ربات", 'callback_data' => "qacenter"]],
    ];
    $msg = "لطفا یکی از گزینه ها را انتخاب کنید";
    if(!is_null($cdata)){
        bot('editMessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else{
        bot('sendmessage', [
            'chat_id' => $userid,
            'text' => $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
    
    exit;
}
if(preg_match('/qac_enter/',$cdata)){
    $platform = explode('#',$cdata)[1];
    bot('editMessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text'=> $qamsg_arr[$platform],
        'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => "↪️بازگشت", 'callback_data' => "qacenter"]]]
        ])
    ]);
}
if(preg_match('/qacenter/',$cdata)){
    
    $keyboard =[
        [['text' => "آموزش دریافت تست رایگان", 'callback_data' => "qac_enter#aa"]],
        [['text' => "آموزش خرید از ربات", 'callback_data' => "qac_enter#bb"]],
        [['text' => "آموزش افزایش موجودی", 'callback_data' => "qac_enter#cc"]],
        [['text' => "آموزش مدیریت سرویس ", 'callback_data' => "qac_enter#dd"]],
        [['text' => "آموزش کسب درامد از ربات", 'callback_data' => "qac_enter#ff"]],
        [['text' => "↪️بازگشت", 'callback_data' => "backhelp"]]
    ];
    
    bot('editMessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text'=> "یکی از گزینه ها را انتخاب کنید",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}

if(preg_match('/help1center|hlpsee/',$cdata)){
    
    $keyboard =[
        [['text' => "آموزش اتصال اندروید", 'callback_data' => "helpcenter#android"]],
        [['text' => "آموزش اتصال آیفون", 'callback_data' => "helpcenter#ios"]],
        [['text' => "آموزش اتصال مک", 'callback_data' => "helpcenter#mac"]],
        [['text' => "آموزش اتصال ویندوز", 'callback_data' => "helpcenter#windows"]],
        [['text' => "↪️بازگشت", 'callback_data' => "backhelp"]]
    ];
    
    bot( ($cdata == 'hlpsee') ? 'sendMessage' : 'editMessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text'=> "یکی از گزینه ها را انتخاب کنید",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}

if(preg_match('/helpcenter/',$cdata)){
    $platform = explode('#',$cdata)[1];
    bot('editMessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text'=> $helpmsg_arr[$platform],
        'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => "↪️بازگشت", 'callback_data' => "backhelp"]]]
        ])
    ]);
}
if ($text == '👤پشتیبانی' or $text =='/support') {
    $state = file_put_contents('state/' . $userid . '.txt', 'support');
    $msg = '
با سلام خدمت شما کاربر گرامی 
پشتیبانی ۲۴ ساعته با  آی دی زیر 👇
'.$supportus.'
یا پیام خود را اینجا بفرستید. سعی ما بر این است که هر چه سریعتر با آن پاسخ دهیم:';
    $telegram->sendMessageCURL($userid, $msg, $cancelop);exit;
}
if($text!='❌ انصراف' and $state=='support'){
    if(strlen($text) < 3) {
        $telegram->sendMessage($userid,'لطفا متن پیام صحیح را با طول کاراکتر حداقل 3 وارد کنید');
        exit;
    }
    $user = $telegram->db->query("select * from fl_user where userid='$userid'")->fetch(2);
    if(!$user){
        $telegram->sendMessage($userid,'اطلاعات شما در سیستم یافت نشد. لطفا مجدد /start بزنید');
        exit;
    }
    $uid = $user['userid'];
	$status = $user['status'] ? '✅' : '☑';
	$orders = $telegram->db->query("select * from fl_order where userid='".$user['userid']."' and status=1")->fetchAll(2);
	$orders_count = count($orders);
	
	$list = $telegram->db->query("select * from fl_subuser where  toplevel_userid=".$user['userid'])->fetchAll(2);
    $list_count = count($list);
    
    $free = file_get_contents("state/{$uid}-free.txt");
    $free_count = ($free == '') ? 0 : $free - 1;
        
    file_put_contents('state/'.$userid.'.txt','');
    $telegram->sendMessageCURL($userid,'❇️پیام شما با موفقیت برای پشتیبانی ارسال شد. پیام شما بزودی بررسی و از طریق همین ربات اطلاع رسانی می شود',$finalop);
    $msg = "
➖id : <code>".$user['userid']."</code>
➖name : <b>".$user['name']."</b>
➖username : <code>".$user['username']."</code>
➖tel : <b>+".$user['tel']."</b>
➖Subs : $list_count /guslst".$user['userid']."
➖status : $status /banusr".$user['id']."
➖free : $free_count /chfrcnt".$user['userid']."
➖orders : <b>$orders_count</b> /getuord".$user['userid']."
➖wallet : <b>".number_format($user['wallet'])."</b> /waladd".$user['id']."
✍ Message : <b>$text</b>
";
    $admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3){
            //$telegram->forwardmessage($admid,$userid,$msgid);
            bot('sendmessage', [
                'chat_id' => $admid,
                'parse_mode' => "HTML",
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => [[ ['text' => "ارسال پاسخ", 'callback_data' =>"replyusr#".$user['userid'] ] ]]
                ])
            ]);
        }
    }
    file_put_contents('state/' . $userid . '.txt', '');
    //$telegram->forwardmessage(ADMIN,$userid,$msgid);
    bot('sendmessage', [
        'chat_id' => ADMIN,
        'parse_mode' => "HTML",
        'text' => $msg,
        'reply_markup' => json_encode([
            'inline_keyboard' => [[ ['text' => "ارسال پاسخ", 'callback_data' =>"replyusr#".$user['userid'] ] ]]
        ])
    ]);
    
}

if(preg_match('/guslst/', $text) and ($userid==ADMIN or isAdmin() ) )  {
    $uid = str_replace('/guslst','', $text);
    $list = $telegram->db->query("select * from fl_subuser where  toplevel_userid='$uid'")->fetchAll(2);
    if(empty($list)){
        $telegram->sendMessage($userid, 'لیست خالی است');exit;
    }else{
        $count = 0;
        foreach ($list as $sb) {
            $uname = $sb['fname'];
            $tel = $sb['tel'];
            $uuid = $sb['userid'];
            $msg .= "#$count | $uname | +$tel | $uuid \n";
            $count++;
        }
        $telegram->sendMessage($userid, $msg);
    }
}
if(preg_match('/replyusr/',$cdata) and ($userid == ADMIN or isAdmin() ) ){
    file_put_contents('state/'.$userid.'.txt',$cdata);
    $msg = "متن پیام خود را وارد کنید";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);exit;
}

if(preg_match('/replyusr/',$state) and ($userid == ADMIN or isAdmin() ) and $text!='❌ انصراف'){
    $uid = str_replace('replyusr#','',$state);
    file_put_contents('state/'.$userid.'.txt','');
    
    $telegram->sendMessage($uid,$text);
    $telegram->sendMessageCURL($userid,'پیام شما با موفقیت به کاربر ارسال شد.',$adminop);exit;
}
if($text == '🔐ادمین ها' and ($userid==ADMIN or isAdmin() )){
    $admins = file_get_contents('admins.php');
    $list = explode('\n',$admins);
    file_put_contents('state/' . $userid . '.txt', 'admin');
    $telegram->sendHTML($userid, "📝 لیست کاربران مدیر به صورت زیر است:
<b>$admins</b>
⚠️اگر قصد عزل یکی از کاربران این لیست را دارید
❇️یا اضافه کردن یک کاربر به عنوان ادمین را دارید, کافیست که آی دی عددی را همین جا ارسال کنید", [['↪️بازگشت']]);

    exit;
}
if ($state == 'admin' and $text != '↪️بازگشت' ) {
    if(is_numeric($text) and strlen($text)>4){
        file_put_contents('state/' . $userid . '.txt', '');
        $admins = file_get_contents('admins.php');
        if(!preg_match("/$text/",$admins)) {
            file_put_contents('admins.php',"\n".$text,FILE_APPEND);
            $msg = 'کاربر به دسترسی مدیریت ارتقا یافت';
        } else{
            $str = str_replace($text,'',$admins);
            //$str=str_replace("\n","",$str);
            file_put_contents('admins.php',$str);
            $msg = 'کاربر از لیست مدیران ربات حذف شد';
        };
        $telegram->sendHTML($userid,$msg,$adminop);
    }else{
        $telegram->sendMessage($userid, 'لطفا یک آی دی عددی و صحیح ارسال کنید');
    }
}
if($text == '💎دریافت نرم افزار یا اپلیکیشن' or $text == '/download') {
    $respd = $telegram->db->query("select * from fl_software WHERE status=1")->fetchAll(2);
    $keyboard = [];
    foreach($respd as $file){
        $link = $file['link'];
        $title = $file['title'];
        $keyboard[] = ['text' => "$title", 'url' => $link];
    }
    $keyboard = array_chunk($keyboard,1);
    bot('sendmessage', [
        'chat_id' => $userid,
        'text' => "
🔰لیست نرم افزار ها به شرح زیر است لطفا یکی از موارد را انتخاب کنید

🔸می توانید به راحتی همه فایل ها را (به صورت رایگان) دریافت کنید
.",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if($text == '📥 کسب درآمد' or $text == '/referer_link'){
    $code = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2)['refcode'];
    $count = $telegram->db->query("select * from fl_subuser where refcode='$code'")->rowCount();
    $msg = "
➕تعداد زیرمجموعه ها : $count

🌟لینک دعوت را با دوستان خود به اشتراک بزارید و به ازای هر خرید %$pursant از مبلغ آن به کیف پول شما اضافه می شود تا بتوانید محصولات داخل فروشگاه را بدون پرداخت هزینه دریافت کنید


";

    $keyboard = [
		[['text' => "دریافت لینک دعوت🔗", 'callback_data' => "gtlnk"]],
		[['text' => "🗒لیست زیرمجموعه ها", 'callback_data' => "gtsblt"]]
	];
    
    bot('sendmessage',[
        'chat_id' => $userid,
        'text' => $msg,
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
    
}
if($cdata == 'gtlnk'){
    $code = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2)['refcode'];
    $telegram->sendMessage($userid, "https://t.me/".botid."?start=$code");
}
if($text == '/getsblst' or $cdata == 'gtsblt'){
    $list = $telegram->db->query("select * from fl_subuser where  toplevel_userid = '$userid' ")->fetchAll(2);
    if(empty($list)){
        $telegram->sendMessage($userid, 'لیست خالی است');exit;
    }else{
        $count = 0;
        foreach ($list as $sb) {
            $uname = $sb['fname'];
            $msg .= "$count - $uname \n";
            $count++;
        }
        $telegram->sendMessage($userid, $msg);
    }
}
if($text=='💰کیف پول' or $text == '/wallet'){
    
$wallet = $telegram->db->query("SELECT * from `fl_user` WHERE userid=$userid")->fetch(2)['wallet'];
$ttl = 0;
$product = '';
$ttl += $wallet;
$product .= "
  💸 موجودی کل : ".number_format($ttl)." تومان ";

if($ttl == 0) $product= '🔻موجودی کیف پول شما صفر است ';

$telegram->sendAction($userid,'typing');

    $keyboard[] = [['text' => "افزایش موجودی", 'callback_data' => "addwalet"]];
    bot('sendmessage',[
        'chat_id' => $userid,
        'text'=> $product,
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if($cdata=='addwalet'){
    $state = file_put_contents('state/'.$userid.'.txt','addwalet');
    $msg = '🔻لطفا مبلغی که قصد شارژ حساب خود دارید را به تومان و اعداد لاتین وارد کنید.'; 
    $telegram->sendMessageCURL($userid,$msg,[['❌ انصراف']]);
}

if($state == 'addwalet' and $text != '❌ انصراف'){
    if(intval($text) and $text > $min_wallet_charge){
        $state = file_put_contents('state/'.$userid.'.txt','');
        $amount = number_format($text);
        $telegram->sendMessageCURL($userid,'برای پرداخت روی دکمه پایین بزنید :',$finalop);
        if($gateways['bahamta']) $keyboard[] = [['text' => "پرداخت آنلاین - $price تومان", 'url' => baseURI."bahamta.php?type=wallet&action=pay&token=$token"]];
        if($gateways['zarin']) $keyboard[] = [['text' => "درگاه زرین پال", 'url' => baseURI."/wallet/pay.php?userid=$userid&amount=$text"]];
        if($gateways['next']) $keyboard[] = [['text' => "درگاه نکست پی", 'url' => baseURI."/wallet/next/pay.php?userid=$userid&amount=$text"]];
        if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت",  'callback_data' => "crdwll#$text"]];
        
        $aa = bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> "لینک پرداخت آنلاین برای شارژ حساب به مبلغ $amount تومان ایجاد شد :",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
       // $telegram->sendMessage($userid,json_encode($aa));
        
    }else {
        $telegram->sendMessage($userid,"لطفا مبلغ را به تومان و بیشتر از $min_wallet_charge تومان وارد کنید");exit;
    }
    exit;
}
if(preg_match('/crdwll/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"<b>صورت حساب شما با موفقیت ایجاد شد😇
لطفا مبلغ مورد نظر را به حساب زیر واریز کنید🙏</b>

☘ $cardinfo ☘

<blockquote>این فاکتور فقط تا نیم ساعت اعتبار دارد</blockquote>
<blockquote>پس از ارسال رسید خرید ها توسط ادمین تایید میشود</blockquote>
<blockquote>با دقت خرید کنید امکان برداشت وجه نیست</blockquote>

پس از پرداخت موفق <b>تصویر فیش واریز</b> را ارسال کنید",$cancelop);
    exit;
}
if(preg_match('/crdwll/',$state) and $text != '❌ انصراف'){
    $input = explode('#',$state);
    $amount = $input[1];
    file_put_contents("state/$userid.txt",'');
    $res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $uid = $res['userid'];
    $name = $res['name'];
    $tel = $res['tel'];
    $username = $res['username'];
    
    $price = number_format($amount);

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption (فیش واریزی در بالای این پیام هست)";
    $msg = "
✅✅درخواست افزایش موجودی شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش، موجودی شما به مبلغ $price تومان شارژ و از طریق ربات اطلاع رسانی می شود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷افزایش موجودی کاربر $name
✖کد کاربری: $userid
📧یوزرنیم: @$username
☎️شماره موبایل : $tel
مبلغ درخواستی: $price تومان
📝اطلاعات پرداخت کارت به کارت: $infoc
 ";
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'تایید پرداخت', 'callback_data' => "aducash#$uid#$amount"],
				['text' => 'عدم تایید', 'callback_data' => "disable#$uid#wallet$amount"]
            ],
            [
                ['text' => 'مبلغ دلخواه', 'callback_data' => "cuscash#$uid"],
            ]
        ]
    ]);
    $uniqmsgid = time().rand(0,99999); 
    $admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3){
			if($fileid) bot('sendphoto',['chat_id' => $admid, 'caption'=> '','photo' => $fileid]);
            $msgres = bot('sendmessage',[
                'chat_id' => $admid,
                'text'=> $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
            $msgresid = $msgres->result->message_id;
            $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', $admid, '$msgresid', 0, $time)");
        }
    }
	if($fileid) bot('sendphoto',['chat_id' => ADMIN, 'caption'=> '','photo' => $fileid]);
    $msgres = bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
    $msgresid = $msgres->result->message_id;
    $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', ".ADMIN.", '$msgresid', 0, $time)");
}

if(preg_match('/cuscash/',$cdata) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/{$userid}.txt","$cdata#$cmsgid");
    $telegram->sendHTML($userid,"لطفا مبلغ دلخواه برای شارژ کیف پول کاربر را بصورت اعداد لاتین وارد کنید مثلا 25000",$cancelop);
}
if(preg_match('/cuscash/',$state) and $text != '❌ انصراف'){
    
    if(!is_numeric($text)) {
        $telegram->sendMessage($userid, 'لطفا یک مقدار عددی صحیح وارد کنید');die;
    }
    
    $input = explode('#',$state);
    $uid = $input[1];
    if(isset($input[2])) $cmsgid = $input[2];
    $amount = $text;
    $price = number_format($amount);
    $telegram->sendMessageCURL($userid,"موجودی کاربر به مقدار $price تومان شارژ شد",$finalop);
    // update button
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
    $telegram->db->query("update fl_user set wallet = wallet + $amount WHERE userid=$uid");
	$telegram->sendHTML($uid,"💹کاربر گرامی موجودی شما به مقدار $price تومان شارژ شد",$finalop);
	file_put_contents("state/{$userid}.txt","");
	
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid'];
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '✅ انجام شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        }
    }
}

if(preg_match('/aducash/',$cdata) and $text != '❌ انصراف'){
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid' and status=2")->fetch(2);
    if(!empty($res)){
        exit;
    }
    
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata);
    $uid = $input[1];
    $amount = $input[2];
    $price = number_format($amount);
    $telegram->sendMessageCURL($userid,"موجودی کاربر به مقدار $price تومان شارژ شد",$finalop);
    // update button
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
    $telegram->db->query("update fl_user set wallet = wallet + $amount WHERE userid=$uid");
	$telegram->sendHTML($uid,"💹کاربر گرامی موجودی شما به مقدار $price تومان شارژ شد",$finalop);
	
	$res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid'];
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        }
    }
	
}
if($text == 'کد تخفیف'  and ($userid==ADMIN or isAdmin() )){
    $res = $telegram->db->query("SELECT * FROM fl_discount WHERE active = 1");
    if($res->rowCount() == 0) {$telegram->sendMessage($userid,"لیست کد ها خالی است \n ========== \n افزودن کد جدید /addcode"); exit;}
    $msg = '';
    foreach ($res->fetchAll(2) as $code) {
        $id = $code['id'];
        $dcode = $code['code'];
        $min = number_format($code['min']);
        $max = number_format($code['max']);
        $count = $code['count'];
        $amount = $code['amount'];
        $owner = $code['userid'] == '' ? "<b>همه</b>" : "<code>{$code['userid']}</code>";
        $expire_date = $code['expire_date'] == 0 ? "<b>نامحدود</b>" : "<code>".date("Y-m-d",$code['expire_date'])."</code>";
        if($amount <= 100) {
            $amount = "$amount %";
        }else {
            $amount = number_format($amount)." تومان ";
        }
        $msg .= "
کد <code>$dcode</code>
تخفیف <b>$amount</b>
حداقل <b>$min</b>
حداکثر <b>$max</b>
تعداد <b>$count</b>
انقضا <b>$expire_date</b>
برای $owner /dcd$id
حذف /delcode$id
=============
";
        if(strlen($msg) > 3950){
            $telegram->sendHTML($userid,$msg,$adminop);
            $msg = '';
        }
    }
    $telegram->sendHTML($userid,$msg."افزودن کد جدید /addcode",$adminop);
}
if(preg_match('/dcd/',$text)){
    file_put_contents('state/'.$userid.'.txt',$text);
    $msg = "اگر می خواهید این کد را به کاربر خاصی اختصاص بدید, لطفا آی دی کاربر را وارد کنید در غیر اینصورت برای عمومی کردن آن عدد 0 لاتین را وارد کنید";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
}
if(preg_match('/dcd/',$state) and ($userid==ADMIN or isAdmin() ) and $text!='❌ انصراف'){
    $did = str_replace('/dcd','', $state);
    $owner = $text == '0' ? '' : $text;
    if($owner !=''){
        $user = $telegram->db->query("select * from fl_user where userid='$text'")->fetch(2);
        if(!$user){
            $telegram->sendMessage($userid,'کاربر مورد نظر یافت نشد');
            exit;
        }
    }

    file_put_contents('state/'.$userid.'.txt','');
    $telegram->db->query("update fl_discount set userid = '$owner' where id=$did");
    $telegram->sendMessageCURL($userid,"مالکیت کد تخفیف با موفقیت تغییر کرد",$adminop);
}
if(preg_match('/delcode/',$text) and ($userid==ADMIN or isAdmin() )){
    $id=str_ireplace('/delcode','',$text);
    $telegram->db->query("delete from fl_discount where id={$id}");
    $telegram->sendMessage($userid,"کد تخفیف با موفقیت حذف شد");
}

if(preg_match('/addcode/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendHTML($userid,"کد تخفیف را بصورت لاتین و مقدار تخفیف را با جداکننده - وارد کنید:
مثال:
<code>mycode-25-1000-2000-10-0-30-0</code>

مقدار اول (mycode) کد تخفیف
مقدار دوم (25) مقدار تخفیف
مقدار سوم (1000) حداقل
مقدار چهارم (2000) حداکثر
مقدار پنجم (10) تعدادمصرف
مقدار ششم (0) مالکیت کد (آیدی عددی کاربر یا برای عمومی شدن 0)
مقدار هفتم (30) تعداد روز انقضا و برای نامحدود 0 بزنید
مقدار هشتم ایدی سرور و اگر میخواید برای همه سرورها باشد 0 بزنید

اگر مقدار تخفیف را تا عدد 100 وارد کنید تخفیف بصورت درصدی محاسبه می شود و اگر از 100 بالاتر باشد مقدار تومانی از خرید کاربر کسر می شود
در صورتی که میخواید هر یک از مقادیر حداکثر و حداقل اعمال نشود آن را 0 قرار بدید
",$cancelop);exit;
}
if(preg_match('/addcode/',$state) and $text != '❌ انصراف'){
    $id = str_ireplace("/addcode",'', $state);
    $input = explode('-',$text);
    if(count($input) != 8) {$telegram->sendMessage($userid,"لطفا متن بالا را با دقت بخونید و فرمت درست را بفرستید ");exit; }
    $code = strtolower($input[0]);
    $amount = $input[1];
    $min = $input[2];
    $max = $input[3];
    $count = $input[4];
    $owner = $input[5] == '0' ? '' : $input[5];
    $days = $input[6];
    $expire = $days == 0 ? '0' : strtotime("+$days days");
    $sid = $input[7];
    $telegram->db->query("INSERT INTO `fl_discount` VALUES (NULL, '$code', '$amount',$min, $max,$count, '$owner', $expire, $sid, '1')");
    $telegram->sendMessageCURL($userid,"✅کد تخفیف با موفقیت اضافه شد", $adminop);
    file_put_contents("state/$userid.txt",'');
}
/* start extra */
if($text=='پلن زمانی' or $cdata == 'backday' and ($userid==ADMIN or isAdmin() )){
    $res = $telegram->db->query("select * from extra_day")->fetchAll(2);
    if(empty($res)){
       bot('sendmessage', [
            'chat_id' => $userid,
            'parse_mode' => "HTML",
            'text' => 'لیست پلن های زمانی خالی است ',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "افزودن پلن زمانی جدید", 'callback_data' =>"adddayplan"],]]
            ])
        ]);
        exit;
    }
    $keyboard = [];
    foreach($res as $cat){
        $id = $cat['id'];
        $title = $cat['volume'];
        $keyboard[] = ['text' => "$title", 'callback_data' => "daydetail#$id"];
    }
    $keyboard = array_chunk($keyboard,2);
    $keyboard[] = [['text' => "افزودن پلن زمانی جدید", 'callback_data' =>"adddayplan"]];
    $msg = ' 📍 برای دیدن جزییات پلن زمانی روی آن بزنید👇';
    
    if(isset($cdata) and $cdata=='backday') {
        bot('editMessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else { $telegram->sendAction($userid, 'typing');
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
    
    
    exit;
}
if($cdata=='adddayplan' and ($userid == ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",'adddayplan');
    $telegram->sendMessageCURL($userid,"تعداد روز و قیمت آن را بصورت زیر وارد کنید :
10-30000

مقدار اول مدت زمان (10) روز
مقدار دوم قیمت (30000) تومان
 ",$cancelop);exit;
}
if(preg_match('/adddayplan/',$state) and $text != '❌ انصراف') {
    $input = explode('-',$text); 
	if(count($input) != 2) {$telegram->sendmessage($userid, 'فرمت ارسالی صحیح نیست. لطفا متن بالا را مجدد بخوانید');exit;}
    $volume = intval($input[0]);
    $price = intval($input[1]);
    $telegram->db->query("insert into extra_day VALUES (NULL,$volume,$price)");
    $telegram->sendMessageCURL($userid,"پلن زمانی جدید با موفقیت اضافه شد",$adminop);
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/daydetail/', $cdata)){
    $id = str_replace('daydetail#','', $cdata);
    $pd = $telegram->db->query("SELECT * FROM `extra_day` WHERE id=$id")->fetch(2);
    if(empty($pd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "موردی یافت نشد",
            'show_alert' => false
        ]);exit;
    }else {
        $id=$pd['id'];
        $volume=$pd['volume'];
        $price=$pd['price'];
        $acount =$pd['acount'];
        $msg = "
▪️#$id
📡$volume روز /chpdaydy$id
💶قیمت $price تومان /chpddyp$id
❌حذف: /delddyp$id
";
       $keyboard = [[['text' => "↪ برگشت", 'callback_data' =>"backday"],]];
       $aa = bot('editmessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'parse_mode' => "HTML",
            'text' => $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
        }
    
}
if(preg_match('/delddyp/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/delddyp','',$text);
    $telegram->db->query("delete from extra_day where id={$fid}");
    $telegram->sendMessage($userid,"پلن موردنظر با موفقیت حذف شد");
}
if(preg_match('/chpddyp/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"قیمت جدید را وارد کنید:", $cancelop);exit;
}
if(preg_match('/chpddyp/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chpddyp','',$state);
    if(is_numeric($text)){
        $telegram->db->query("update extra_day set price='$text' where id={$pid}");
        $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
        file_put_contents("state/$userid.txt",'');
    }else{
        $telegram->sendMessage($userid,"یک مقدار عددی و صحیح وارد کنید");
    }
}
if(preg_match('/chpdaydy/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"روز جدید را وارد کنید:", $cancelop);exit;
}
if(preg_match('/chpdaydy/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chpdaydy','',$state);
    $telegram->db->query("update extra_day set volume=$text where id={$pid}");
    $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
    file_put_contents("state/$userid.txt",'');
}
/******* end */
if($text=='پلن حجمی' or $cdata == 'backvol' and ($userid==ADMIN or isAdmin() )){
    $res = $telegram->db->query("select * from extra_plan")->fetchAll(2);
    if(empty($res)){
       bot('sendmessage', [
            'chat_id' => $userid,
            'parse_mode' => "HTML",
            'text' => 'لیست پلن های حجمی خالی است ',
            'reply_markup' => json_encode([
                'inline_keyboard' => [[['text' => "افزودن پلن حجمی جدید", 'callback_data' =>"addvolumeplan"],]]
            ])
        ]);
        exit;
    }
    $keyboard = [];
    foreach($res as $cat){
        $id = $cat['id'];
        $title = $cat['volume'];
        $keyboard[] = ['text' => "$title", 'callback_data' => "voldetail#$id"];
    }
    $keyboard = array_chunk($keyboard,2);
    $keyboard[] = [['text' => "افزودن پلن حجمی جدید", 'callback_data' =>"addvolumeplan"]];
    $msg = ' 📍 برای دیدن جزییات پلن حجمی روی آن بزنید👇';
    
    if(isset($cdata) and $cdata=='backvol') {
        bot('editMessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else { $telegram->sendAction($userid, 'typing');
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
    
    
    exit;
}
if($cdata=='addvolumeplan' and ($userid == ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",'addvolplan');
    $telegram->sendMessageCURL($userid,"حجم و قیمت آن را بصورت زیر وارد کنید :
10-30000

مقدار اول حجم (10) گیگابایت
مقدار دوم قیمت (30000) تومان
 ",$cancelop);exit;
}
if(preg_match('/addvolplan/',$state) and $text != '❌ انصراف') {
    $input = explode('-',$text); 
	if(count($input) != 2) {$telegram->sendmessage($userid, 'فرمت ارسالی صحیح نیست. لطفا متن بالا را مجدد بخوانید');exit;}
    $volume = intval($input[0]);
    $price = intval($input[1]);
    $telegram->db->query("insert into extra_plan VALUES (NULL,$volume,$price)"); 
    $telegram->sendMessageCURL($userid,"پلن حجمی جدید با موفقیت اضافه شد",$adminop);
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/voldetail/', $cdata)){
    $id = str_replace('voldetail#','', $cdata);
    $pd = $telegram->db->query("SELECT * FROM `extra_plan` WHERE id=$id")->fetch(2);
    if(empty($pd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "موردی یافت نشد",
            'show_alert' => false
        ]);exit;
    }else {
        $id=$pd['id'];
        $volume=$pd['volume'];
        $price=$pd['price'];
        $acount =$pd['acount'];
        $msg = "
▪️#$id
📡حجم $volume گیگ /chpvvl$id
💶قیمت $price تومان /chpvlp$id
❌حذف: /delvl$id
";
       $keyboard = [[['text' => "↪ برگشت", 'callback_data' =>"backvol"],]];
       $aa = bot('editmessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'parse_mode' => "HTML",
            'text' => $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
        }
    
}
if(preg_match('/delvl/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/delvl','',$text);
    $telegram->db->query("delete from extra_plan where id={$fid}");
    $telegram->sendMessage($userid,"پلن موردنظر با موفقیت حذف شد");
}
if(preg_match('/chpvlp/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"قیمت جدید را وارد کنید:", $cancelop);exit;
}
if(preg_match('/chpvlp/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chpvlp','',$state);
    if(is_numeric($text)){
        $telegram->db->query("update extra_plan set price='$text' where id={$pid}");
        $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
        file_put_contents("state/$userid.txt",'');
    }else{
        $telegram->sendMessage($userid,"یک مقدار عددی و صحیح وارد کنید");
    }
}
if(preg_match('/chpvvl/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"حجم جدید را وارد کنید:", $cancelop);exit;
}
if(preg_match('/chpvvl/',$state) and $text != '❌ انصراف') {
    $pid=str_ireplace('/chpvvl','',$state);
    $telegram->db->query("update extra_plan set volume=$text where id={$pid}");
    $telegram->sendMessageCURL($userid,"✅عملیات با موفقیت انجام شد", $adminop);
    file_put_contents("state/$userid.txt",'');
}
/*end extra */
if($text=='👤 پیگیری افراد' and ($userid == ADMIN or isAdmin() )){
    file_put_contents('state/'.$userid.'.txt','uinfo');
    $msg = "لطفا آی دی عددی یا نام کاربر را وارد کنید";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
}
if($state=='uinfo' and ($userid == ADMIN or isAdmin() ) and $text!='❌ انصراف'){
	if(strlen($text) < 3) {
        $telegram->sendMessage($userid,'لطفا کاراکتر صحیح با حداقل 3  وارد کنید');
        exit;
    }
    $users = $telegram->db->query("select * from fl_user where userid='$text' OR name LIKE '%$text%'")->fetchAll(2);
    if(empty($users)){
        $telegram->sendMessage($userid,'کاربری یافت نشد');
        exit;
    }
    $msg = '';
    foreach ($users as $user){
		$status = $user['status'] ? '✅' : '☑';
		$uid = $user['userid'];
        $orders = $telegram->db->query("select * from fl_order where userid='".$user['userid']."' and status=1")->fetchAll(2);
        $orders_count = count($orders);
        
        $list = $telegram->db->query("select * from fl_subuser where  toplevel_userid=".$user['userid'])->fetchAll(2);
        $list_count = count($list);
        
        $free = file_get_contents("state/{$uid}-free.txt");
        $free_count = ($free == '') ? 0 : $free - 1;
        
        file_put_contents('state/'.$userid.'.txt','');
        $msg .= "
➖id : <code>".$user['userid']."</code>
➖name : <b>".$user['name']."</b>
➖username : <code>".$user['username']."</code>
➖tel : <b>+".$user['tel']."</b>
➖status : $status /banusr".$user['id']."
➖Subs : $list_count /guslst".$user['userid']."
➖free : $free_count /chfrcnt".$user['userid']."
➖orders : <b>$orders_count</b> /getuord".$user['userid']."
➖wallet : <b>".number_format($user['wallet'])."</b> /waladd".$user['id'].PHP_EOL;

        if(strlen($msg) > 3950){
            $telegram->sendHTML($userid,$msg,$adminop);
            $msg = '';
        }
    }
    $aa = $telegram->sendHTML($userid,$msg,$adminop);
    //$telegram->sendHTML($userid,json_encode($aa));

}
if(preg_match('/gusinf/',$cdata) and ($userid==ADMIN or isAdmin() )){
    $input = explode('#', $cdata);
    $uid = $input[1];
    $user = $telegram->db->query("select * from fl_user where userid='$uid'")->fetch(2);
    $status = $user['status'] ? '✅' : '☑';
    $orders_count = $telegram->db->query("select * from fl_order where userid='$uid' and status=1")->rowCount();
    
    $list = $telegram->db->query("select * from fl_subuser where  toplevel_userid=".$user['userid'])->fetchAll(2);
    $list_count = count($list);
    
    $free = file_get_contents("state/{$uid}-free.txt");
    $free_count = ($free == '') ? 0 : $free - 1;
    
    $free = file_get_contents("state/{$uid}-free.txt");
    $free_count = ($free == '') ? 0 : $free - 1;
        
    $msg .= "
➖id : <code>$uid</code>
➖name : <b>".$user['name']."</b>
➖username : <code>".$user['username']."</code>
➖tel : <b>+".$user['tel']."</b>
➖status : $status /banusr".$user['id']."
➖Subs : $list_count /guslst".$user['userid']."
➖free : $free_count /chfrcnt".$user['userid']."
➖orders : <b>$orders_count</b> /getuord".$user['userid']."
➖wallet : <b>".number_format($user['wallet'])."</b> /waladd".$user['id'].PHP_EOL;

    $telegram->sendHTML($userid,$msg,$adminop);
}
if($text == "🔎جستجو سفارش" and ($userid==ADMIN or isAdmin() ) )  {
    file_put_contents('state/' . $userid . '.txt', 'srchrmrk');
    $telegram->sendMessageCURL($userid, "⏪ ریمارک کانفیگ را ارسال کنید مثلا srv-50",$cancelop);exit;
}
if($state == 'srchrmrk' and $text != '❌ انصراف'){
    $result = $telegram->db->query("select * from fl_order where remark='$text' and status=1")->fetch();
    if(empty($result)){
        $telegram->sendMessage($userid,"موردی یافت نشد");exit;
    }else{
        $id = $result['id'];
        $remark = $result['remark'];
        $uid = $result['userid'];
        $server_id = $result['server_id'];
        $inbound_id = $result['inbound_id'];
        $telegram->sendMessageCURL($userid, "سفارش $remark یافت شد :",$adminop);
        $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
        $action = $panel_type == 'marzban' ? 'ordMRZtail' : 'svcdetadm';
            
        $keyboard = [[['text' => "$remark", 'callback_data' =>  "$action#$id#$uid#0"]]];
        $msg = ' 📍 برای دیدن مشخصات سرویس روی آن بزنید👇';
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
        file_put_contents('state/' . $userid . '.txt', '');
        exit;
    }
}
if( (preg_match('/getuord/',$text) or preg_match('/ordADMINpaginate|getuord/',$cdata)) and ($userid==ADMIN or isAdmin() )){  
    if(preg_match('/ordADMINpaginate/',$cdata)){
        $input = explode('#',$cdata);
        $newpage = $input[1];
        $uid = $input[2];
    }elseif(preg_match('/getuord/',$cdata)){
        $input = explode('#',$cdata);
        $uid = $input[1];
    }else $uid = str_ireplace('/getuord','', $text);
    
    
    $orders = $telegram->db->query("select * from fl_order where userid='$uid' and status=1")->fetchAll();
    if(empty($orders)){
        $telegram->sendMessage($userid,"لیست سفارش های کاربر خالی است");exit;
    }else{
        $results_per_page = 30;
        $number_of_result = $telegram->db->query("SELECT * FROM `fl_order` WHERE userid=$uid and status=1")->rowCount();
        $number_of_page = ceil ($number_of_result / $results_per_page);
        $page = (preg_match('/ordADMINpaginate/',$cdata)) ? $newpage : 1;
        $page_first_result = ($page-1) * $results_per_page;

        $orders = $telegram->db->query("SELECT * FROM `fl_order` WHERE userid=$uid and status=1 order by id DESC limit $page_first_result, $results_per_page")->fetchAll();
        if(empty($orders)){
            $telegram->sendMessage($userid, 'لیست سفارش ها خالی است.');
            exit;
        }
        $keyboard = [];
        foreach($orders as $order){
            $id = $order['id'];
            $remark = $order['remark'];
            $server_id = $order['server_id'];
            $inbound_id = $order['inbound_id'];
            $sres = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2);
            $stitle = $sres['title'];
            $flag = $sres['flag'];

            $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
            $action = $panel_type == 'marzban' ? 'ordMRZtail' : 'svcdetadm';
            $keyboard[] =
                ['text' => "$remark", 'callback_data' => "$action#$id#$uid#$page"];
            // ['text' => "#$server_id $stitle $flag", 'callback_data' => "not223hing"],

        }
        $keyboard = array_chunk($keyboard,2);

        /* Setup page vars for display. */
        $prev = $page - 1;      //previous page is page - 1
        $next = $page + 1;      //next page is page + 1
        $lastpage = ceil($number_of_page/$results_per_page);      //lastpage is = total pages / items per page, rounded up.
        $lpm1 = $lastpage - 1;                      //last page minus 1
        //$telegram->sendMessage($userid,"prev $prev next $next lastpage $lastpage page_first_result $page_first_result page $page number_of_page $number_of_page number_of_result $number_of_result");

        $buttons = [];
        //previous button
        if ($prev > 0) $buttons[] = ['text' => "◀", 'callback_data' => "ordADMINpaginate#$prev#$uid"];

        //next button
        if ($next > 0 and $page != $number_of_page) $buttons[] = ['text' => "▶", 'callback_data' => "ordADMINpaginate#$next#$uid"]; $keyboard[] = $buttons;

        $msg = ' 📍 برای نمایش اطلاعات سرویس روی آن بزنید👇';

        if(isset($cdata)) {
            bot('editMessageText', [
                'chat_id' => $userid,
                'message_id' => $cmsgid,
                'text'=> $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]);
        }else { $telegram->sendAction($userid, 'typing');
            bot('sendmessage',[
                'chat_id' => $userid,
                'text'=> $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]);
        }
        exit;
    }
}
if(preg_match('/svcdetadm/', $cdata)){
    $input = explode('#', $cdata);
    $id = $input[1];
    $uid = $input[2];
    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$id")->fetch(2);
    $sid = $order['server_id'];
    $remark = $order['remark'];
    //$inbound_id = $order['inbound_id'];
    $page = $input[3];
	
    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$id")->fetch(2);
    if(empty($order)){
        $telegram->sendMessage($userid,"موردی یافت نشد");exit;
    }else {
        $fid = $order['fileid'];
        $name = $order['remark'];

        $date = jdate("Y-m-d H:i",$order['date']);
        $expire_date = jdate(" H:i d-m-Y",$order['expire_date']);
        $remark = $order['remark'];
        $acc_link = $order['link'];
        $protocol = $order['protocol'];
        $server_id = $order['server_id'];
        $inbound_id = $order['inbound_id'];

        include_once('vray.php');
        $response = getList($server_id)->obj;
        if($inbound_id == 0) {
            foreach($response as $row){
                if($row->remark == $remark) {
                    $enable = $row->enable;
                    $total = $row->total;
                    $up = $row->up;
                    $down = $row->down;
                    $netType = json_decode($row->streamSettings)->network;
					
					$expire_date = jdate(" H:i d-m-Y",substr_replace($row->expiryTime, "", -3));
                    break;
                }
            }
        }else {
            foreach($response as $row){
                if($row->id == $inbound_id) {
                    $netType = json_decode($row->streamSettings)->network;
                    $clients = $row->clientStats;
                    foreach($clients as $client) {
                        if($client->email == $remark) {
                            $enable = $row->enable;
                            $total = $client->total;
                            $up = $client->up;
                            $down = $client->down;
                            // $expire_date = jdate("H:i Y-m-d",$client->expiryTime);
							$expire_date = jdate(" H:i d-m-Y",substr_replace($client->expiryTime, "", -3));
                            break;
                        }
                    }
                    if(is_null($total)){
                        $clients = $settings['clients'];
                            foreach($clients as $key => $client) {
                                if($client['email'] == $remark) {
                                    $total = $settings['clients'][$key]['totalGB'];
                                }
                            }
                    }
                    break;
                }
            }
        }

        $sres = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2);
        $stitle = $sres['title'];
        $flag = $sres['flag'];

        $leftgb = round( ($total - $up - $down) / 1073741824, 2) . " GB";
        $msg = "#$name \n UserID: $uid \n 🌐$stitle $flag\n📝 $date \n🔗<code>$acc_link</code>";
        $status_label = $enable ? '✅فعال' : '☑️غیرفعال';
        $keyboard = [
            [

                ['text' => " $leftgb حجم باقیمانده", 'callback_data' => "ds23432f"],
                ['text' => $netType. " نوع شبکه ", 'callback_data' => "4no4thi5ng"],
            ],
            [
                ['text' => " انقضا ⏰ ". $expire_date, 'callback_data' => "f5hed_id"],
            ],
            [
                ['text' => " $protocol پروتکل📡", 'callback_data' => "nrod1th6ing"],
				['text' => "❌حذف سرویس", 'callback_data' => "dlmysv#$id"]
            ],
        ];
        if($page != 0) $keyboard[] = [['text' => "↪ برگشت", 'callback_data' => "ordADMINpaginate#$page#$uid"]];

        bot('editmessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'parse_mode' => "HTML",
            'text' => $msg,
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}
if(preg_match('/dlusmysv/', $cdata)){
    if($gateways['delete_service'] == 0 and !is_null($gateways['delete_service'])) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "در حال حاضر امکان حذف سرویس نیست",
            'show_alert' => false
        ]);
        exit;
    }
    
    $id = str_replace('dlusmysv#','', $cdata);
    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$id")->fetch(2);
    $server_id = $order['server_id'];
    $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
    $keyboard = [[['text' => "☑️خیر", 'callback_data' => $panel_type == 'marzban' ? "ordMRZtail#$id" : "ordetail#$id"],['text' => "✅بله", 'callback_data' => "dlmysv#$id"]]];
            
    bot('editmessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => '⚠️بعد از حذف سرویس مبلغی به حساب کاربری شما عودت داده نمیشود',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]); 
}
if(preg_match('/dlmysv/', $cdata)){
    $id = str_replace('dlmysv#','', $cdata);
    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$id")->fetch(2);
    $remark = $order['remark'];
    $protocol = $order['protocol'];
    $server_id = $order['server_id'];
    $inbound_id = $order['inbound_id'];
    
    $panel_type = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['ptype'];
    if($panel_type == 'marzban'){
        require_once('marz.php');
        $response = mdelete_user($server_id, $remark);
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }

    	/*if($response->detail){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا در عملیات. مدیریت اطلاع بدید',
                'show_alert' => true
            ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $remark :".$response->detail);
            exit;
        }*/
    }else{
        require_once('vray.php');
        if($inbound_id > 0) {
           $response = remove_client($server_id, $inbound_id, $remark, 1);
            
        }else{
            $response = remove_inbound($server_id, $remark, 1);
        }
    }
    $telegram->db->query("DELETE FROM `fl_order` WHERE remark='$remark' ");
    bot('editmessageText', [
            'chat_id' => $userid,
            'message_id' => $cmsgid,
            'parse_mode' => "HTML",
            'text' => "سرویس $remark با موفقیت حذف شد",
        ]); 
    
}
if ($text == '📨 فوروارد همگانی' and ($userid == ADMIN or isAdmin() )){
    $state = file_put_contents('state/' . $userid . '.txt', 'f2a');
    $msg = "لطفا پیام خود ارسال کنید. ";
    $telegram->sendAction($userid, 'typing');
    $telegram->sendHTML($userid, $msg, $cancelop);
    exit;
}
if($state=='f2a' and $text!='❌ انصراف'){
    file_put_contents('state/'.$userid.'.txt','');
    $result = $telegram->db->query("select * from fl_user")->fetchAll();
    $telegram->sendMessageCURL($userid,'👍🏻✅ پیام شما با موفقیت به تمام کاربران ربات فروارد شد ',$adminop);
    foreach ($result as $user){
        if($user['userid']!=ADMIN){
            $telegram->forwardmessage($user['userid'],ADMIN,$msgid);
        }
    }
}
if($text=='📮 پیام به کاربر' and ($userid == ADMIN or isAdmin() )){
    file_put_contents('state/'.$userid.'.txt','msg');
    $msg = "لطفا آی دی کاربر دریافت کننده پیام را وارد کنید";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
}
if($state=='msg' and ($userid == ADMIN or isAdmin() ) and $text!='❌ انصراف'){
    $user = $telegram->db->query("select * from fl_user where userid='$text' ")->rowCount();
    if(!$user){
        $telegram->sendMessage($userid,'کاربر مورد نظر یافت نشد');
        exit;
    }
    file_put_contents('state/'.$userid.'.txt','sendmsg'.$text);
    $msg = "حالا پیام خود را وارد کنید";
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
}
if(preg_match('/sendmsg/',$state) and ($userid == ADMIN or isAdmin() ) and $text!='❌ انصراف'){
    $uid = str_replace('sendmsg','',$state);
    $user = $telegram->db->query("select * from fl_user where userid=$uid ")->fetch(2);
    if(!$user){
        $telegram->sendMessage($userid,'کاربر مورد نظر یافت نشد');
        exit;
    }
    $uid = $user['userid'];
    file_put_contents('state/'.$userid.'.txt','');
    
    $telegram->sendMessage($uid,$text);
    $telegram->sendMessageCURL($userid,'پیام شما با موفقیت به کاربر ارسال شد.',$adminop);exit;
}
if($text == '/id' or $text == '🆔 آیدی عددی من'){
    file_put_contents('state/' . $userid . '.txt', '');
    $telegram->sendHTML($userid, "آیدی عددی : <code>$userid</code>", $finalop);
    exit;
}
if(preg_match('/banusr/',$text) and ($userid==ADMIN or isAdmin() )){
    $id = str_replace('/banusr','', $text);
    $user = $telegram->db->query("select * from fl_user where id=$id")->fetch(2);
    if(!$user){
        $telegram->sendMessage($userid,'کاربر مورد نظر یافت نشد');
        exit;
    }
    $telegram->db->query("update fl_user set status = !status where id=$id");
    $telegram->sendMessage($userid,"وضعیت کاربر تغییر کرد");
}

if($text=='⚙️ تنظیمات' and ($userid==ADMIN or isAdmin() ) ){
    $keyboard =[
        [
            ['text' => ($botstatus == '' ? '✅' : '') ."On Bot", 'callback_data' => "onbot"],
            ['text' => ($botstatus == '' ? '' : '✅') ."Off Bot", 'callback_data' => "offbot"],
        ],
    ];
    bot('sendmessage',[
        'chat_id' => $userid,
        'text'=> "لطفا یکی از گزینه ها را انتخاب کنید",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/onbot|offbot/',$cdata) and ($userid==ADMIN or isAdmin() )){
    $botstatus = ($cdata == "onbot") ? '' : 'close';
    file_put_contents('botstatus',$botstatus);
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "تغییرات اعمال شد",
        'show_alert' => false
    ]);
    $keyboard =[
        [
            ['text' => ($botstatus == '' ? '✅' : '') ."On Bot", 'callback_data' => "onbot"],
            ['text' => ($botstatus == '' ? '' : '✅') ."Off Bot", 'callback_data' => "offbot"],
        ],
    ];
    bot('editMessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text'=> "لطفا یکی از گزینه ها را انتخاب کنید",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if($text=='🤖درگاه و امکانات‌' and ($userid==ADMIN or isAdmin() ) ){
    $gtw = $telegram->db->query("select * from gateway where id=1")->fetch(2);
    $keyboard =[
        [
            ['text' => ($gtw['zarin'] == 1 ? '✅' : '') ."زرین", 'callback_data' => "gtwa#zarin"],
            ['text' => ($gtw['next'] == 1 ? '✅' : '') ."نکست", 'callback_data' => "gtwa#next"],
        ],
        [
            ['text' => ($gtw['card'] == 1 ? '✅' : '') ."کارت", 'callback_data' => "gtwa#card"],
            ['text' => ($gtw['wallet'] == 1 ? '✅' : '') ."کیف پول", 'callback_data' => "gtwa#wallet"],
        ],
        [
            ['text' => ($gtw['buy'] == 1 ? '✅' : '') ."خرید", 'callback_data' => "gtwa#buy"],
			['text' => ($gtw['bahamta'] == 1 ? '✅' : '') ."باهمتا", 'callback_data' => "gtwa#bahamta"],
        ],
        [
            ['text' => ($gtw['change_location'] == 1 ? '✅' : '') ."تغییر لوکیشن", 'callback_data' => "gtwa#change_location"],
            ['text' => ($gtw['change_protocol'] == 1 ? '✅' : '') ."تغییر پروتکل", 'callback_data' => "gtwa#change_protocol"],
        ],
        [
            ['text' => ($gtw['buy_gb'] == 1 ? '✅' : '') ."خرید حجم", 'callback_data' => "gtwa#buy_gb"],
            ['text' => ($gtw['buy_day'] == 1 ? '✅' : '') ."خرید روز", 'callback_data' => "gtwa#buy_day"],
        ],
        [
            ['text' => ($gtw['renew'] == 1 ? '✅' : '') ."تمدید", 'callback_data' => "gtwa#renew"],
            ['text' => ($gtw['change_nettype'] == 1 ? '✅' : '') ."تغییر نوع شبکه", 'callback_data' => "gtwa#change_nettype"],
        ],
		[
            ['text' => ($gtw['delete_service'] == 1 ? '✅' : '') ."حذف سرویس", 'callback_data' => "gtwa#delete_service"],
        ],
        
    ];
    bot('sendmessage',[
        'chat_id' => $userid,
        'text'=> "با کلیک روی هر یک از گزینه ها میتوانید آن را فعال یا غیرفعال کنید",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/gtwa/',$cdata) and ($userid==ADMIN or isAdmin() )){
    $column = str_replace('gtwa#','',$cdata);
    $telegram->db->query("update gateway set $column = ! $column where id=1");
    $gtw = $telegram->db->query("select * from gateway where id=1 ")->fetch(2);
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "تغییرات اعمال شد",
        'show_alert' => false
    ]);
    $keyboard =[
        [
            ['text' => ($gtw['zarin'] == 1 ? '✅' : '') ."زرین", 'callback_data' => "gtwa#zarin"],
            ['text' => ($gtw['next'] == 1 ? '✅' : '') ."نکست", 'callback_data' => "gtwa#next"],
        ],
        [
            ['text' => ($gtw['card'] == 1 ? '✅' : '') ."کارت", 'callback_data' => "gtwa#card"],
            ['text' => ($gtw['wallet'] == 1 ? '✅' : '') ."کیف پول", 'callback_data' => "gtwa#wallet"],
        ],
        [
            ['text' => ($gtw['buy'] == 1 ? '✅' : '') ."خرید", 'callback_data' => "gtwa#buy"],
            ['text' => ($gtw['bahamta'] == 1 ? '✅' : '') ."باهمتا", 'callback_data' => "gtwa#bahamta"],
        ],
        [
            ['text' => ($gtw['change_location'] == 1 ? '✅' : '') ."تغییر لوکیشن", 'callback_data' => "gtwa#change_location"],
            ['text' => ($gtw['change_protocol'] == 1 ? '✅' : '') ."تغییر پروتکل", 'callback_data' => "gtwa#change_protocol"],
        ],
        [
            ['text' => ($gtw['buy_gb'] == 1 ? '✅' : '') ."خرید حجم", 'callback_data' => "gtwa#buy_gb"],
            ['text' => ($gtw['buy_day'] == 1 ? '✅' : '') ."خرید روز", 'callback_data' => "gtwa#buy_day"],
        ],
        [
            ['text' => ($gtw['renew'] == 1 ? '✅' : '') ."تمدید", 'callback_data' => "gtwa#renew"],
            ['text' => ($gtw['change_nettype'] == 1 ? '✅' : '') ."تغییر نوع شبکه", 'callback_data' => "gtwa#change_nettype"],
        ],
		[
            ['text' => ($gtw['delete_service'] == 1 ? '✅' : '') ."حذف سرویس", 'callback_data' => "gtwa#delete_service"],
        ],
        
    ];
    bot('editMessageText', [
        'chat_id' => $userid,
        'message_id' => $cmsgid,
        'text'=> "با کلیک روی هر یک از گزینه ها میتوانید آن را فعال یا غیرفعال کنید",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
/* version 1*/
if ($text == 'نمایندگی و همکاری 🤝' or $cdata=='ca1t'){
    $respd = $telegram->db->query("select * from fl_1cat WHERE parent=0")->fetchAll();
    if(empty($respd)){
        $telegram->sendMessage($userid, 'هیچ دسته بندی در ربات تعریف نشده است');
        exit;
    }
    $keyboard = [];
    foreach($respd as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $keyboard[] = ['text' => "$name", 'callback_data' => "li1st#$id"];
    }
    $keyboard = array_chunk($keyboard,1);
    if(isset($cdata) and $cdata=='ca1t') {
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text'=> ' 📍 لطفا یکی از دسته بندی های زیر را انتخاب کنید👇',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }else {
        bot('sendmessage',[
            'chat_id' => $userid,
            'text'=> ' 📍 لطفا یکی از دسته بندی های زیر را انتخاب کنید👇',
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}
if(preg_match('/li1st/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
    $respd = $telegram->db->query("select * from fl_1file WHERE catid='$id' and active=1")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "
💡پلنی در این دسته بندی وجود ندارد
        ",
            'show_alert' => false
        ]);
    }else{
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "
📍در حال دریافت لیست پلن ها
        ",
            'show_alert' => false
        ]);
        $keyboard = [];
        foreach($respd as $file){
            $id = $file['id'];
            $name = $file['title'];
            $keyboard[] = ['text' => "$name", 'callback_data' => "fi1le#$id"];
        }
        $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => "retailsrvc"];
        $keyboard = array_chunk($keyboard,1);
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text' => "
🔰 حالا یکی از موارد زیر را انتخاب کنید تا جزییات پلن برای شما نمایش داده شود👈
",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }

}
if(preg_match('/fi1le/',$cdata)){
    $input = explode('#', $cdata);
    $id = $input[1];
    $rcount = $telegram->db->query("select * from fl_accounts WHERE fid={$id} and active=1 and sold=0")->rowCount();
    if($rcount == 0) {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "در حال حاضر برای این پلن اکانت قابل فروشی وجود ندارد",
            'show_alert' => true
        ]);
        exit;
    }
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "
♻️در حال دریافت جزییات ...
        ",
        'show_alert' => false
    ]);
    $respd = $telegram->db->query("select * from fl_1file WHERE id='$id' and active=1")->fetch(2);
    $catname = $telegram->db->query("select * from fl_1cat WHERE id=".$respd['catid'])->fetch(2)['title'];
    $name = $catname." ".$respd['title'];
    $price = number_format($respd['price']);
    $des1c = $respd['descr'];
    $fileImg = $respd['pic']."?".rand(0,999999999);
    $fileImg = "<a href='".baseURI."/images/$fileImg'>&#8194;</a>";
    if($price == 0 or ($userid == ADMIN or isAdmin() )){
        $keyboard = [[['text' => '📥 دریافت رایگان', 'callback_data' => "down1load#$id"]]];
    }else{
        $token = base64_encode("{$cuserid}.{$id}");
		if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $price تومان", 'url' => baseURI."pay1.php?token=$token"]];
		if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."nx1pay.php?token=$token"]];
		if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "wal1pay#$id#".$respd['price']]];
		if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $price تومان",  'callback_data' => "off1pay#$id"]];
		
		$dcount = $telegram->db->query("select * from fl_discount WHERE active=1")->rowCount();
        if($dcount > 0){
            $keyboard[] = [['text' => '🔸کد تخفیف دارید؟ بزنید ', 'callback_data' => "submit1discount#$id"]];
        }
        
    }
	$keyboard[] = [['text' => '🔙 بازگشت', 'callback_data' => "li1st#".$respd['catid']]]; 
    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "
🔻عنوان :$name

📃توضیحات :
$des1c
$fileImg
",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/submit1discount/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendMessageCURL($userid,"کد تخفیف را وارد کنید :",$cancelop);
    exit;
}
if(preg_match('/submit1discount/',$state) and $text != '❌ انصراف'){
    $text = strtolower($text);
    $dcount = $telegram->db->query("select * from fl_discount WHERE code='$text' and active=1");
	if(!$dcount){
        $telegram->sendMessage($userid,"کد وارد شده اشتباه است❌");
    }else{
	  if($dcount->rowCount() > 0){
        $fid = str_replace('submit1discount#','', $state);
        $respd = $telegram->db->query("select * from fl_1file WHERE id='$fid' and active=1")->fetch(2);
        $name = $respd['title']; 
        $price = $respd['price'];
        $desc = $respd['descr'];
        $fileImg = $respd['pic']."?".rand(0,999999999);
        $fileImg = "<a href='".baseURI."/$fileImg'>&#8194;</a>";

        // discount
		$dres = $dcount->fetch(2);
		$min = $dres['min'];
		$max = $dres['max']; 
		$amount = $dres['amount'];
		$ownerid = $dres['userid'];
		$expire_date = $dres['expire_date'];

		if($ownerid != 0){
			if($ownerid != $userid){
				$telegram->sendMessage($userid,"شما امکان استفاده از این کد تخفیف را ندارید❌");
				exit;
			}
		}
		if($expire_date !=0 and $expire_date < $time){
			$telegram->sendMessage($userid,"مدت زمان استفاده از این کد به پایان رسیده است❌");
			exit;
		}

		if( ($price < $min and $min !=0) or ($price > $max and $max !=0) ){
			$telegram->sendMessage($userid,"کد تخفیف وارد شده برای این سفارش معتبر نمی باشد❌");
			exit;
		}

		if($amount <= 100) {
			$price = number_format( $price * (100-$amount)/100 );
			$amount = "$amount %";
		}else {
			$price = number_format( $price - $amount );
			$amount = number_format($amount)." تومان ";
		}
        $telegram->sendMessageCURL($userid,"کد تخفیف به مقدار $amount اعمال شد :",$finalop);
		file_put_contents("state/$userid.txt",'');
        if($price == 0 or ($userid == ADMIN or isAdmin() )){
            $keyboard = [[['text' => '📥 دریافت رایگان', 'callback_data' => "down1load#$fid#code"]]];
        }else{
            $token = base64_encode("{$userid}.{$fid}.{$text}");
            //if($gateways['zarin']) $keyboard[] = [['text' => "پرداخت زرین پال - $price تومان", 'url' => baseURI."pay1.php?token=$token"]];
            //if($gateways['next']) $keyboard[] = [['text' => "پرداخت نکست پی - $price تومان", 'url' => baseURI."nx1pay.php?token=$token"]];
            if($gateways['card']) $keyboard[] = [['text' => "کارت به کارت - $price تومان",  'callback_data' => "off1pay#$fid#$text"]];
            if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "wal1pay#$fid#$text"]];
        }
        bot('SendMessage', [
            'chat_id' => $userid,
            'parse_mode' => "HTML",
        'text' => "
🔻$name
💰قیمت : $price تومان
📃توضیحات :
$desc
$fileImg
",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);

	   }else{
			$telegram->sendMessage($userid,"کد وارد شده اشتباه است❌");
	   }
	}
    
}
if(preg_match('/wal1pay/',$cdata)) {
    $input = explode('#', $cdata);
    $fid = $input[1];
    if(!$input[2]) {
        $telegram->sendMessage($userid,"مجدد روی خرید کانفیگ بزنید");exit;
    }
    $dcode = $input[2];
    
    $file_detail = $telegram->db->query("select * from fl_1file WHERE id=$fid")->fetch(2);
    $price = $file_detail['price'];
    
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
    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }

    $res = $telegram->db->query("select * from fl_accounts where fid=$fid and sold=0 and active=1 order by id ASC")->fetch(2);
    if(empty($res)){
        $telegram->sendMessage($userid,'در حال حاضر هیچ اکانت قابل فروشی وجود ندارد');
        exit;
    }
    $accid = $res['id'];
    $text = $res['text'];
    $res = $telegram->db->query("select * from fl_1file where id=$fid")->fetch(2);
    $telegram->db->query("update fl_user set wallet = wallet - $price where userid='$userid'");
    $telegram->db->query("update fl_accounts set sold=$userid where id=$accid");
    //$telegram->sendMessage($userid,$text);
     $telegram->sendHTML($userid,"
✅پرداخت شما با موفقیت تکمیل شد
🗒اطلاعات اکانت شما به شرح زیر است:

$text

" ,$finalop);
}
if(preg_match('/off1pay/',$cdata)) {
    file_put_contents("state/$userid.txt",$cdata);
    $telegram->sendHTML($userid,"سلام عزیز به بخش واریز کارت به کارت خوش آمدید
    برای اضافه کردن موجودی مبلغ مورد نظر را به شماره کارت زیر واریز کنید سپس اسکرین شات فیش واریزی را در همین صفحه ارسال کنید
    تا ارسال نکردن فیش واریزی از این صفحه خارج نشوید اگر قصد لغو داشتید از دکمه ی انصراف استفاده کنید.

🔸$cardinfo",$cancelop);
    exit;
}
if(preg_match('/off1pay/',$state) and $text != '❌ انصراف'){
    $input = explode('#',$state);
    $fid = $input[1];
    $dcode = $input[2];
    file_put_contents("state/$userid.txt",'');
    $res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $uid = $res['userid'];
    $name = $res['name'];
    $username = $res['username'];

    $res = $telegram->db->query("select * from fl_1file where id=$fid")->fetch(2);
    $catname = $telegram->db->query("select * from fl_1cat where id=".$res['catid'])->fetch(2)['title'];
    $filename = $catname." ".$res['title']; $fileprice = $res['price'];
    
    
    if($dcode){
        $dcount = $telegram->db->query("select * from fl_discount WHERE code='$dcode' and active=1");
        if($dcount->rowCount() > 0){
            $amount = $dcount->fetch(2)['amount'];
            if($amount <= 100) {
                $fileprice = $fileprice * (100-$amount)/100;
            }else {
                $fileprice = $fileprice - $amount ;
            }
        }
    }

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption <a href='$fileurl'>&#8194;نمایش فیش</a>";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش, اطلاعات اکانت از طریق ربات برای شما ارسال می شود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷سفارش جدید خرید $filename ($fileprice تومان)
✖کد کاربری : $userid
👤نام و نام خانوادگی : $name
📧یوزرنیم : @$username
📝اطلاعات پرداخت کارت به کارت: $infoc
.";
    $keyboard = json_encode([
        'inline_keyboard' => [
            [
                ['text' => 'تایید پرداخت', 'callback_data' => "enab1le#$uid#$fid"],
                ['text' => 'عدم تایید', 'callback_data' => "disable#$uid"]
            ]
        ]
    ]);
    
    $uniqmsgid = time().rand(0,99999); 
    $admins = file_get_contents('admins.php');
    $list = explode(PHP_EOL,$admins);
    foreach($list as $admid){
        if(strlen($admid) > 3){
            if($fileid) bot('sendphoto',['chat_id' => $admid, 'caption'=> '','photo' => $fileid]);
            $msgres = bot('sendmessage',[
                'chat_id' => $admid,
                'text'=> $msg,
                'parse_mode' => 'HTML',
                'reply_markup' => $keyboard
            ]);
            $msgresid = $msgres->result->message_id;
            $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', $admid, '$msgresid', 0, $time)");
        }
    }
    if($fileid) bot('sendphoto',['chat_id' => ADMIN, 'caption'=> '','photo' => $fileid]);
    $msgres = bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => $keyboard
    ]);
    $msgresid = $msgres->result->message_id;
    $telegram->db->query("INSERT INTO verifylogs VALUES (NULL, '$uniqmsgid', ".ADMIN.", '$msgresid', 0, $time)");
}
if($text == '♻️تمدید اکانت'){
    file_put_contents("state/$userid.txt","rene1wacc");
    $telegram->sendMessageCURL($userid,"لطفا ابتدا آخرین تعرفه اکانت مورد نظر را که قبلا خرید کردید را از بخش فروشگاه چک کنید و بعد مبلغ را به شماره کارت زیر واریز کنید

🔸$cardinfo
    
بعد اطلاعات اکانت‏ی که قبلا از طریق ربات خرید کردید و قصد تمدید آن را دارید به همراه کد پیگیری و زمان پرداخت و مبلغ فیش ارسال کنید تا تمدید اکانت شما انجام شود "
    ,$cancelop);
}
if($state == 'rene1wacc' and $text != '❌ انصراف'){
    file_put_contents("state/$userid.txt",'');
    $res = $telegram->db->query("select * from fl_user where userid=$userid")->fetch(2);
    $uid = $res['userid'];
    $name = $res['name'];
    $username = $res['username'];

    $fileurl = $telegram->FileURL($fileid);
    $infoc = strlen($text) > 1 ? $text : "$caption <a href='$fileurl'>&#8194;نمایش فیش</a>";
    $msg = "
✅✅درخواست شما با موفقیت ارسال شد
بعد از بررسی و تایید فیش, اکانت شما تمدید و از طریق ربات اطلاع رسانی میشود.
/start";
    $telegram->sendMessageCURL($userid,$msg,$finalop);
    // notify admin
    $msg = "
🏷سفارش جدید تمدید اکانت
✖کد کاربری : $userid
👤نام و نام خانوادگی : $name
📧یوزرنیم : @$username
📝اطلاعات اکانت و فیش پرداختی: $infoc
.";
    bot('sendmessage',[
        'chat_id' => ADMIN,
        'text'=> $msg,
        'parse_mode' => 'HTML',
        'reply_markup' => json_encode([
            'inline_keyboard' => [
                [
                    ['text' => 'تایید پرداخت', 'callback_data' => "enab1le#$uid"],
                    ['text' => 'عدم تایید', 'callback_data' => "d1isable#$uid"]
                ]
            ]
        ])
    ]);
}
if(preg_match('/enab1le/',$cdata) and ($userid == ADMIN or isAdmin())){
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid' and status=2")->fetch(2);
    if(!empty($res)){
        exit;
    }
    
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata);
    $uid = $input[1];
    $fid = $input[2];
    $acctxt = ''; 
    
    $res = $telegram->db->query("select * from fl_accounts where fid=$fid and sold=0 and active=1 order by id ASC")->fetch(2);
    if(empty($res)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'در حال حاضر هیچ اکانتی برای ارسال وجود ندارد',
            'show_alert' => false
        ]);
        exit;
    }
    
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([ 
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid']; 
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        } 
    }
    $telegram->sendMessageCURL($userid,'اطلاعات اکانت با موفقیت برای کاربر ارسال شد',$finalop);
    
    $accid = $res['id'];
    $text = $res['text'];
    $telegram->db->query("update fl_accounts set sold=$uid where id=$accid");
    
    $telegram->sendHTML($uid,"اطلاعات اکانت برای سفارش با کارت به کارت به شرح زیر است :
$text",$finalop);

}
if(preg_match('/d1isable/',$cdata) and ($userid == ADMIN or isAdmin())){
    file_put_contents("state/{$userid}.txt",$cdata);
    $telegram->sendMessageCURL($userid,'لطفا دلیل عدم تایید تراکنش را وارد کنید (این متن برای مشتری ارسال می شود) ',$cancelop);
}
if(preg_match('/d1isable/',$state) and $text != '❌ انصراف'){
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$state);
    $uid = $input[1];
    $telegram->sendMessageCURL($userid,'متن پیام با موفقیت برای مشتری ارسال شد',$finalop);
    $telegram->sendMessage($uid,$text);
    exit;
}
if(preg_match('/down1load/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
	
	$free = file_get_contents("state/{$userid}-1free.txt");
    if($free == '1' and !($userid == ADMIN or isAdmin() )){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '⚠️شما قبلا هدیه رایگان خود را دریافت کردید',
            'show_alert' => false
        ]); 
        exit;
    }else {
        file_put_contents("state/{$userid}-1free.txt","1");
    }
	
    $respd = $telegram->db->query("select * from fl_accounts WHERE fid={$id} and active=1 and sold=0")->fetch(2);
	if(empty($respd)){
        $telegram->sendMessage($userid,'در حال حاضر هیچ اکانت قابل فروشی وجود ندارد');
        exit;
    }
    $acc_text = $respd['text'];
    $acc_id = $respd['id'];
    //$fileLink = "<a href='http://dfsd.ir/$filelink'>&#8194;</a>$name";
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => '♻️در حال ارسال اکانت ...',
        'show_alert' => false
    ]);
    $telegram->sendHTML($cuserid,$acc_text,$finalop);
    $telegram->db->query("update fl_accounts set sold=$userid WHERE id={$acc_id}");
	// update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([ 
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
    /*bot('senddocument',[
        'chat_id' => $cuserid,
        'document' => $fileid,
        'caption' => $name
    ]);*/
}
if ($text == '➕ثبت پلن 1' and ($userid == ADMIN or isAdmin() )){
    $state = file_put_contents('state/'.$userid.'.txt','add1product');
    $telegram->db->query("delete from fl_1file WHERE active=0");
    $sql = "INSERT INTO `fl_1file` VALUES (NULL, '', 0, '', 0, '', '',0,1, '$time');";
    $telegram->db->query($sql);
    $msg = '◀️ لطفا عنوان پلن را وارد کنید';
    $telegram->sendMessageCURL($userid,$msg,$cancelop);
    exit;
}
// add product
if(preg_match('/add1product/',$state) and $text!='❌ انصراف'){

    $catkey = [];
    $cats = $telegram->db->query("SELECT * FROM `fl_1cat`")->fetchAll();
    foreach ($cats as $cat){
        $id = $cat['id'];
        $name = $cat['title'];
        $catkey[] = ["$id - $name"];
    }
    $catkey[] = ['❌ انصراف'];

    $step = $telegram->checkStep('fl_1file');
    if($step==1 and $text!='❌ انصراف'){
        $msg = '✅عنوان پلن با موفقیت ثبت شد
◀️ لطفا قیمت پلن را به تومان وارد کنید
* عدد 0 به معنای رایگان بودن است.
';
        if(strlen($text)>1){
            $telegram->db->query("update fl_1file set title='$text',step=2 where active=0 and step=1");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 1
    if($step==2 and $text!='❌ انصراف'){
        $msg = '✅قیمت پلن با موفقیت ثبت شد . 
◀️ لطفا دسته بندی پلن را انتخاب کنید
.';
        if(is_numeric($text)){
            $telegram->db->query("update fl_1file set price='$text',step=3 where step=2");
            $telegram->sendMessageCURL($userid,$msg,$catkey);
        }else{
            $msg = '‼️ لطفا یک مقدار عددی وارد کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 2
    if($step==3 and $text!='❌ انصراف'){
        $msg = '✅دسته بندی پلن موفقیت ثبت شد . 
◀️ لطفا توضیحات پلن را وارد کنید
.';
        $inarr = 0;
        foreach ($catkey as $op) {
            if (in_array($text, $op) and $text != '❌ انصراف') {
                $inarr = 1;
            }
        }
        if( $inarr==1 ){
            $input = explode(' - ',$text);
            $catid = $input[0];
            $telegram->db->query("update fl_1file set catid='$catid',step=4 where step=3");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }else{
            $msg = '‼️ لطفا فقط یکی از گزینه های پیشنهادی زیر را انتخاب کنید';
            $telegram->sendMessageCURL($userid,$msg,$catkey);
        }
    } //step 3
    if($step==4 and $text!='❌ انصراف'){
        $msg = '✅توضیحات پلن با موفقیت ثبت شد . 
◀️ لطفا تصویر یا پیشنمایش را بصورت عکس ارسال کنید
.';
        if(strlen($text)>1 ){
            $telegram->db->query("update fl_1file set descr='$text',step=5 where step=4");
            $telegram->sendMessageCURL($userid,$msg,$imgop);
        }

    } //step 4
    if($step==5 and $text!='❌ انصراف'){
        if($text != 'رد کردن این مرحله'){$imgtxt = '✅پیشنمایش  با موفقیت ثبت شد . ';}
        $msg = $imgtxt.' 
◀ حالا️ اکانت های این پلن  را بصورت زیر ارسال کنید
دقت کنید که تمامی اطلاعات اکانت را با عبارت seprator از هم جدا کنید 

توجه کنید که اگر میخواهید قابل کلیک باشد آن را به اینصورت وارد کنید :
<code>شارژ</code>
کلمه شارژ برای کاربر قابل کلیک خواهد شد

username: Test password: pwd...

seprator

link or vmess or giftcode or anything...


اگر تعداد اکانت ها زیاد است آن را با فرمت بالا در یک فایل .txt ارسال کنید
';
        if($text == 'رد کردن این مرحله'){
            $telegram->db->query("update fl_1file set step=6 where step=5");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }elseif($fileid){
            $photoURL = $telegram->FileURL($fileid);
            $photoext = pathinfo(basename($photoURL),PATHINFO_EXTENSION);
            $image = "images/".time().".$photoext";
            $somecontent = get_web_page($photoURL."?".rand(0,999999999));
            $handle = fopen($image,"x+");
            fwrite($handle,$somecontent);
            fclose($handle);

            $telegram->db->query("update fl_1file set pic='$image',step=6 where step=5");
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }else{
            $msg = '‼️ لطفا تصویر را ارسال کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 5
    if($step==6 and $text!='❌ انصراف'){
		if($fileid){
            $textURL = $telegram->FileURL($fileid);
            $textext = pathinfo(basename($textURL),PATHINFO_EXTENSION);
            $text = get_web_page($textURL."?".rand(0,999999999));
        }
        if(preg_match('/seprator/',strtolower($text))){
            $telegram->db->query("update fl_1file set fileid='$fileid',active=1,step=10 where step=6");
            $id = $telegram->db->query("select * from fl_1file where active=1 order by id DESC limit 1")->fetch(2)['id'];

            $accs = explode('seprator',$text);
            foreach ($accs as $acc){
                if(strlen($acc) > 5)
                    $telegram->db->query("INSERT INTO `fl_accounts` (`id`, `fid`, `text`, `sold`, `active`) VALUES (NULL, $id, '$acc', '0', '1');");
            }
            $msg = "✅️ اکانت های این پلن  با موفقیت ثبت شد";
            $telegram->sendMessageCURL($userid,$msg,$finalop);
            file_put_contents('state/'.$userid.'.txt','');
        }else{
            $msg = '‼️ لطفا اکانت ها را با جداکننده معتبر ارسال کنید';
            $telegram->sendMessageCURL($userid,$msg,$cancelop);
        }
    } //step 6
}
// end add product
if($text=='مدیریت پلن ها 1' and ($userid==ADMIN or isAdmin() )){
    $res = $telegram->db->query("select * from fl_1file where active=1")->fetchAll();
    if(empty($res)){
        $msg = "موردی یافت نشد";
        $telegram->sendMessage($userid,$msg);
    }else {
        $product ='';
        foreach ($res as $pd){
            $id=$pd['id'];
            $name=$pd['title'];
            $price=$pd['price'];
            $accnum = $telegram->db->query("select * from fl_accounts where sold > 0 and fid=$id")->rowCount();
            $accdnum = $telegram->db->query("select * from fl_accounts where sold=0 and fid=$id")->rowCount();
            $product = "
▪️#$id
🔻نام : $name /chpn1m$id
💶قیمت : $price تومان /ch1pp$id
✴️ویرایش توضیحات : /des1c$id
❌حذف : /del1pd$id
تعداد اکانت های فروخته شده : $accnum
تعداد اکانت های باقیمانده : $accdnum
⚡دریافت لیست اکانت ها : /getli1stpd$id
📝افزودن اکانت جدید : /add1pd$id
=====";
            $telegram->sendMessage($userid,$product);
        }
    }
}
if(preg_match('/getli1stpd/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/getli1stpd','',$text);
    $res = $telegram->db->query("select * from fl_accounts where fid={$fid}")->fetchAll();
    $txt = '';
    foreach ($res as $acc){
        $sold = $acc['sold'] == '1' ? 'SOLD' : 'OK';
        $accid = $acc['id'];
        $txt = $acc['text']." \n $sold | ❌ /delacc$accid \n =========== \n";
		$telegram->sendMessage($userid,$txt);
    }
    //$telegram->sendMessage($userid,$txt);
}
if(preg_match('/delacc/',$text) and ($userid==ADMIN or isAdmin() )){
    $aid=str_ireplace('/delacc','',$text);
    $telegram->db->query("delete from fl_accounts where id={$aid}");
    $telegram->sendMessage($userid,"اکانت موردنظر با موفقیت حذف شد");
}
if(preg_match('/add1pd/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessageCURL($userid,"اکانت ها  را بصورت زیر ارسال کنید
دقت کنید که تمامی اطلاعات اکانت را با عبارت seprator از هم جدا کنید 

توجه کنید که اگر میخواهید قابل کلیک باشد آن را به اینصورت وارد کنید :
<code>شارژ</code>
کلمه شارژ برای کاربر قابل کلیک خواهد شد

username: Test password: pwd...

seprator

link or vmess or giftcode or anything...

اگر تعداد اکانت ها زیاد است آن را با فرمت بالا در یک فایل .txt ارسال کنید

",$cancelop);exit;
}
if(preg_match('/add1pd/',$state)){
    $pid=str_ireplace('/add1pd','',$state);
    if($fileid){
        $textURL = $telegram->FileURL($fileid);
        $textext = pathinfo(basename($textURL),PATHINFO_EXTENSION);
        $text = get_web_page($textURL."?".rand(0,999999999));
    }
    if(preg_match('/seprator/',strtolower($text))){
        $accs = explode('seprator',$text); 
        foreach ($accs as $acc){
            if(strlen($acc) > 5)
                $telegram->db->query("INSERT INTO `fl_accounts` (`id`, `fid`, `text`, `sold`, `active`) VALUES (NULL, $pid, '$acc', '0', '1');");
        }
        $telegram->sendMessageCURL($userid,"✅اکانت های جدید با موفقیت اضافه شد",$finalop);
        file_put_contents('state/'.$userid.'.txt','');
    }else{
        $msg = '‼️ لطفا اکانت ها را با جداکننده معتبر ارسال کنید';
        $telegram->sendMessageCURL($userid,$msg,$cancelop);
    }
}

if(preg_match('/del1pd/',$text) and ($userid==ADMIN or isAdmin() )){
    $fid=str_ireplace('/del1pd','',$text);
    $telegram->db->query("delete from fl_1file where id={$fid}");
    $telegram->sendMessage($userid,"پلن موردنظر با موفقیت حذف شد");
}
if(preg_match('/chpn1m/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"نام جدید پلن را وارد کتید:");exit;
}
if(preg_match('/chpn1m/',$state)){
    $pid=str_ireplace('/chpn1m','',$state);
    $telegram->db->query("update fl_1file set title='$text' where id={$pid}");
    $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/des1c/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"توضیحات جدید را وارد کتید:");exit;
}
if(preg_match('/des1c/',$state)){
    $pid=str_ireplace('/des1c','',$state);
    $telegram->db->query("update fl_1file set descr='$text' where id={$pid}");
    $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/ch1pp/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"قیمت جدید را وارد کتید:");exit;
}
if(preg_match('/ch1pp/',$state)){
    $pid=str_ireplace('/ch1pp','',$state);
    if(is_numeric($text)){
        $telegram->db->query("update fl_1file set price='$text' where id={$pid}");
        $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
        file_put_contents("state/$userid.txt",'');
    }else{
        $telegram->sendMessage($userid,"یک مقدار عددی و صحیح وارد کنید");
    }
}

if($text=='مدیریت دسته بندی ها 1' and ($userid==ADMIN or isAdmin() )){
    $cats = $telegram->db->query("SELECT * FROM `fl_1cat`")->fetchAll();
    if(empty($cats)){
        $msg = "موردی یافت نشد";
    }else {
        $msg = '';
        foreach ($cats as $cty) {
            $id = $cty['id'];
            $cname = $cty['title'];
            $msg .= "
✅نام : $cname
♻️ویرایش : /edit1c$id
❌حذف : /del1cat$id
====";
        }
    }
    $telegram->sendMessage($userid,$msg);
}
if($text=='افزودن دسته بندی 1' and ($userid == ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",'add1cat');
    $telegram->sendMessage($userid,"نام دسته بندی را وارد کتید:");exit;
}
if(preg_match('/add1cat/',$state)){
    $telegram->db->query("insert into fl_1cat VALUES (NULL,'$text',0)");
    $telegram->sendMessage($userid,"✅دسته بندی جدید با موفقیت اضافه شد");
    file_put_contents("state/$userid.txt",'');
}
if(preg_match('/del1cat/',$text) and ($userid==ADMIN or isAdmin() )){
    $pid=str_ireplace('/del1cat','',$text);
    $telegram->db->query("delete from fl_1cat where id={$pid}");
    $telegram->sendMessage($userid,"دسته بندی موردنظر با موفقیت حذف شد");
}
if(preg_match('/edit1c/',$text) and ($userid==ADMIN or isAdmin() )){
    file_put_contents("state/$userid.txt",$text);
    $telegram->sendMessage($userid,"نام جدید دسته بندی را وارد کتید:");exit;
}
if(preg_match('/edit1c/',$state)){
    $pid=str_ireplace('/edit1c','',$state);
    $telegram->db->query("update fl_1cat set title='$text' where id={$pid}");
    $telegram->sendMessage($userid,"✅عملیات با موفقیت انجام شد");
    file_put_contents("state/$userid.txt",'');
}

if ($text == '📈آمار1' and  ($userid == ADMIN or isAdmin() ) ) {
    file_put_contents('state/' . $userid . '.txt', '');
    $users = $telegram->db->query("select * from fl_user")->rowCount();
    $product = $telegram->db->query("select * from fl_1file WHERE active=1")->rowCount();
    $fault = $telegram->db->query("select * from fl_1order where status=0")->rowCount();
    $success = $telegram->db->query("select * from fl_1order where status=1")->rowCount();
    $income = $telegram->db->query("select sum(amount) as amount from fl_1order where status=1")->fetch(2)['amount'];
    $income = number_format($income);
    $msg = "
✅تعداد کل کاربران ربات :$users 

✅تعداد کل محصولات :$product 

⏩تعداد تراکنش های ناموفق :$fault 

✅تعداد تراکنش های موفق :$success

✅درآمد کل  :$income تومان

.
    ";
    $telegram->sendMessage($userid, $msg);
}

if(($text == 'نسخه 1'  or $text == '↪ ️برگشت  ' ) and ($userid == ADMIN or isAdmin() )){
    file_put_contents('state/' . $userid . '.txt', '');
    $telegram->sendHTML($userid, 'به مدیریت نسخه ۱ خوش آمدید', $version1op);
}
/*end version 1*/


/*marzban*/
if(preg_match('/walMRZpay/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
    $dcode = $input[2];
    
    $file_detail = $telegram->db->query("select * from fl_file WHERE id=$id")->fetch(2);
    $days = $file_detail['days'];
    $date = time();
    $expire_date = $date + (86400 * $days);
    $volume = $file_detail['volume'];
    $protocol = explode('|', $file_detail['protocol']);
    $price = $file_detail['price'];
    $server_id = $file_detail['server_id'];

    $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
    if($server_info['ucount'] != 0) {
        $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
    } else {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'ظرفیت این سرور پر شده است',
            'show_alert' => false
        ]);
        exit;
    }
	
	$seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
    if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
	
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
    
    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }
    // marzban Api
    require_once('marz.php');

    $savedinfo = file_get_contents('savedinfo.txt');
    $savedinfo = explode('-',$savedinfo);
    $port = $savedinfo[0] + 1;
    $last_num = $savedinfo[1] + 1;

    $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
    $uremark = $telegram->db->query("select * from fl_remark where userid=$userid")->fetch(2);
    if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
    $username = "{$uremark}_{$last_num}";

    file_put_contents('savedinfo.txt',$port.'-'.$last_num);

    $response = adduser($server_id, $username, $expire_date, $volume, $protocol);  
    //$telegram->sendMessage($userid, json_encode($response));die;
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"free = serverID: $server_id :".$response->detail);
        exit;
    }
	bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => '♻️در حال ارسال اکانت ...',
        'show_alert' => false
    ]);

    $panel_url = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['panel_url'];
    $sublink = $panel_url.$response->subscription_url;
$acc_text = "🔗 $username \n \n <code>$sublink</code>" . "\n \n  <b>برای کپی کردن لینک روی آن کلیک کنید</b> " ;

  include 'phpqrcode/qrlib.php';
  $file = "images/$userid".time().".png";
    QRcode::png($sublink, $file, 'L', 10, 5);
      
  $acc_text = "

\n <b>🚀اطلاعات سرویس شما به شکل زیر است👇
</b>
$acc_text";


	$telegram->sendPhoto($userid,'',$file);
	$keyboard = [
	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
		[
		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$username"],
		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
	    ]
	];
	bot('sendmessage', [
		'chat_id' => $userid,
		'parse_mode' => 'HTML',
		'text' => $acc_text,
		'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard,
        ])
	]);
	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$userid}, '', {$id}, $server_id, 0, '$username', '".implode('|',$protocol)."', $expire_date, '$sublink', $price,1, '$date', 0);");

    $telegram->db->query("update fl_user set wallet = wallet - $price WHERE userid='$userid'");
    
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
    
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
$sndmsg = "
خرید با کیف پول 
قیمت : $price
آیدی کاربر : $userid
آیدی سرور : $server_id
لینک دریافتی :

$sublink
";
    $telegram->sendMessage($sendchnl,$sndmsg);
}
if(preg_match('/downMRZload/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
	
	$free = file_get_contents("state/{$userid}-free.txt");
	if($free == '') $free = 2;
	if($free < 2  and !($userid == ADMIN or isAdmin() )){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => '⚠️شما قبلا هدیه رایگان خود را دریافت کردید',
			'show_alert' => false
		]); 
		exit; 
	}
	
    $file_detail = $telegram->db->query("select * from fl_file WHERE id=$id")->fetch(2);
    $days = $file_detail['days'];
    $date = time();
    $expire_date = $date + (86400 * $days);
    $volume = $file_detail['volume'];
    $protocol = explode('|', $file_detail['protocol']);
    $price = $file_detail['price'];
    $server_id = $file_detail['server_id'];

    $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
    if($server_info['ucount'] != 0) {
        $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
    } else {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'ظرفیت این سرور پر شده است',
            'show_alert' => false
        ]);
        exit;
    }

    // marzban Api
    require_once('marz.php');

    $savedinfo = file_get_contents('savedinfo.txt');
    $savedinfo = explode('-',$savedinfo);
    $port = $savedinfo[0] + 1;
    $last_num = $savedinfo[1] + 1;

    $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
    $uremark = $telegram->db->query("select * from fl_remark where userid=$userid")->fetch(2);
    if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
    $username = "{$uremark}_{$last_num}";

    file_put_contents('savedinfo.txt',$port.'-'.$last_num);

    $response = adduser($server_id, $username, $expire_date, $volume, $protocol);  
    //$telegram->sendMessage($userid, json_encode($response));die;
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"free = serverID: $server_id :".$response->detail);
        exit;
    }
	bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => '♻️در حال ارسال اکانت ...',
        'show_alert' => false
    ]);

    $panel_url = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['panel_url'];
    $sublink = $panel_url.$response->subscription_url;
$acc_text = "🔗 $username \n \n <code>$sublink</code>" . "\n \n  <b>برای کپی کردن لینک روی آن کلیک کنید</b> " ;

  include 'phpqrcode/qrlib.php';
  $file = "images/$userid".time().".png";
    QRcode::png($sublink, $file, 'L', 10, 5);
      
  $acc_text = "

\n <b>🚀اطلاعات سرویس شما به شکل زیر است👇
</b>
$acc_text";



	$telegram->sendPhoto($userid,'',$file);
	$keyboard = [
	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
		[
		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$username"],
		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
	    ]
	];
	bot('sendmessage', [
		'chat_id' => $userid,
		'parse_mode' => 'HTML',
		'text' => $acc_text,
		'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard,
        ])
	]);
	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$userid}, '', {$id}, $server_id, 0, '$username', '".implode('|',$protocol)."', $expire_date, '$sublink', $price,1, '$date', 0);");
	file_put_contents("state/{$userid}-free.txt",$free - 1);
    
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
}
if(preg_match('/down2MRZload/',$cdata)) {
    $input = explode('#', $cdata);
    $id = $input[1];
    $uid = $userid;
    
    if(count($input) == 4){
        $uid = $input[2];
        $ccount = $input[3];
    }

	include_once ('phpqrcode/qrlib.php');
    $file = 'images/'.$uid.rand(0,9999999).".png";
    $file_detail = $telegram->db->query("select * from fl_file WHERE id=$id")->fetch(2);
    $days = $file_detail['days'];
    $date = time();
    $expire_date = $date + (86400 * $days);
    $volume = $file_detail['volume'];
    $protocol = explode('|', $file_detail['protocol']);
    $price = $file_detail['price'];
    $server_id = $file_detail['server_id'];

    $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
    if($server_info['ucount'] != 0) {
        $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
    } else {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'ظرفیت این سرور پر شده است',
            'show_alert' => false
        ]);
        exit;
    }

    // marzban Api
    require_once('marz.php');

    $savedinfo = file_get_contents('savedinfo.txt');
    $savedinfo = explode('-',$savedinfo);
    $port = $savedinfo[0] + 1;
    $last_num = $savedinfo[1] + 1;

    $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
    $uremark = $telegram->db->query("select * from fl_remark where userid=$uid")->fetch(2);
    if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
    $username = "{$uremark}_{$last_num}";

    file_put_contents('savedinfo.txt',$port.'-'.$last_num);

    $response = adduser($server_id, $username, $expire_date, $volume, $protocol);  
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"free = serverID: $server_id :".$response->detail);
        exit;
    }
	bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => '♻️در حال ارسال اکانت ...',
        'show_alert' => false
    ]);

    $panel_url = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['panel_url'];
    $sublink = $panel_url.$response->subscription_url;
$acc_text = "🔗 $username \n \n <code>$sublink</code>" . "\n \n  <b>برای کپی کردن لینک روی آن کلیک کنید</b> " ;

  $file = "images/$userid".time().".png";
    QRcode::png($sublink, $file, 'L', 10, 5);
      
  $acc_text = "

\n <b>🚀اطلاعات سرویس شما به شکل زیر است👇
</b>
$acc_text";



	$telegram->sendHTML($userid,"اکانت با موفقیت برای کاربر ارسال شد", $finalop); 
	$telegram->sendPhoto($uid,'',$file);
	$keyboard = [
	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
		[
		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$username"],
		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
	    ]
	];
	bot('sendmessage', [
		'chat_id' => $uid,
		'parse_mode' => 'HTML',
		'text' => $acc_text,
		'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard,
        ])
	]);
	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  '$uid', '', {$id}, $server_id, 0, '$username', '".implode('|',$protocol)."', $expire_date, '$sublink', $price,1, '$date', 0);");
    
    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
}
if(preg_match('/mrzChngLnk/', $cdata)){
    $input = explode('#',$cdata);
    $oid = $input[1];
    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$oid")->fetch(2);
    $username = $order['remark'];
    $inbound_id = $order['inbound_id'];
    $server_id = $order['server_id'];
    
    require_once('marz.php');
    $response = muser_detail($server_id, $username);
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
        exit;
    }
   
    $response = revoke_muser($server_id,$username); 
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if($response->detail){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در عملیات. مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage($userid,"serverID: $server_id, username: $username :".$response->detail);
        exit;
    }
    
    $panel_url = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['panel_url'];
    $sublink = $panel_url.$response->subscription_url;
    $telegram->db->query("UPDATE `fl_order` SET link='$sublink' WHERE id=$oid");
    bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "لینک اشتراک بروز شد",
        'show_alert' => false
    ]);
    $cdata = "ordMRZtail#$oid";
}
if(preg_match('/switchsMRZsrv/', $cdata)){
	if($gateways['change_location'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر تغییر لوکیشن غیرفعال است و بزودی فعال می کنیم',
            'show_alert' => true
        ]);
        exit;
    }
    $input = explode('#',$cdata);
    $order_id = $input[1];
    $order = $telegram->db->query("select * from fl_order where id = '$order_id' ")->fetch(2);
    $server_id = $order['server_id'];
    
    $respd = $telegram->db->query("SELECT * FROM `fl_server` INNER JOIN server_info WHERE fl_server.id = server_info.id and fl_server.active=1 and server_info.ptype = 'marzban' and fl_server.id != $server_id")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'در حال حاضر هیچ سرور فعالی برای تغییر لوکیشن وجود ندارد',
            'show_alert' => true
        ]);exit;
    }
    $keyboard = [];
    foreach($respd as $cat){
        $sid = $cat['id'];
        $name = $cat['title'].$cat['flag'];
        $keyboard[] = ['text' => "$name", 'callback_data' => "chngMRZsrrv#$sid#$order_id"];
    }
    $keyboard = array_chunk($keyboard,2);
    $keyboard[] = [['text' => '🔙 بازگشت', 'callback_data' => "ordMRZtail#$order_id"]];
    bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
        'text'=> ' 📍 لطفا برای تغییر لوکیشن سرویس فعلی, یکی از سرورها را انتخاب کنید👇',
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);

}
if(preg_match('/chngMRZsrrv/',$cdata)){
    $input = explode('#',$cdata);
    $sid = $input[1];
    $oid = $input[2];
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $inbound_id = $order['inbound_id'];
    $server_id = $order['server_id'];
    $fileid = $order['fileid'];
    $username = $order['remark'];
	
	$server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$sid")->fetch(2);
	if($server_info['ucount'] == 0) {
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => 'ظرفیت این سرور پر شده است',
			'show_alert' => true
		]);
		exit;
	}
	
    include_once('marz.php');
    $response = muser_detail($server_id, $username);
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"serverID: $server_id, username: $username :".$response->detail);
        exit;
    }
   
    $expire_date = $response->expire;
    $total = $response->data_limit;
    $used_traffic = $response->used_traffic;
    $volume = round( ($total - $used_traffic) / 1073741824, 2);
    
    if(in_array($server_id, $multi_srvs) and in_array($sid, $multiplus_srvs))  $volume = $volume * $to_multiplus;
    elseif(in_array($server_id, $multiplus_srvs) and in_array($sid, $multi_srvs))  $volume = $volume * $to_multi;
    
	//if($server_id == 62 and $sid == 61) $volume = $volume * $to_multiplus;
	//elseif($server_id == 61 and $sid == 62)  $volume = $volume * $to_multi;
    
    $proxies = json_decode(json_encode($response->proxies), 1);
    $protocol = array_keys($proxies);
    //file_put_contents('1212.txt', json_encode($response));die;
    $response = adduser($sid, $username, $expire_date, $volume, $protocol);  
    //$telegram->sendMessage($userid, json_encode($response));die;
    //file_put_contents('1212.txt', json_encode($protocol));die;
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"chngloc = serverID: $sid :".json_encode($response->detail));
        exit;
    }
    
    $panel_url = $telegram->db->query("SELECT * FROM server_info WHERE id=$sid")->fetch(2)['panel_url'];
    $sublink = $panel_url.$response->subscription_url;

    $telegram->db->query("UPDATE fl_order set server_id=$sid,link='$sublink' where id=$oid");
    $isvip = $telegram->db->query("select * from server_info where id = $server_id")->fetch(2);
    if($isvip['vip'] == '0'){
        $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount + 1 WHERE id=$server_id");
    }
	$telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$sid");

    $userQuery = $telegram->db->query("SELECT userid FROM fl_order where id=$oid");
    $user = $userQuery->fetch(2);
    $userid = $user['userid'];
    

    $response = mdelete_user($server_id, $username);
    $server_title = $telegram->db->query("select * from fl_server where id=$sid")->fetch(2)['title'];
	bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => "✅سرویس مورد نظر با موفقیت به لوکیشن $server_title انتقال یافت
        
توجه فرمایید که لینک قبلی باطل و از لینک جدید باید استفاده کنید",
        'show_alert' => true
    ]);

	$cdata = "ordMRZtail#$oid";
}
if(preg_match('/ordMRZtail/', $cdata)){
    $input = explode('#', $cdata);
    $id = $input[1];
    $order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$id")->fetch(2);
    if(empty($order)){
        $telegram->sendMessage($userid,"موردی یافت نشد");exit;
    }else {
        $fid = $order['fileid']; 
    	$respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2); 
    	if($respd){
    	    $cadres = $telegram->db->query("select * from fl_cat WHERE id=".$respd['catid'])->fetch(2);
    	    if($cadres) {
    	        $catname = $cadres['title'];
        	    $name = $catname." ".$respd['title'];
    	    }else $name = "#$id";
    	}else $name = "#$id";

        $date = jdate("Y-m-d H:i",$order['date']);
        $username = $order['remark'];
        $acc_link = $order['link'];
        $protocol = $order['protocol'];
        $server_id = $order['server_id'];
        $price = $order['amount'];
        $inbound_id = $order['inbound_id'];
        $uid =  $order['userid'];

        include_once('marz.php');
        $response = muser_detail($server_id, $username);
        if(is_null($response) or !$response){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
                'show_alert' => true
            ]);
            exit;
        }
        
        $sres = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2);
        $stitle = $sres['title'];
        $flag = $sres['flag'];
        $msg = "✅ $name \n🌐 $stitle $flag \n📝 $date \n🌟 $link_status \n🔗 $username \n <code>$acc_link</code>";
        
    	if(!$response->subscription_url){
            bot('answercallbackquery', [
                'callback_query_id' => $cid,
                'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
                'show_alert' => true
            ]); //$telegram->sendMessage(ADMIN,"serverID: $server_id, username: $username :".$response->detail);
            //exit;
                $keyboard = [[['text' => "↪ برگشت", 'callback_data' => "backto"],['text' => "❌حذف سرویس", 'callback_data' => "dlusmysv#$id"]]];
                $aa = bot('editmessageText', [
                    'chat_id' => $userid,
                    'message_id' => $cmsgid,
                    'parse_mode' => "HTML",
                    'text' => $msg,
                    'reply_markup' => json_encode([
                        'inline_keyboard' => $keyboard
                    ])
                ]);
                exit;
        }else{
            $expire_date = $response->expire;
        
            $inputSeconds = $expire_date - time();
            $time = secondsToTime($inputSeconds);
            $left_days = $time['d'];
    		if(is_null($orginal_days)) $orginal_days = 0;
    		
            $total = $response->data_limit;
            $used_traffic = $response->used_traffic;
            if(strlen($expire_date) > 0 and !is_null($expire_date)) $telegram->db->query("update fl_order set expire_date='$expire_date' where id=$id"); 
            $link_status = $response->status == 'active' ? 'فعال' : 'غیرفعال';
            $expire_date = $expire_date == 0 ? 'نامحدود' : jdate(" H:i d-m-Y",$expire_date);
            $leftgb = round( ($total - $used_traffic) / 1073741824, 2) . " GB";
        }
       
        

		

        $keyboard = [
            [['text' => "🔄 تغییر لینک و قطع دسترسی دیگران", 'callback_data' => "mrzChngLnk#$id"]],
    		[
    			['text' => " $leftgb حجم باقیمانده", 'callback_data' => "nsdfi3nskld"],
    			['text' => "🧩 کیو آر کد", 'callback_data' => "qrcode#$id"],
    		],
            [
                ['text' => " انقضا ⏰ ". $expire_date, 'callback_data' => "n4oth4ing"],
            ],
            [['text' => '📱دریافت لینک تکی', 'callback_data' => "getmrzsngl#$id" ]],
    		[
    			['text' => '♻ تمدید سرویس', 'callback_data' => "renMRZewacc#$id" ],
    			['text' => '🔌تغییر لوکیشن', 'callback_data' => "switchsMRZsrv#$id"]
    		],
        ];
        $server_info = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2);
        $extrakey = []; 
        if($gateways['buy_gb'] == 1) $extrakey[] = ['text' => "📥افزایش حجم سرویس", 'callback_data' => "upmysrvice#$server_id#0#$username"];
        if($gateways['buy_day'] == 1) $extrakey[] = ['text' => "افزایش زمان سرویس✨", 'callback_data' => "relinsrvc#$server_id#0#$username"];
        if($order['amount'] != 0 ) $keyboard[] = $extrakey;
        
        $keyboard[] = [['text' => "↪ برگشت", 'callback_data' => "backto"],['text' => "❌حذف سرویس", 'callback_data' => "dlusmysv#$id"]];
        //$keyboard[] = [['text' => "↪ برگشت", 'callback_data' => "backto"]];
        
        if($uid != $userid){
            $keyboard = [
                [['text' => "🔄 تغییر لینک و قطع دسترسی دیگران", 'callback_data' => "mrzChngLnk#$id"]],
        		[
        			['text' => " $leftgb حجم باقیمانده", 'callback_data' => "nsdfi3nskld"],
        			['text' => "🧩 کیو آر کد", 'callback_data' => "qrcode#$id"],
        		],
                [
                    ['text' => " انقضا ⏰ ". $expire_date, 'callback_data' => "n4oth4ing"],
                ],
                [['text' => '📱دریافت لینک تکی', 'callback_data' => "getmrzsngl#$id" ]],
                [
                    ['text' => '🔌تغییر لوکیشن', 'callback_data' => "switchsMRZsrv#$id"],
                    ['text' => "❌حذف سرویس", 'callback_data' => "dlmysv#$id"]
                ]
            ];
        }
            
           $aa = bot('editmessageText', [
                'chat_id' => $userid,
                'message_id' => $cmsgid,
                'parse_mode' => "HTML",
                'text' => $msg,
                'reply_markup' => json_encode([
                    'inline_keyboard' => $keyboard
                ])
            ]); //$telegram->sendMessage($userid,"upmysrvice#$server_id#0#$username");
        }
}
if(preg_match('/getmrzsngl/',$cdata)){
    $input = explode('#',$cdata);
    $oid = $input[1];
	$order = $telegram->db->query("SELECT * FROM `fl_order` WHERE id=$oid")->fetch(2);
	$acc_link = $order['link'];
    $username = $order['remark'];
    $server_id = $order['server_id'];

    include_once('marz.php');
    $response = muser_detail($server_id, $username); 
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا دریافت اطلاعات. به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"serverID: $server_id, username: $username :".$response->detail);
        exit;
    }
    $links = $response->links; 
    $msg = "";
    $count = 0;
    foreach($links as $link){
        $count++;
        if($link == 'False' or $count == 1) continue;
        if(strlen($msg) > 3370){
            bot('sendmessage',[
                'chat_id' => $userid,
                'parse_mode' => 'HTML',
                'text'=> $msg,
            ]);
            $msg = '';
        }
        
        if(preg_match('/^vmess:\/\//',$link)){
            $link_info = json_decode(base64_decode(str_replace('vmess://','',$link)));
            $uuid = $link_info->id;
            $remark = $link_info->ps;
        }elseif(preg_match('/^ss:\/\//',$link)){
    	    $link_info = str_replace("ss://",'',$link);
    	    
    	    $link_info = explode("#",$link_info);
    	    $remark = $link_info[1];
    	}else{
            $link = urldecode($link);
            $link_info = parse_url($link);
            $uuid = $link_info['user'];
            $remark = $link_info['fragment'];
    
        }
        $msg .= "🔗$remark \n <code>$link</code> \n \n";
        
    }
    bot('sendmessage',[
        'chat_id' => $userid,
        'parse_mode' => 'HTML',
        'text'=> $msg,
    ]);
    
}
if(preg_match('/renMRZewacc/',$cdata)){
	if($gateways['renew'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر تمدید غیرفعال است و بزودی فعال می کنیم',
            'show_alert' => true
        ]);
        exit;
    }
    $oid = str_replace('renMRZewacc#','',$cdata);
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $sid = $order['server_id'];
    $respd = $telegram->db->query("select * from fl_file WHERE server_id='$sid' and price > 0 and active=1 order by id asc")->fetchAll(2);
    if(empty($respd)){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡پلنی برای تمدید وجود ندارد ",
            'show_alert' => false
        ]);
    }else{
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "📍در حال دریافت لیست پلن ها",
            'show_alert' => false
        ]);
        $keyboard = [];
        foreach($respd as $file){
            $id = $file['id'];
            $name = $file['title'];
            $price = $file['price'];
            $price = number_format($price).' تومان ';
            $keyboard[] = ['text' => "$name - $price", 'callback_data' => "re2MRZnewacc#$id#$oid"];
        }
        $keyboard[] = ['text' => '🔙 بازگشت', 'callback_data' => "ordMRZtail#$oid"];
        $keyboard = array_chunk($keyboard,1);
        bot('editMessageText', [
            'chat_id' => $cuserid,
            'message_id' => $cmsgid,
            'text' => "🔰 یکی از پلن ها را برای تمدید انتخاب کنید👈
⚠️ با تمدید اکانت حجم و زمان انقضای باقیمانده از اول محاسبه می شود و امکان جمع آن با سرویس تمدید نیست.
✔️اگر فقط حجم یا زمان سرویس به پایان رسیده می توانید از دکمه های افزایش زمان/حجم سرویس هر یک را جداگانه شارژ کنید
",
            'reply_markup' => json_encode([
                'inline_keyboard' => $keyboard
            ])
        ]);
    }
}
if(preg_match('/re2MRZnewacc/',$cdata)){
    if($gateways['renew'] == 0){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻در حال حاضر تمدید غیرفعال است و بزودی فعال می کنیم',
            'show_alert' => true
        ]);
        exit;
    }
    $input = explode('#',$cdata);
    $fid = $input[1];
    $oid = $input[2];
    $respd = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
    $price = $respd['price'];
	
	if($price == 0){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => "امکان تمدید کانفیگ تست وجود ندارد",
			'show_alert' => false
		]);exit;
	}
    
    $telegram->db->query("update fl_order set fileid=$fid where id=$oid");

    $token = base64_encode("$userid#$fid#$oid");
    if($gateways['wallet']) $keyboard[] = [['text' => '🏅 پرداخت با کیف پول', 'callback_data' => "walMRZrnwpay#$fid#$oid"]];
    
    $keyboard[] = [['text' => '🔙 بازگشت', 'callback_data' => "renMRZewacc#$oid"]];


    bot('editMessageText', [
        'chat_id' => $cuserid,
        'message_id' => $cmsgid,
        'parse_mode' => "HTML",
        'text' => "لطفا با یکی از روش های زیر اکانت خود را تمدید کنید :",
        'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard
        ])
    ]);
}
if(preg_match('/walMRZrnwpay/', $cdata)){
    $input = explode('#',$cdata);
    $fid = $input[1];
    $oid = $input[2];
    
    $order = $telegram->db->query("select * from fl_order where id=$oid")->fetch(2);
    $username = $order['remark'];
    $server_id = $order['server_id'];

    $file_detail = $telegram->db->query("select * from fl_file WHERE id='$fid' and active=1")->fetch(2);
    $price = $file_detail['price'];
    $volume = $file_detail['volume'];
    $days = $file_detail['days'] + 1;
    $expire_date = time() + (86400 * $days);
    $protocol = explode('|', $file_detail['protocol']);
 
    $userwallet = $telegram->db->query("select wallet from fl_user WHERE userid='$userid'")->fetch(2)['wallet'];
    
    if($userwallet < $price) {
        $needamount = $price - $userwallet;
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => "💡موجودی کیف پول (".number_format($userwallet)." تومان) کافی نیست لطفا به مقدار ".number_format($needamount)." تومان شارژ کنید ",
            'show_alert' => true
        ]);
        exit;
    }

    // marzban Api
    require_once('marz.php');
    $response = edit_muser($server_id, $username, $expire_date, $volume, $protocol);  
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در تمدید سرویس. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"Renew = serverID: $server_id :".$response->detail);
        exit;
    }

	if(is_null($response)){
		bot('answercallbackquery', [
			'callback_query_id' => $cid,
			'text' => '🔻مشکل فنی در اتصال به سرور. لطفا به مدیریت اطلاع بدید',
			'show_alert' => true
		]);
		exit;
	}
	$telegram->db->query("update fl_order set expire_date= $expire_date + $days * 86400,notif=0 where id='$oid'");
    //$telegram->db->query("INSERT INTO `fl_extra_order` VALUES (NULL, '$userid', '$server_id', '$inbound_id', '$remark', '$price', '$time');");
    $telegram->db->query("update fl_user set wallet = wallet - $price WHERE userid='$userid'");
	// update button
	bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
			'inline_keyboard' => [[['text' => '✅انجام شد', 'callback_data' => "dontsendanymore"]]],
		])
	]);
    $telegram->sendMessage($userid, "✅سرویس $username با موفقیت تمدید شد");exit;

}

if(preg_match('/enaMRZble/',$cdata) and $text != '❌ انصراف'){
    
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid' and status=2")->fetch(2);
    if(!empty($res)){
        exit;
    }
    file_put_contents("state/{$userid}.txt","");
    $input = explode('#',$cdata);
    $uid = $input[1];
    $fid = $input[2];
    $acctxt = '';
    
    $file_detail = $telegram->db->query("select * from fl_file WHERE id=$fid")->fetch(2);
    $days = $file_detail['days'];
    $date = time();
    $expire_date = $date + (86400 * $days);
    $volume = $file_detail['volume'];
    $protocol = explode('|', $file_detail['protocol']);
    $price = $file_detail['price'];
    $seller = $telegram->db->query("select * from fl_sellers where userid=$userid")->fetch(2);
    if(!empty($seller)) $price = $price - (($price) * ($seller['percent'] / 100));
    $server_id = $file_detail['server_id'];

    $server_info = $telegram->db->query("SELECT * FROM fl_server WHERE id=$server_id")->fetch(2);
    if($server_info['ucount'] != 0) {
        $telegram->db->query("UPDATE `fl_server` SET `ucount` = ucount - 1 WHERE id=$server_id");
    } else {
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => 'ظرفیت این سرور پر شده است',
            'show_alert' => false
        ]);
        exit;
    }

    // marzban Api
    require_once('marz.php');

    $savedinfo = file_get_contents('savedinfo.txt');
    $savedinfo = explode('-',$savedinfo);
    $port = $savedinfo[0] + 1;
    $last_num = $savedinfo[1] + 1;

    $srv_remark = $telegram->db->query("select * from fl_server WHERE id=$server_id")->fetch(2)['remark'];
    $uremark = $telegram->db->query("select * from fl_remark where userid=$uid")->fetch(2);
    if($uremark) $uremark = $uremark['remark']; else $uremark = $srv_remark;
    $username = "{$uremark}_{$last_num}";

    file_put_contents('savedinfo.txt',$port.'-'.$last_num);

    $response = adduser($server_id, $username, $expire_date, $volume, $protocol);  
    if(is_null($response) or !$response){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻اتصال به سرور برقرار نیست. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]);
        exit;
    }

	if(!$response->subscription_url){
        bot('answercallbackquery', [
            'callback_query_id' => $cid,
            'text' => '🔻خطا در ساخت کانفیگ. لطفا به مدیریت اطلاع بدید',
            'show_alert' => true
        ]); $telegram->sendMessage(ADMIN,"free = serverID: $server_id :".$response->detail);
        exit;
    }
	bot('answercallbackquery', [
        'callback_query_id' => $cid,
        'text' => '♻️در حال ارسال اکانت ...',
        'show_alert' => false
    ]);

    $panel_url = $telegram->db->query("SELECT * FROM server_info WHERE id=$server_id")->fetch(2)['panel_url'];
    $sublink = $panel_url.$response->subscription_url;
$acc_text = "🔗 $username \n \n <code>$sublink</code>" . "\n \n  <b>برای کپی کردن لینک روی آن کلیک کنید</b> " ;

  include 'phpqrcode/qrlib.php';
  $file = "images/$userid".time().".png";
    QRcode::png($sublink, $file, 'L', 10, 5);
      
  $acc_text = "

\n <b>🚀اطلاعات سرویس شما به شکل زیر است👇
</b>
$acc_text";




	$telegram->sendPhoto($uid,'',$file);
	$keyboard = [
	    //[['text' => "دانلود برنامه های موردنیاز📥", 'callback_data' => $donwnload_link]],
	    [['text' => "آموزش اتصال🔗", 'callback_data' => "hlpsee"]],
		[
		    ['text' => "متصل شدم!", 'callback_data' => "connctedmsg#$username"],
		    ['text' => "وصل نشد!", 'callback_data' => "connctnotmsg"]
	    ]
	];
	bot('sendmessage', [
		'chat_id' => $uid,
		'parse_mode' => 'HTML',
		'text' => $acc_text,
		'reply_markup' => json_encode([
            'inline_keyboard' => $keyboard,
        ])
	]);
	$order = $telegram->db->query("INSERT INTO `fl_order` VALUES (NULL,  {$uid}, '', {$fid}, $server_id, 0, '$username', '".implode('|',$protocol)."', $expire_date, '$sublink', $price,1, '$date', 0);");

    // update button
    bot('editMessageReplyMarkup',[
		'chat_id' => $userid,
		'message_id' => $cmsgid,
		'reply_markup' => json_encode([
            'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        ])
    ]);
    $res = $telegram->db->query("select * from verifylogs WHERE message_id='$cmsgid'")->fetch(2);
    if(!empty($res)){
        $uniqmsgid = $res['uniqid'];
        $res2 = $telegram->db->query("select * from verifylogs WHERE uniqid='$uniqmsgid'")->fetchAll(2);
        foreach($res2 as $rsmsg){
            $rid = $rsmsg['id'];
            $mownerid = $rsmsg['userid'];
            $mmsgid = $rsmsg['message_id'];
            $telegram->db->query("update verifylogs set status = 2 WHERE id='$rid' ");
            bot('editMessageReplyMarkup',[
        		'chat_id' => $mownerid,
        		'message_id' => $mmsgid,
        		'reply_markup' => json_encode([
        			'inline_keyboard' => [[['text' => '✅ارسال شد', 'callback_data' => "dontsendanymore"]]],
        		])
        	]);
        }
    }
   
// pay referer 
    $userReferer = $telegram->db->query("select * from fl_subuser where userid=$uid");
    if($userReferer->rowCount() ){
        $ures = $userReferer->fetch(2);
        $userToplevel = $ures['toplevel_userid'];
        $ufname = $ures['fname'];
        $amount = ($price) * ($pursant / 100);
        $telegram->db->query("update fl_user set wallet= wallet + $amount WHERE userid=$userToplevel");
        $telegram->sendMessage($userToplevel, "💟کاربر {$ufname} یک خرید به مبلغ  $price تومان انجام داد و $pursant درصد آن یعنی $amount تومان به کیف پول شما اضافه شد👍"); 
    }
    
    
}
/*end marzban*/
if ($text == '❌ انصراف') {
    file_put_contents('state/' . $userid . '.txt', '');
    $telegram->db->query("delete from fl_file where active=0");
    $telegram->sendHTML($userid, '‼️‼️عملیات مورد نظر لغو شد', $finalop);
}