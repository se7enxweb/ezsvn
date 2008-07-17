<?php
require 'autoload.php';

$input = new ezcConsoleInput( );
$output = new ezcConsoleOutput( );

$helpOption = $input->registerOption( new ezcConsoleOption( 'h', 'help' ) );
$helpOption->isHelpOption = true;

$fileOption = $input->registerOption( new ezcConsoleOption( 'f', 'file', ezcConsoleInput::TYPE_STRING ) );
$fileOption->shorthelp = "Path to XML definition file.";
$fileOption->longhelp = "Path to XML definition file.";
$fileOption->mandatory = false;
$fileOption->default = 'svn.xml';

$baseOption = $input->registerOption( new ezcConsoleOption( 'b', 'base', ezcConsoleInput::TYPE_NONE ) );
$baseOption->shorthelp = "Update (svn export) base.";
$baseOption->longhelp = "Update (svn export) base.";
$baseOption->mandatory = false;
$baseOption->default = false;

try
{
    $input->process();
}
catch ( ezcConsoleException $e )
{
    $output->outputText( $e->getMessage() );
}

if ( $helpOption->value === true )
{
    $output->outputText( $input->getHelpText( "SVN Client" ) );
    $output->outputText( $input->getHelpText( "Syncronisation between repositories and environment." ) );
    $output->outputText( $input->getSynopsis() );
    foreach ( $input->getOptions() as $option )
    {
        $output->outputText( "-{$option->short}/{$option->long}: {$option->shorthelp}" );
    }
    exit( 0 );
}

$file = $fileOption->value;



try
{
    $svn = new xrowSVN( $file );
}
catch ( Exception $e )
{
    echo $e->getMessage()."\n";
}

if ( is_object( $svn ) )
{
	if ( $baseOption->value )
	{
	    $output->outputText( 'Updating base.' );
	    $svn->updateBase();
	    $output->outputText( 'Base updated.' );
	}
	$output->outputText( 'Updating checkouts.' ); 
	$svn->update();
    $output->outputText( 'Checkouts updated.' ); 

    if ( !$options['ignore-cache']  )
    {
        eZCache::clearAll();
        $output->outputText( 'Cleared all caches.' );
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
