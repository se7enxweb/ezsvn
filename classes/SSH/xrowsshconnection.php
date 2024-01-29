<?php

class xrowSSHConnection
{
    private $server;
    private $port;
    private $user;
    private $password;

    /**
     * 
     */
    function __construct( $server, $port = 22 )
    {
        $this->server = $server;
        $this->port = $port;
    }

    /**
     * Enter description here...
     *
     * @return resource
     */
    function init()
    {
        if ( $connection = ssh2_connect( $this->server, $this->port ) )
        {
            if ( ssh2_auth_password( $connection, $this->user, $this->password ) )
            {
                return $connection;
            }
            else
            {
                throw new Exception( 'Login Failed' );
            }
        }
        else
        {
            throw new Exception( 'Can not connected to ' . $this->server . ':' . $this->port );
        }
        #$this->shell=ssh2_shell( $this->conn, 'bash'); 
        #stream_set_blocking($this->shell, true);
    }

    function login( $user, $password )
    {
        $this->user = $user;
        $this->password = $password;
    }

    /* Creates a new connection wiht the same data
     * 
     */
    function __clone()
    {
        $new = new xrowSSHConnection( $this->server, $this->port );
        $new = $new->login( $this->user, $this->password );
        return $new;
        ;
    }

}
?>
