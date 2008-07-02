<?php
require 'autoload.php';
if ( !isset( $cli ) or !is_object( $cli ) )
{

    $cli = eZCLI::instance();
    $script = eZScript::instance( array( 'description' => ( "eZ publish SVN Client \n" .
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

$sys = eZSys::instance();

try
{
    $svn = new xrowSVN( 'svn.xml' );
}catch ( Exception $e )
{
    echo $e->getMessage()."\n";
}
 
if ( is_object( $svn ) )
{
	if ( $options['include-base'] )
	{
	    $cli->output( 'Updating base.' );
	    $svn->updateBase();
	    $cli->output( 'Base updated.' );
	}   
	$cli->output( 'Updating checkouts.' ); 
	$svn->update();
    $cli->output( 'Checkouts updated.' ); 

    if ( !$options['ignore-cache']  )
    {
        eZCache::clearAll();
        $cli->output( 'Cleared all caches.' );
    }
	if ( !$isCRON )
		return $script->shutdown();
}

if ( !$isCRON )
{
	$script->showHelp();
	return $script->shutdown();
}
?>
