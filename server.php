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


//The Server
error_reporting(E_ALL);

//Client Class

class ClientClass
{
    // property declaration
    public $ipaddress;
    public $port;
    public $url;


    // constructor
    public function __construct() {
        $this->ipaddress = '127.0.0.1';
        $this->port = 10000;
	$this->url = '';
    }

    // set values
    public function set($ipaddress,$port,$url) {
        $this->ipaddress = $ipaddress;
        $this->port = $port;
	$this->url = $url;
    }
}


//port number entered as an argument
$port = $_SERVER['argv'][1];


//Creates directory cache if not present
if(!is_dir("cache"))
{
    mkdir("cache", 0777);
}


$client_callback = array();
$i = 0;

//Loads previous entries from the database.txt if it crashed before

$filehandle = fopen("database.txt", "c+");

if (filesize( "database.txt" ) )
{
     while (($buffer = fgets($filehandle, 4096)) !== false) {
	  $pieces = explode(" ", $buffer);
	  $assigned = new ClientClass();
	  $client_callback[] = $assigned;
	  $assigned->set($pieces[0],$pieces[1],trim($pieces[2])); 
	  $i+=1;
     }
     if (!feof($filehandle)) {
	    echo "Error: unexpected fgets() fail\n";
     }
}
fclose($filehandle);

//Deletes a line from a file

function delLineFromFile($fileName, $lineNum){

 
    if(!is_writable($fileName))
    {
	print "The file $fileName is not writable";
	exit;
    }
    else
    {   
	$arr = file($fileName);
    }
 
    $lineToDelete = $lineNum;
    if($lineToDelete > sizeof($arr))
    {
	print "You have chosen a line number, <b>[$lineNum]</b>,  higher than the length of the file.";
	exit;
    }

    unset($arr["$lineToDelete"]);

    if (!$fp = fopen($fileName, 'w+'))
    {
        print "Cannot open file ($fileName)";
        exit;
    }
  
    if($fp)
    {
        foreach($arr as $line) 
	{ 
	    fwrite($fp,$line); 
	}
    fclose($fp);
    }
}


//Detects a disconnected client

function disconnected(&$assignar)
{
    $currentdir = getcwd();
    $currentdir .= '/cache/';
    $total = 0;
    $lineexpect = 0;

    while (list($key, $assign) = each($assignar))
    {	
	    /* Create a TCP/IP socket. */

	    $socket_callback = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	    $result_callback = @socket_connect($socket_callback, $assign->ipaddress, $assign->port);

	    if($result_callback === false)
	    {
		  unset($assignar["$key"]);
		  echo "Client with ip " . $assign->ipaddress . " on port number " . $assign->port . " is down \n";	      
		  delLineFromFile('database.txt', $lineexpect);
	    }
	    else
	    {
		  socket_close($socket_callback);
	    }
     }
     reset ($assignar);
}

//Checks for page updates

function updatecheck(&$assignar)
{

    $currentdir = getcwd();
    $currentdir .= '/cache/';
    $total = 0;
    $lineexpect = 0;

    while (list($key, $assign) = each($assignar))
    {
      //echo "anirup";
      $patterns[0] = 'http';
      $patterns[1] = '~';
      $patterns[2] = '\n';
      $patterns[3] = '=';
      $patterns[4] = '&';
      $patterns[5] = '/';
      $patterns[6] = '?';
      $patterns[7] = '-';
      $patterns[8] = '.';
      $patterns[9] = ':';
      $patterns[10] = 'www';
	    
      $sub = str_replace($patterns, "",$assign->url).str_replace('.',"",$assign->ipaddress);
      $filename = $currentdir.$sub.$assign->port;

      //echo $filename;

      if(is_file($filename))
      {
	    /* Create a TCP/IP socket. */

	    $socket_callback = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	    $result_callback = @socket_connect($socket_callback, $assign->ipaddress, $assign->port);

	    /*
	    if($result_callback === false)
	    {
		  unset($assignar["$key"]);
		  echo "Client with ip " . $assign->ipaddress . " on port number " . $assign->port . " is down \n";	      
		  delLineFromFile('database.txt', $lineexpect);
		
	    }
	    else
	    */

	    if($result_callback === true)
	    {
		$lineexpect += 1;
		$str1 = file_get_contents($assign->url);
		$str2 = file_get_contents($filename);
		if(strcasecmp($str1 ,$str2))
		{
		    file_put_contents($filename, $str1, LOCK_EX);

		    if ($result_callback === true) {
			    $return_callback = array(
				    'action' => "NOTIFY",
				    'url' => $assign->url,
			    );

			    $return_callback_json = json_encode($return_callback);
			    //echo $return_callback_json;
			    write($socket_callback,$return_callback_json);
			    sleep(2);
			    socket_close($socket_callback);		      
		    }
		}
	    }
	}
    }
    reset ($assignar);
}

//Unsubscribe function

function unSubscribe(&$assignar,$ipaddress,$port,$url)
{
    $lineexpect = 0;
    $check = 0;

    while (list($key, $assign) = each($assignar))
    {
      if($assign->ipaddress == $ipaddress && $assign->port == $port && $assign->url == $url)
      {
	  unset($assignar["$key"]);
	  delLineFromFile('database.txt', $lineexpect);
	  $check = 1;
	  break;
      }
      else
      {
	  $lineexpect += 1;
      }
    }
    reset ($assignar);
    return $check;
}

$clients = array();
$socket = socket_create(AF_INET,SOCK_STREAM,SOL_TCP);
socket_bind($socket,'127.0.0.1',$port);
socket_listen($socket);
socket_set_nonblock($socket);

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

//Uses forks to ensure certain amount of multithreading.php's architecture doesn't support multithreading

$SHM_KEY = ftok(__FILE__, chr(1));
$handle = sem_get($SHM_KEY);
$buffer = shm_attach($SHM_KEY);

shm_put_var($buffer, 1, $client_callback);

$pid = pcntl_fork();
if ($pid == -1) {
    die('could not fork');
} 
else if ($pid) 
{
    $random = 0;
    //echo "anirup";
    while(true)
    {
	if(($newc = @socket_accept($socket)) !== false)
	{
	    echo "Incoming Request ......\n";
	    $clients[] = $newc;
	    $input = socket_read($newc, 2048);
	    //echo $input;
	    $decodearray = json_decode($input);
	    if(trim($decodearray->action) == "SUBSCRIBE")
	    {
		$file = 'database.txt';
		
		$socket_callback = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		$result_callback = @socket_connect($socket_callback, $decodearray->ip, $decodearray->port);
		socket_close($socket_callback);
		if($result_callback === true)
		{
		    $assigned = new ClientClass();
		    $client_callback[] = $assigned;
		    $assigned->set($decodearray->ip,$decodearray->port,$decodearray->url);

		    //	    echo $client_callback[0]->ipaddress;

		    $dataentry = $decodearray->ip." ".$decodearray->port." ". $decodearray->url . "\n";
	     
		    // Write the contents to the file, 
		    // using the FILE_APPEND flag to append the content to the end of the file
		    // and the LOCK_EX flag to prevent anyone else writing to the file at the same time
		    file_put_contents($file, $dataentry, FILE_APPEND | LOCK_EX);

		    $currentdir = getcwd();
		    $currentdir .= '/cache/';
		    $cacheurl = file_get_contents($decodearray->url);

		    $patterns[0] = 'http';
		    $patterns[1] = '~';
		    $patterns[3] = '=';
		    $patterns[4] = '&';
		    $patterns[5] = '/';
		    $patterns[6] = '?';
		    $patterns[7] = '-';
		    $patterns[8] = '.';
		    $patterns[9] = ':';
		    $patterns[10] = 'www';
	    
		    $subcache = str_replace($patterns, "",$decodearray->url).str_replace('.',"",$decodearray->ip);

		    $cachefilename = $currentdir.$subcache.$decodearray->port;

		    file_put_contents($cachefilename, $cacheurl);

		    $returnclient = array(
			  'returnvalue' => "SUCCESS",
		    );
		    $return = json_encode($returnclient);
		    //echo $return;
		    write($newc,$return);
		    echo "Request Completed ......\n";
		 }
		 else
		 {
		    $returnclient = array(
			  'returnvalue' => "FAIL",
		    );
		    $return = json_encode($returnclient);
		    //echo $return;
		    write($newc,$return);
		    echo "Request Completed ......\n";
		 }
	    }
	    else
	    {
		if(trim($decodearray->action) == "UNSUBSCRIBE")
		{
		    $file = 'database.txt';
		    

		    $dataentry = $decodearray->ip." ".$decodearray->port." ". $decodearray->url . "\n";
	     
		    if(unSubscribe($client_callback,$decodearray->ip,$decodearray->port,$decodearray->url))
		    {
			  $returnclient = array(
				'returnvalue' => "SUCCESS",
			  );
			  $return = json_encode($returnclient);
			  //echo $return;
			  write($newc,$return);
			  echo "Request Completed ......\n";
		    }
		    else
		    {
			  $returnclient = array(
				'returnvalue' => "FAIL",
			  );
			  $return = json_encode($returnclient);
			  //echo $return;
			  write($newc,$return);
			  echo "Request Completed ......\n";
		    }
	    }
	}
	
    }

    if($random == 300000)
    {
	disconnected($client_callback);
	$random = 0;
    }
    $random += 1;

    sem_acquire($handle);
    shm_put_var($buffer, 1, $client_callback);
    sem_release($handle);

    }
    pcntl_wait($status); //Protect against Zombie children
} 
else 
{
    while(true)
    {
	//echo "anirup";
	sem_acquire($handle);
	if(shm_has_var($buffer,1))
	{
	    $callback_client =  shm_get_var($buffer, 1);
	}
	sem_release($handle);
	if(is_array($callback_client))
	{
	  updatecheck($callback_client);
	}
    }
}

?>
 
