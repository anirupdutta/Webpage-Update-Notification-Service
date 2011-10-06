#!/usr/bin/php -q


/***************

@author :Anirup Dutta
@copyright : anirupdutta,2011
@email : anirupdutta@heybuddy.in
@license : http://www.gnu.org/licenses/agpl.html

****************/

<?php


/***************

@author :Anirup Dutta
@copyright : anirupdutta,2011
@email : anirupdutta@heybuddy.in
@license : http://www.gnu.org/licenses/agpl.html

****************/

//The Client
error_reporting(E_ALL);

$message = strtoupper($_SERVER['argv'][1]);
$address = $_SERVER['argv'][2];
$port = $_SERVER['argv'][3];
$callback_ipaddr = $_SERVER['argv'][4];
$callback_port = $_SERVER['argv'][5];
$url = $_SERVER['argv'][6];

$messagearray = array (
    'action' => $message,
    'ip' => $callback_ipaddr,
    'port' => $callback_port,
    'url' => $url,
);

$jsonMethodRequest = json_encode($messagearray);

function write($sock,$msg) { 
        $length = strlen($msg); 
        while(true) { 
            $sent = socket_write($sock,$msg,$length); 
            if($sent === false) { 
                return false; 
            } 
            if($sent < $length) { 
                $msg = substr($msg, $sent); 
                $length -= $sent; 
                print("Message truncated: Resending: $msg"); 
            } else { 
                return true; 
            } 
        } 
        return false; 
} 

/* Create a TCP/IP socket. */
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if ($socket === false) {
    echo "socket_create() failed: reason: " . socket_strerror(socket_last_error()) . "\n";
} else {
    echo "socket successfully created.\n";
}

echo "Attempting to connect to '$address' on port '$port'...";
$result = socket_connect($socket, $address, $port);
if ($result === false) {
    echo "socket_connect() failed.\nReason: ($result) " . socket_strerror(socket_last_error($socket)) . "\n";
} else {
    echo "successfully connected to $address.\n";
}

while (true == true)
{
    write($socket, $jsonMethodRequest);
    //socket_write($socket, $jsonMethodRequest, strlen($jsonMethodRequest));
   
    $input = socket_read($socket, 2048);
    if(!empty($input))
    {
	$response = json_decode($input);
	echo "Response from server is:" . $response->returnvalue ."\n";
	//sleep(5);
	break;
    }
}

socket_close($socket);
?> 
