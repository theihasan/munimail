<?php
$socket = fsockopen('localhost', 25, $errno, $errstr, 30);
if (!$socket) {
    die("Connection failed: $errstr ($errno)");
}

// Read greeting
echo "Server: " . fgets($socket, 512);

// Send EHLO
fwrite($socket, "EHLO test.domain.com\r\n");
echo "Client: EHLO test.domain.com\n";

// Read all EHLO responses
while($line = fgets($socket, 512)) {
    echo "Server: " . $line;
    if(substr($line, 3, 1) === ' ') break;
}

//Send MAIL command
fwrite($socket, "MAIL FROM: <test@test.com>\r\n");
echo "Client: MAIL FROM: <test@test.com>\n";

//Read response
while($line = fgets($socket, 512)) {
    echo "Server: " . $line;
    if(substr($line, 3, 1) === ' ') break;
}

// Send QUIT
fwrite($socket, "QUIT\r\n");
echo "Client: QUIT\n";
echo "Server: " . fgets($socket, 512);

fclose($socket);
?>