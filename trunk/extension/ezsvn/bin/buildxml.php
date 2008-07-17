<?php
require 'autoload.php';
$cli = eZCLI::instance();
$script = eZScript::instance( array( 
    'description' => ( "SVN XML build tool \n" . "Syncronisation between repositories and environment.\n" . "\n" . "./extension/ezsvn/bin/buildxml.php --file=svn.xml" ) , 
    'use-session' => false , 
    'use-modules' => false , 
    'use-extensions' => true 
) );
$script->startup();
$options = $script->getOptions( "[file:]", "", array( 
    'file' => 'Path of file' 
) );
$script->initialize();
$sys = eZSys::instance();
if ( $options['file'] )
{
    $file = $options['file'];
}
else
{
    $file = 'svn.xml';
}

try
{
    if ( file_exists( $file ) )
    {
        throw new Exception( "File '$file' already exists." );
    }
    $base = array( 
        'url' => "http://pubsvn.ez.no/nextgen/trunk/" , 
        'revision' => "HEAD" 
    );
    $workingcopies = xrowSVNWorkingCopy::getFromPath( '.' );
    xrowSVN::buildXML( $file, $workingcopies, $base );
}
catch ( Exception $e )
{
    echo $e->getMessage() . "\n";
}
$script->shutdown();
?>