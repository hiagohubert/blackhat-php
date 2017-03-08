<?php

$bind_ip  = "0.0.0.0";
$bind_port = 9999;

$server = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

socket_bind($server, $bind_ip , $bind_port);

socket_listen($server, 5);

printf("[*] Listening on %s:%d", $bind_ip, $bind_port);


while(1){
    $client = socket_accept($server);
    socket_getpeername($client, $address, $port);
    
    printf("\n[*] Accepted connection from: %s:%d", $address,$port);
     
    #print out what the client sends
    socket_recv($client,$request, 4096, MSG_DONTWAIT);
    
    printf("\n[*] Received: %s \n",$request);

    #send back a packet
    socket_write($client, "ACK!", strlen("ACK!"));
	
    socket_close($client);
 
}

socket_close($server);
