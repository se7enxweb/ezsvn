<?php

// include classes
include_once( "lib/ezsoap/classes/ezsoapclient.php" );
include_once( "lib/ezsoap/classes/ezsoaprequest.php" );

$Module =& $Params['Module'];
include_once( 'kernel/common/template.php' );
$tpl =& eZTemplate::factory();

$http =& eZHTTPTool::instance();

$output = "";
if ( $http->hasPostVariable( 'Execute' ) )
{
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

    // create a new client
    if ( $options['server-port'] )
        $client = new eZSOAPClient( $options['server'], '/svn/configserver', $options['server-port'] );
    else
        $client = new eZSOAPClient( $options['server'], '/svn/configserver' );
    $client->setLogin( $options['user'] );
    $client->setPassword( $options['password'] );

    // create the SOAP request object
    $request = new eZSOAPRequest( "eZSOAPsvn::config", 'http://' . $server . '/svn/configserver' );

    // add parameters to the request #'3f5bedf1f158f517d2d14c8b475eeae2'
    $request->addParameter( "remote_id", $options['config-id'] );

    // send the request to the server and fetch the response
    $response =& $client->send( $request );

    if ( $response->isFault() )
    {
        #$cli->output(  "SOAP fault: " . $response->faultCode(). " - " . $response->faultString() . "" );
    }
    else
    {
        $configarray = $response->value();
        include_once( 'extension/ezsvn/classes/ezsvn.php' );
        if ( !$options['include-base'] )
            $configarray  = array_slice( $configarray, 1);

        foreach ( $configarray as $repository )
        {
            $output .= $repository['url'] . "\n";
            $result = eZSVN::execute( $repository );
			if ( $result === false )
				$output .= 'An error occured.' ."\n";
			if ( is_array( $result ) )
				$output .= join( $result['output'], "\n" ) . "\n";
			$output .= "\n";
        }
        $output .= 'All sources updated.' . "\n";
        include_once( 'kernel/classes/ezcache.php' );
        eZCache::clearAll();
        $output .= 'Cleared all caches.' . "\n";
    }
}
$tpl->setVariable( 'output', $output );
$Result = array();
$Result['left_menu'] = "design:parts/ezadmin/menu.tpl";
$Result['content'] = $tpl->fetch( "design:ezsvn/client.tpl" );
$Result['path'] = array( array( 'url' => false,
                        'text' => 'SVN client' ) );
?>
