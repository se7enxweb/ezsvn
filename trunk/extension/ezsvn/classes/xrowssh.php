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
	function __construct($user, $pass, $host, $port=22) {
		if ($this->connect($host,$port)) {
			if ($this->auth_pwd($user,$pass))
			{
				#$this->shell=ssh2_shell( $this->conn, 'bash'); 
				#stream_set_blocking($this->shell, true);
				return true;
			}
			else
			{
				return false;
			}
		} else {
			return false;
		}
	}

	function connect($host,$port=22) {
		if ($this->conn = ssh2_connect($host, $port)) {
			return true;
		} else {
			throw new Exception( 'Can not connected to '.$host.':'.$port );

		}
	}

	function auth_pwd($u,$p) {
		if (ssh2_auth_password($this->conn, $u, $p)) {
			return true;
		} else {
			throw new Exception(  'Login Failed' );
		}
	}

	function send_file($localFile,$remoteFile,$permision) {
		if (@ssh2_scp_send($this->conn, $localFile, $remoteFile, $permision)) {
			return true;
		} else {
			$this->error = 'Can not transfer file';
			return false;
		}
	}

	function get_file($remoteFile,$localFile) {
		if (ssh2_scp_recv($this->conn, $remoteFile, $localFile)) {
			return true;
		} else {
			throw new Exception( 'Can not receive file' );
		}
	}
	function exec_cmd($cmd) {
		$this->stream = ssh2_exec($this->conn, $cmd, false);
		stream_set_blocking( $this->stream, true );
	}

	function get_output() {
		$line = '';
		while ($get=fgets($this->stream)) {
			$line.=$get;
		}
		return $line;
	}
	function unlink( $filename  )
	{
		$sftp = ssh2_sftp( $this->conn );
		return ssh2_sftp_unlink( $sftp, $filename );
	}
	function __destruct() {
		// if disconnect function is available call it..
		if ( function_exists('ssh2_disconnect') ) {
			ssh2_disconnect($this->conn);
		} else { // if no disconnect func is available, close conn, unset var
			@fclose($this->conn);
			unset($this->conn);
		}
		// return null always
		return NULL;
	}
}

?>