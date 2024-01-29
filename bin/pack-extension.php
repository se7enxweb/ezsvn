<?php
require 'ezc/Base/ezc_bootstrap.php';
date_default_timezone_set( "UTC" );
$name = 'translationmanagement.zip';
$extensiondir = realpath( dirname( __FILE__ ). '\..\..' );
$dir = realpath( dirname( __FILE__ ). '\..\..\..' );
$output = new ezcConsoleOutput();

$extensions = array();
$d = dir( $extensiondir );
var_dump( $extensiondir );
while (false !== ($entry = $d->read()))
{
	if ( $entry != '.' and $entry != '..' and file_exists( $extensiondir . DIRECTORY_SEPARATOR . $entry . DIRECTORY_SEPARATOR .'extension.xml') )
	{
		$extensions[] =  $entry;
	}
}
$d->close();
if ( count( $extensions ) > 0)
{
foreach ( $extensions as $key => $extension )
{
	$output->outputLine( "[$key]" . " $extension" );
}
	$question = new ezcConsoleQuestionDialog( $output );
	$question->options->text = "Which extension do you want to build?";
	$question->options->showResults = true;
	$question->options->validator = new ezcConsoleQuestionDialogCollectionValidator( array_keys( $extensions ), false, 
	ezcConsoleQuestionDialogCollectionValidator::CONVERT_NONE );
	$answer = ezcConsoleDialogViewer::displayDialog( $question );
	$extension = $extensions[$answer];
	$dir = $extensiondir . DIRECTORY_SEPARATOR . $extension;
	$xml = simplexml_load_file( $dir . '/extension.xml' );
	//while( !in_array( $answer, array_keys( $extensions ) ) )
	//{
	//	$answer = ezcConsoleDialogViewer::displayDialog( $question );
	//}
}
else
{ 
	$output->outputLine( "No extensions found." );
	sleep(15);
	exit(1);
	
}

$filename = $extensiondir . DIRECTORY_SEPARATOR . $xml->{'name'} . '.zip';
if ( file_exists( $filename ) )
{
    unlink( $filename );
}
try 
{
    $archive = ezcArchive::open( $filename, ezcArchive::ZIP );
    echo "Collecting all files.\n";
    if ( $xml->{'exclude'} )
    {
    	$files = ezcBaseFile::findRecursive( $dir, array(), array( '@'.$xml->{'exclude'}.'@' ) );
    }
    else 
    {
    	$files = ezcBaseFile::findRecursive( $dir, array() );
    }
    echo "Packing archive.\n";
    $archive->append( $files, $dir . DIRECTORY_SEPARATOR );

    $archive->close();
    echo "Closing archive $filename.\n";
    
}
catch ( Exception $e )
{
    echo $e->__toString();
    sleep(15);
}
sleep(15);
?>