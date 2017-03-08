<?php 

# define some global variables
$listen             = false;
$command            = false;
$upload             = false;
$execute            = "";
$target             = "";
$upload_destination = "";
$port               = 0;

function usage(){
	echo "Black Hat PHP Net Tool\n\n";

	echo "Usage: netcat.php -t target_host -p port \n";
	echo "-l --listen               - listen on [host]:[port] for incoming connections\n";
	echo "-e --execute=file_to_run  - execute the given file upon receiving a connection\n";
	echo "-c --command              - initialize a command shell\n\n";

	echo "Examples: \n";
	echo "netcat.php -t 192.168.0.1 -p 5555 -l -c\n";
	echo "netcat.php -t 192.168.0.1 -p 5555 -l -u=c:\\target.exe\n";
	echo "netcat.php -t 192.168.0.1 -p 5555 -l -e=\"cat /etc/passwd\"\n";
	echo "echo 'ABCDEF' | ./netcat.php -t 192.168.11.12 -p 135\n";
	die;

}

function client_sender($buffer){
	global $target, $port;

	try {
		
		$client = fsockopen($target, $port);

		if(isset($buffer)){
			$test = fwrite($client, $buffer);
			var_dump($test);
			while (1) {
				$recv_len = 1;
				$response = "";

				while ($recv_len){
					$data = fgets($client, 4096);
					$recv_len = strlen($data);
					$response .= $data;
				}

				echo $response;

				$buffer = rtrim( fgets( STDIN ), "\n" );
				$buffer .= "\n";

				fwrite($client, $buffer);
				fflush($client);

			}

		}

	} catch (Exception $e) {
		echo "[*] Exception! Exiting.\n";
		socket_close($client);
	}

}

function main(){
	global $argc, $argv, $listen, $command , $upload , $execute, $target, $upload_destination, $port;

	if(count($argv)==1){
		usage();
	}


	$options = getopt("hle:t:p:cu:", ["help", "listen", "execute", "target", "port", "command", "upload"]);

	if(!is_array($options)){
		print "There was a problem reading in the options.\n\n"; 
		usage();
	}

	foreach ($options as $option=>$arg) {
		if(in_array($option, ["h", "help"])){
			usage();
		}elseif (in_array($option, ["l", "listen"])) {
			$listen = true;
		}else if(in_array($option, ["e", "execute"])){
			$execute = $arg;
		}elseif (in_array($option, ["c", "command"])) {
			$command = true;
		}elseif (in_array($option, ["u", "upload"])) {
			$upload_destination = $arg;
		}elseif (in_array($option, ["t", "target"])) {
			$target = $arg;
		}elseif (in_array($option, ["p", "port"])) {
			$port = (int)$arg;
		}else{
			usage();
		}
	}

	if(!$listen && strlen($target) && $port>0){
		$buffer = rtrim( fgets( STDIN ), "\n" );

		client_sender($buffer);
	}

	/*if($listen){
		server_loop();
	}*/

}

main();