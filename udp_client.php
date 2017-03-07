<?php

$target_host = "127.0.0.1";
$target_port = 7718;

#create the socket
$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

#connect the client
socket_connect($socket, $target_host, $target_port);

#send some data
socket_sendto($socket, "AAABBBCCC" , 100 , 0 , $target_host , $target_port);

#receive some data
$response = socket_recvfrom($socket, $buf, 4096, 0, $client_host, $client_port);

echo $buf;

