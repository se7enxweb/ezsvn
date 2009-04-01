<?php

/*
 * Notes
 * ssh2_shell() seems to interfere with file transfer. The transfer seem got get interrupted. I found broken files.
 */
class xrowSSH
{
    public $conn;
    public $error;
    public $stream;
    public $shell;

    function __construct( xrowSSHConnection $connection )
    {
        $this->conn = $connection->init();
    }

    function sendFile( $localFile, $remoteFile, $permision )
    {
        if ( @ssh2_scp_send( $this->conn, $localFile, $remoteFile, $permision ) )
        {
            return true;
        }
        else
        {
            $this->error = 'Can not transfer file';
            return false;
        }
    }

    function getFile( $remoteFile, $localFile )
    {
        if ( ssh2_scp_recv( $this->conn, $remoteFile, $localFile ) )
        {
            return true;
        }
        else
        {
            throw new Exception( 'Can not receive file' );
        }
    }

    function stat( $path )
    {
        $sftp = ssh2_sftp( $this->conn );
        return @ssh2_sftp_stat( $sftp, $path );
    }

    function exec_cmd( $cmd )
    {
        $this->stream = ssh2_exec( $this->conn, $cmd, false );
        stream_set_blocking( $this->stream, true );
        if ( is_resource( $this->stream ) )
        {
            while ( ! feof( $this->stream ) )
            {
                echo fread( $this->stream, 8192 );
            }
        }
        else
        {
            throw new Exception( "no stream" );
        
        }
    }

    function get_output()
    {
        $line = '';
        while ( $get = fgets( $this->stream ) )
        {
            $line .= $get;
        }
        return $line;
    }

    function unlink( $filename )
    {
        $sftp = ssh2_sftp( $this->conn );
        return ssh2_sftp_unlink( $sftp, $filename );
    }

    function __destruct()
    {
        // if disconnect function is available call it..
        if ( function_exists( 'ssh2_disconnect' ) )
        {
            ssh2_disconnect( $this->conn );
        }
        else
        { // if no disconnect func is available, close conn, unset var
            @fclose( $this->conn );
            unset( $this->conn );
        }
        // return null always
        return NULL;
    }
}

?>