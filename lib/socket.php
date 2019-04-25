<?php

interface SocketServer
{
    const ERROR_NO_TYPE     = 0;
    const ERROR_CREATE      = 1;
    const ERROR_BIND        = 2;
    const ERROR_LISTEN      = 3;
    const ERROR_ACCEPT      = 4;
    const ERROR_READ        = 5;
    const ERROR_WRITE       = 6;
    const ERROR_PEER_NAME   = 7;
    const ERROR_SET_OPTION  = 8;

    public function run();
}

class Server implements SocketServer
{
    const DEFAULT_HOST = '127.0.0.1';
    const DEFAULT_PORT = 1993;

    protected $_host = null;
    protected $_port = null;

    protected $_socket = null;
    protected $_channel = null;

    protected $_byteRead = 2048;

    /**
     * Server constructor.
     *
     * @param int $byteRead The maximum number of bytes read is specified.
     */
    public function __construct($byteRead = 2048)
    {
        set_time_limit(0);
        ob_implicit_flush();

        $options = getopt("i:p:");
        $this->_host = isset($options['i']) ? $options['i'] : Server::DEFAULT_HOST;
        $this->_port = isset($options['p']) ? $options['p'] : Server::DEFAULT_PORT;

        $this->_byteRead = $byteRead;

        $this->init();
    }

    /**
     * @param int $byteRead
     */
    public function setReadByte($byteRead)
    {
        $this->_byteRead = $byteRead;
    }

    public function run()
    {
        do {
            if (($this->_channel = socket_accept($this->_socket)) === false) {
                $this->_errorToServer("", self::ERROR_ACCEPT);
                break;
            }

            do {
                $clientMessage = $this->_getClientMessage();
                if (!$clientMessage) {
                    continue;
                }

                $this->responseToServer("<<< " . $clientMessage);
                $this->_handleClientMessage($clientMessage);

                if ($clientMessage === 'close') {
                    break;
                }

            } while (true);
            socket_close($this->_channel);
        } while (true);

        socket_close($this->_socket);
    }

    /**
     * @param string $message
     */
    public function responseToClient($message)
    {
        $message = $this->_prepareMessage($message);
        if (socket_write($this->_channel, $message, strlen($message)) === false){
            $this->_errorToServer("", self::ERROR_WRITE);
        }
    }

    /**
     * @param string $message
     */
    public function responseToServer($message)
    {
        echo $this->_prepareMessage($message);
    }

    protected function init()
    {
        if (($this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
            $this->_errorToServer("", self::ERROR_CREATE);
        }

        if (!socket_set_option($this->_socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            $this->_errorToServer("", self::ERROR_SET_OPTION);
        }

        if (socket_bind($this->_socket, $this->_host, $this->_port) === false) {
            $this->_errorToServer("", self::ERROR_BIND);
        }

        if (socket_listen($this->_socket) === false) {
            $this->_errorToServer("", self::ERROR_LISTEN);
        }

        $this->responseToServer("Start server.");
        $this->responseToServer("Host:" . $this->_host);
        $this->responseToServer("Port:" . $this->_port);
    }

    /**
     * @param string $message
     * @param int $type
     */
    protected function _errorToServer($message, $type = self::ERROR_NO_TYPE)
    {
        if (!empty($message)) {
            $message .= "\n";
        }

        if ($type > 0) {
            $resource = $this->_socket;

            switch ($type) {
                case self::ERROR_CREATE:
                    $message = "Create error: \n";
                    break;
                case self::ERROR_BIND:
                    $message = "Bind error: \n";
                    break;
                case self::ERROR_LISTEN:
                    $message = "Listen error: \n";
                    break;
                case self::ERROR_ACCEPT:
                    $message = "Accept error: \n";
                    $resource = $this->_channel;
                    break;
                case self::ERROR_READ:
                    $message = "Read error: \n";
                    break;
                case self::ERROR_WRITE:
                    $message = "Write error: \n";
                    $resource = $this->_channel;
                    break;
                case self::ERROR_PEER_NAME:
                    $message = "Get peer name error: \n";
                    $resource = $this->_channel;
                    break;
                case self::ERROR_SET_OPTION:
                    $message = "Set option error: \n";
                    break;
            }

            $message .= socket_strerror(socket_last_error($resource));
        }

        $this->responseToServer($message);
    }

    /**
     * @return string|false
     */
    protected function _getClientMessage()
    {
        if (($clientMessage = socket_read($this->_channel, $this->_byteRead, PHP_NORMAL_READ)) === false) {
            $this->_errorToServer("", self::ERROR_READ);
        } else {
            return trim(mb_strtolower($clientMessage));
        }

        return false;
    }

    /**
     * Will returned client IP and PORT (e.g. "127.0.0.1:30000")
     *
     * @return false|string
     */
    protected function _getClientIPAndHost()
    {
        if (socket_getpeername($this->_channel, $clientIP, $clientPort) === false) {
            $this->_errorToServer("", self::ERROR_PEER_NAME);
        } else {
            return $clientIP . ':' . $clientPort;
        }

        return false;
    }

    /**
     * Handle and send response to client
     *
     * @param string $message
     */
    protected function _handleClientMessage($message)
    {
        switch ($message) {
            case 'connect':
                $response = "OK";
                break;
            case 'whoami':
                $client = $this->_getClientIPAndHost();
                $response = $client ? $client : "Sorry, I don't know. Who are you?";
                break;
            case 'close':
                $response = "OK";
                break;
            default:
                $response = "What you mean? Use: connect|whoami|close";
        }

        $this->responseToServer(">>> ". $response);
        $this->responseToClient($response);
    }

    /**
     * @param string $message
     * @return string
     */
    protected function _prepareMessage($message)
    {
        $message .= "\n";

        return $message;
    }

}
