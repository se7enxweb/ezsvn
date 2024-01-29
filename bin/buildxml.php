<?php
require 'autoload.php';

ezcBase::addClassRepository( './extension/ezsvn/classes', './extension/ezsvn/classes/autoloads' );

$input = new ezcConsoleInput( );
$output = new ezcConsoleOutput( );

$helpOption = $input->registerOption( new ezcConsoleOption( 'h', 'help' ) );
$helpOption->isHelpOption = true;

$fileOption = $input->registerOption( new ezcConsoleOption( 'f', 'file', ezcConsoleInput::TYPE_STRING ) );
$fileOption->shorthelp = "Path to output file.";
$fileOption->longhelp = "Path to output file.";
$fileOption->mandatory = false;
$fileOption->default = 'svn.xml';

$overrideOption = $input->registerOption( new ezcConsoleOption( 'o', 'override', ezcConsoleInput::TYPE_NONE ) );
$overrideOption->shorthelp = "Override existing output file.";
$overrideOption->longhelp = "Override existing output file.";
$overrideOption->mandatory = false;
$overrideOption->default = false;
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
    $output->outputText( $input->getHelpText( "Tool to create a XML file about svn information of a project" ) );
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
    if ( file_exists( $file ) and !$overrideOption->value )
    {
        throw new Exception( "File '$file' already exists." );
    }
    $base = array( 
        'url' => "http://pubsvn.ez.no/nextgen/trunk/" , 
        'revision' => "HEAD" 
    );

    xrowSVN::buildXML( $file, xrowSVNWorkingCopy::getFromPath( '.' ), xrowSVNWorkingCopy::getRootFromPath( '.' ) );
    $output->outputText( "Done. $file created." );
}
catch ( Exception $e )
{
    $output->outputText( $e->getMessage() );
}
?>
