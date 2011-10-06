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

//Client Callback.Listens to notify messages from the server
error_reporting(E_ALL);
$port = $_SERVER['argv'][1];

$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
socket_bind($socket,'127.0.0.1',$port);
socket_listen($socket);
socket_set_nonblock($socket);

echo "Client callback started...\n";
 

while (true == true)
{   
    if(($client = @socket_accept($socket)) !== false)
    {
	$input = @socket_read($client, 2048);
	if(!empty($input))
	{
	    $response = json_decode($input);
	   // var_dump($response);
	    echo $response->action ."   ". $response->url ."\n";
	}
	socket_close($client);
    }
}
socket_close($socket);

?>