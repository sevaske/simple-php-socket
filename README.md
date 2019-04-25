# simple-php-socket

#### Include and run
```php
<?php
require_once "./lib/socket.php";

$server = new Server();
$server->run();
```
#### Start the server
```
$ php index.php -i {host} -p {port}
```

Default host is 127.0.0.1
Default port is 1993

#### Connection to the server
```
$ telnet {host} {port}
```
