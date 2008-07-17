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

$cacheOption = $input->registerOption( new ezcConsoleOption( 'c', 'cache', ezcConsoleInput::TYPE_NONE ) );
$cacheOption->shorthelp = "Clear cache.";
$cacheOption->longhelp = "Clear cache.";
$cacheOption->mandatory = false;
$cacheOption->default = false;

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
    $output->outputLine( $input->getHelpText( "SVN Client" ) );
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
    $output->outputText( $e->getMessage() );
}

if ( is_object( $svn ) )
{
    if ( $baseOption->value )
    {
        $output->outputLine( 'Updating base.' );
        try
        {
            $svn->updateBase();
        }
        catch ( Exception $e )
        {
            $output->outputText( $e->getMessage() );
        }
        
        $output->outputLine( 'Base updated.' );
    }
    $output->outputLine( 'Updating checkouts.' );
    try
    {
        foreach ( $svn->config->checkout as $checkout )
        {
            $output->outputText( "Updating... '".$checkout['path']."'. " );
            try
            {
                $svn->update( $checkout );
                $output->outputLine( "Done." );
            }
            catch ( xrowSVNUpdateException $e )
            {
                $output->outputText( $e->getMessage() );
                $output->outputText( "We might need more exceptions!" );
                $output->outputLine( );          
                $question = ezcConsoleQuestionDialog::YesNoQuestion( $output, "Do you want try to cleanup?", "n" );
                $output->outputLine( );
                $answer = ezcConsoleDialogViewer::displayDialog( $question );
 
                if ( $answer == 'y' )
                {
                    $output->outputLine( 'Cleaning...' );
                    $e->wc->cleanup();
                    $output->outputLine( 'Trying again...' );
                    $svn->update( $e->wc->toArray() );
                    $output->outputLine( "Done." );
                }
                else
                {
                    $output->outputLine( "You choose no. skipping." );
                }
            }
            catch ( xrowSVNLockException $e )
            {
                $output->outputLine( "Skipped." );
                $output->outputLine( $e->getMessage() );
            }
            catch ( xrowSVNDifferentPathException $e )
            {
                $output->outputText( $e->getMessage() );
                $output->outputLine( );          
                $question = ezcConsoleQuestionDialog::YesNoQuestion( $output, "Do you want to delete the existing?", "n" );
                $output->outputLine( );
                $answer = ezcConsoleDialogViewer::displayDialog( $question );
 
                if ( $answer == 'y' )
                {
                    $output->outputLine( 'Deleting...' );
                    ezcBaseFile::removeRecursive( $e->wc->path );
                    $output->outputLine( 'Trying again...' );
                    $svn->update( $e->wc->toArray() );
                    $output->outputLine( "Done." );
                }
                else
                {
                    $output->outputLine( "You choose no. skipping." );
                }
            }
            catch ( Exception $e )
            {
                $output->outputText( $e->getMessage() );
            }
        }
    }
    catch ( Exception $e )
    {
        $output->outputText( $e->getMessage() );
    }
    $output->outputLine( 'Checkouts updated.' );
    
    if ( $cacheOption->value and class_exists( 'eZCache' ) )
    {
        eZCache::clearAll();
        $output->outputLine( 'Cleared all caches.' );
    }
}
?>