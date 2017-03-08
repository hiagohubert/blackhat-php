<?php

$target_host = "127.0.0.1";
$target_port = 9999;

#create the socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

#connect the client
socket_connect($socket, $target_host, $target_port);

#send some data
socket_write($socket, "GET / HTTP/1.1\r\nHost: google.com\r\n\r\n");

#receive some data
$response = socket_read($socket, 4096);

echo $response;



