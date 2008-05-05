<?php
# don't know why i needed thm before remove those parts later
#include_once( 'lib/ezutils/classes/ezdebug.php' );
#include_once( 'kernel/common/i18n.php' );

if ( !isset( $cli ) or  !is_object( $cli ) ){

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "eZ publish SVN Client \n" .
                                                         "Syncronisation between repositories and environment.\n" .
                                                         "\n" .
                                                         "./extension/ezsvn/bin/ezsvn.php --user=admin --password=publish --config-id=12345" ),
                                      'use-session' => false,
                                      'use-modules' => false,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[config-id:][user:][password:][server:][server-port:][include-base][ignore-cache]",
                                "",
                                array( 'config-id' => 'Contentobject id or remote id of remote configuration object',
                                       'user' => 'Username on remote server',
                                       'password' => 'Password on remote server',
                                       'server' => 'Soap Server FQDN',
				                       'server-port' => 'Server Port',
                                       'include-base' => 'Update the base sources',
                                       'ignore-cache' => 'Do not clear caches' ) );
$script->initialize();
$isCRON=false;
}
else
{
	$isCRON=true;
}

$sys =& eZSys::instance();

$ini =& eZINI::instance( 'svn.ini' );
if ( !$options['user'] )
	$options['user'] = $ini->variable( 'Settings', 'User' );
if ( !$options['password'] )
	$options['password'] = $ini->variable( 'Settings', 'Password' );
if ( !$options['server'] )
	$options['server'] = $ini->variable( 'Settings', 'Server' );
if ( !$options['server-port'] )
	$options['server-port'] = $ini->variable( 'Settings', 'Port' );
if ( !$options['config-id'] )
	$options['config-id'] = $ini->variable( 'Settings', 'ConfigID' );
if ( $options['config-id'] )
{
	$ini =& eZINI::instance("svn.ini");
	if ($options['server'])
	{
		$server = $options['server'];
	}
	else
	{
		$server = $ini->variable('Settings', 'Server');
	}
	if ($options['server-port'])
	{
		$serverport = $options['server-port'];
	}
	else
	{
		$serverport = $ini->variable('Settings', 'Port');
	}
    $cli->output( 'Running configuration #'.$options['config-id'].' from server '. $server );
	$cli->output( '' );
    // include client classes
	include_once( "lib/ezsoap/classes/ezsoapclient.php" );
	include_once( "lib/ezsoap/classes/ezsoaprequest.php" );

	// create a new client
	if ( $serverport )
		$client = new eZSOAPClient( $server, '/svn/configserver', $serverport );
	else
		$client = new eZSOAPClient( $server, '/svn/configserver' );
	$client->setLogin( $options['user'] );
	$client->setPassword( $options['password'] ); 

	$namespace = "http://soapinterop.org/";

	// create the SOAP request object
	$request = new eZSOAPRequest( "eZSOAPsvn::config", 'http://' . $server . '/svn/configserver' );

	// add parameters to the request #'3f5bedf1f158f517d2d14c8b475eeae2'
	$request->addParameter( "remote_id", $options['config-id'] );

	// send the request to the server and fetch the response
	$response = $client->send( $request );

	if ( !is_object( $response ) )
	{
	    $cli->output(  "Server didn't respond." );
	    if ( !$isCRON )
			return $script->shutdown();
	}
	// check if the server returned a fault, if not print out the result
	if ( is_object( $response ) and $response->isFault() )
	{
		$cli->output(  "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
		if ( !$isCRON )
			return $script->shutdown( 1 );
	}
	elseif ( is_object( $response ) )
	{
		$configarray = $response->value();
		include_once( 'extension/ezsvn/classes/ezsvn.php' );
		if ( !$options['include-base'] )
			$configarray  = array_slice( $configarray, 1);
		foreach ( $configarray as $repository )
    	{
    		$cli->output( $repository['url'] );
            $result = eZSVN::execute( $repository, true );
			if ( $result === false )
				$cli->output( 'An error occured.' );
			$cli->output( '' );
    	}
        $cli->output( 'Sources updated.' );

        if ( !$options['ignore-cache']  )
        {
            include_once( 'kernel/classes/ezcache.php' );
            eZCache::clearAll();
            $cli->output( 'Cleared all caches.' );
        }
		if ( !$isCRON )
			return $script->shutdown();
	}
}
if ( !$isCRON )
{
	$script->showHelp();
	return $script->shutdown();
}
?>
