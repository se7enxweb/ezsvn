<?php

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );
include_once( 'kernel/common/i18n.php' );
include_once( 'kernel/classes/ezcache.php' );
include_once( 'extension/ezsvn/classes/ezsvn.php' );

$cli =& eZCLI::instance();
$script =& eZScript::instance( array( 'description' => ( "eZ publish SVN base update \n" .
                                                         "Allows for easy updating the sources\n" .
                                                         "\n" .
                                                         "./extension/ezsvn/bin/updatebase.php --name=trunk" ),
                                      'use-session' => false,
                                      'use-modules' => false,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[name:]",
                                "",
                                array( 'name' => 'Name or alias of base sources') );
$script->initialize();

$sys =& eZSys::instance();
$ini =& eZINI::instance( 'svn.ini' );
if ( !$options['name'] )
{
    $script->showHelp();
    return $script->shutdown();
}

$repository = array_merge( $ini->variable( 'Repositories', $options['name'] ), array( 'name' => $options['name'] ) );
$cli->output( 'Getting sources from '. $repository['url'] );
eZSVN::execute( $repository );

eZCache::clearAll();

$cli->output( 'Sources updated.' );
return $script->shutdown();
?>
