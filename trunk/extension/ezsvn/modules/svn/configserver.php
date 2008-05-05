<?php
include_once( "lib/ezsoap/classes/ezsoapserver.php" );
include_once( 'kernel/classes/datatypes/ezuser/ezuser.php' );
include_once( 'extension/ezsvn/modules/svn/soap.php' );

// Get the username and the password.
$loginUsername = $_SERVER['PHP_AUTH_USER'];
$loginPassword = $_SERVER['PHP_AUTH_PW'];

eZUser::loginUser( $loginUsername, $loginPassword );

#$server = new eZSOAPServer();
#$server->registerFunction( 'config' );
# after 3.5 can use 
$server->registerObject( 'eZSOAPsvn' );
$server->processRequest();


eZExecution::cleanExit();
#function config ( $remote_id = false )
#{
#	return eZSOAPsvn::config( $remote_id );
#}
?>
