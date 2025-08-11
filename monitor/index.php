<?php
$domain = 'google.com';

require 'vendor/autoload.php';

// instantiate
$checker = new Hedii\UptimeChecker\UptimeChecker();
$result = $checker->check($domain);

if(!$result['success']){
    echo 'down';
}else{
    echo 'ok';
}