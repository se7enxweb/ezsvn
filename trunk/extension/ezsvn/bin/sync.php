<?php
require 'autoload.php';

$input = new ezcConsoleInput( );
$output = new ezcConsoleOutput( );

$helpOption = $input->registerOption( new ezcConsoleOption( 'h', 'help' ) );
$helpOption->isHelpOption = true;

$fileOption = $input->registerOption( new ezcConsoleOption( 'i', 'ini', ezcConsoleInput::TYPE_STRING ) );
$fileOption->shorthelp = "Path to mirror.ini definition file.";
$fileOption->longhelp = "Path to mirror.ini definition file.";
$fileOption->mandatory = false;
$fileOption->default = false;

$mirrorOption = $input->registerOption( new ezcConsoleOption( 'm', 'mirror', ezcConsoleInput::TYPE_STRING ) );
$mirrorOption->shorthelp = "Default mirror name";
$mirrorOption->longhelp = "Default mirror name";
$mirrorOption->mandatory = false;
$mirrorOption->default = 'Default';

$cacheOption = $input->registerOption( new ezcConsoleOption( 'c', 'cache', ezcConsoleInput::TYPE_NONE ) );
$cacheOption->shorthelp = "Clear Cache";
$cacheOption->longhelp = "Clear Cache";
$cacheOption->mandatory = false;
$cacheOption->default = false;

$binaryOption = $input->registerOption( new ezcConsoleOption( 'b', 'binary', ezcConsoleInput::TYPE_NONE ) );
$binaryOption->shorthelp = "Include binary";
$binaryOption->longhelp = "Include binary";
$binaryOption->mandatory = false;
$binaryOption->default = false;

$databaseOption = $input->registerOption( new ezcConsoleOption( 'd', 'database', ezcConsoleInput::TYPE_NONE ) );
$databaseOption->shorthelp = "Include database";
$databaseOption->longhelp = "Include database";
$databaseOption->mandatory = false;
$databaseOption->default = true;

$verboseOption = $input->registerOption( new ezcConsoleOption( 'v', 'verbose', ezcConsoleInput::TYPE_NONE ) );
$verboseOption->shorthelp = "verbose";
$verboseOption->longhelp = "verbose";
$verboseOption->mandatory = false;
$verboseOption->default = false;

try
{
    $input->process();
}
catch ( ezcConsoleException $e )
{
    $output->outputText( $e->getMessage() );
}
if ( $fileOption->value == false )
{
    if ( file_exists( 'settings/override/mirror.ini' ) )
    {
        $file = 'settings/override/mirror.ini';
    }
    elseif ( file_exists( 'mirror.ini' ) )
    {
        $file = 'mirror.ini';
    }
}
else
{
    $file = $fileOption->value;
}
$ini = $reader = new ezcConfigurationIniReader( $file );
$cfg = $reader->load();
if ( $cfg->hasGroup( $mirrorOption->value ) )
{
    $config = $cfg->getSettingsInGroup( $mirrorOption->value );
}
else
{
    $output->outputLine( 'Mirror definition not found.' );
    exit( 0 );
}

$time = time();
$archivefilename = "archive.tgz";
$dbdatafilename = "database_data.sql";
$dbtstructurefilename = "database_structure.sql";
$files = array( 
    $archivefilename , 
    $dbdatafilename , 
    $dbtstructurefilename 
);
$connection = new xrowSSHConnection( $config['RemoteServer'], $config['RemotePort'] );
$connection->login( $config['RemoteUser'], $config['RemotePassword'] );
try
{
    $ssh = new xrowSSH( $connection );
}
catch ( ezcConsoleException $e )
{
    $output->outputText( $e->getMessage() );
    exit( 0 );
}

if ( $helpOption->value )
{
    $output->outputLine( $input->getHelpText( "Mirror tool" ) );
    $output->outputText( $input->getHelpText( "Syncronisation between live and staging." ) );
    $output->outputText( $input->getSynopsis() );
    foreach ( $input->getOptions() as $option )
    {
        $output->outputText( "-{$option->short}/{$option->long}: {$option->shorthelp}" );
    }
    exit( 0 );
}

$ssh2 = new xrowSSH( $connection );
foreach ( $files as $file )
{
    $file = $config['RemoteEZPublishRoot'] . '/' . $file;
    $remotestatinfo = $ssh2->stat( $file );
    if ( $remotestatinfo )
    {
        $ssh2->unlink( $file );
        if ( $verboseOption->value )
        {
            $output->outputLine( "Deleting old file <$file>" );
        }
    }
}

if ( $databaseOption->value )
{
    $command = new xrowConsoleCommand( "mysqldump" );
    $command->addShortOption( 'C' );
    $command->addLongOption( 'no-data' );
    $command->addLongOption( 'add-drop-table' );
    $command->addLongOption( 'single-transaction' );
    $command->addShortOption( 'n' );
    $command->addLongOption( 'user', $config['RemoteDatabaseUser'] );
    $command->addLongOption( 'password', $config['RemoteDatabasePassword'] );
    $command->addLongOption( 'host', $config['RemoteDatabaseHost'] );
    $command->addArgument( $config['RemoteDatabaseName'] );
    $command->redirectOuputAfter( $config['RemoteEZPublishRoot'] . '/' . $dbtstructurefilename, '>' );
    if ( $verboseOption->value )
    {
        $output->outputLine( "Executing command <" . $command->getCommand() . ">" );
    }
    $ssh->exec_cmd( $command->getCommand() );
    $output->outputLine( 'Database structure dumped.' );
    
    $ignoretables = array( 
        'ezsession' , 
        'ezsearch_object_word_link' , 
        'ezsearch_return_count' , 
        'ezsearch_search_phrase' , 
        'ezsearch_word' 
    );
    $ignoretablestr = '';
    $command = new xrowConsoleCommand( "mysqldump" );
    foreach ( $ignoretables as $ignoretable )
    {
        $command->addLongOption( 'ignore-table', $config['RemoteDatabaseName'] . '.' . $ignoretable );
    }
    $command->addShortOption( 'C' );
    $command->addLongOption( 'no-create-info' );
    $command->addLongOption( 'add-drop-table' );
    $command->addLongOption( 'single-transaction' );
    $command->addShortOption( 'n' );
    $command->addLongOption( 'user', $config['RemoteDatabaseUser'] );
    $command->addLongOption( 'password', $config['RemoteDatabasePassword'] );
    $command->addLongOption( 'host', $config['RemoteDatabaseHost'] );
    $command->addArgument( $config['RemoteDatabaseName'] );
    $command->redirectOuputAfter( $config['RemoteEZPublishRoot'] . '/' . $dbdatafilename, '>' );
    if ( $verboseOption->value )
    {
        $output->outputLine( "Executing command <" . $command->getCommand() . ">" );
    }
    $ssh->exec_cmd( $command->getCommand() );
    $output->outputLine( 'Database data dumped.' );

}

waitTillFileIsReady( $config, $config['RemoteEZPublishRoot'] . '/' . $dbdatafilename, $output );
if ( $binaryOption->value === true and $databaseOption->value === true )
{
    $cmd = "cd " . $config['RemoteEZPublishRoot'] . " && tar -czf $archivefilename var/*/storage $dbdatafilename $dbtstructurefilename";
    $ssh->exec_cmd( $cmd );
}
elseif ( $databaseOption->value )
{
    $cmd = "cd " . $config['RemoteEZPublishRoot'] . " && tar -czf $archivefilename $dbdatafilename $dbtstructurefilename";
    $ssh->exec_cmd( $cmd );
}
waitTillFileIsReady( $config, $config['RemoteEZPublishRoot'] . '/' . $archivefilename, $output );

$ssh2 = new xrowSSH( $connection );
$remotestatinfo = $ssh2->stat( $config['RemoteEZPublishRoot'] . '/' . $archivefilename );

$output->outputLine( "Start download <" . $config['RemoteEZPublishRoot'] . '/' . $archivefilename . "> " . $remotestatinfo['size'] . " bytes." );

$ssh2->getFile( $config['RemoteEZPublishRoot'] . '/' . $archivefilename, $archivefilename );

$output->outputLine( "All data is downloaded." );

$ssh->unlink( $dbtstructurefilename );
$ssh->unlink( $dbdatafilename );
$ssh->unlink( $archivefilename );
unset( $ssh );
$output->outputLine( 'Remote session completed.' );

$localstatinfo = stat( $archivefilename );

$output->outputLine( "Local file <" . $archivefilename . "> size: " . $localstatinfo['size'] . " bytes" );
$output->outputLine( 'Extracting data.' );
if ( file_exists( $archivefilename ) )
{
    /* @TODO http://issues.ez.no/IssueView.php?Id=13501&activeItem=1
 * 
    $tar = ezcArchive::open( $archivefilename );
    $tar->extract( "." );
    unset( $tar );
*/
    exec( "tar -xzf $archivefilename", $out, $retval );
    unlink( $archivefilename );

}
$output->outputLine( 'Injecting into database.' );
$command = new xrowConsoleCommand( "mysql" );
$command->addShortOption( 'e', 'CREATE DATABASE IF NOT EXISTS ' . $config['LocalDatabaseName'] );
$command->addLongOption( 'user', $config['LocalDatabaseUser'] );
$command->addLongOption( 'password', $config['LocalDatabasePassword'] );
$command->addLongOption( 'host', $config['LocalDatabaseHost'] );
if ( $verboseOption->value )
{
    $output->outputLine( "Executing command <" . $command->getCommand() . ">" );
}
exec( $command->getCommand(), $out, $retval );
if ( file_exists( $dbtstructurefilename ) )
{
    $command = new xrowConsoleCommand( "mysql" );
    $command->addShortOption( 'f' );
    $command->addLongOption( 'user', $config['LocalDatabaseUser'] );
    $command->addLongOption( 'password', $config['LocalDatabasePassword'] );
    $command->addLongOption( 'host', $config['LocalDatabaseHost'] );
    $command->addArgument( $config['LocalDatabaseName'] );
    $command->redirectOuputAfter( $dbtstructurefilename, '<' );
    if ( $verboseOption->value )
    {
        $output->outputLine( "Executing command <" . $command->getCommand() . ">" );
    }
    exec( $command->getCommand(), $out, $retval );
    unlink( $dbtstructurefilename );
}
if ( file_exists( $dbdatafilename ) )
{
    $command = new xrowConsoleCommand( "mysql" );
    $command->addShortOption( 'f' );
    $command->addLongOption( 'user', $config['LocalDatabaseUser'] );
    $command->addLongOption( 'password', $config['LocalDatabasePassword'] );
    $command->addLongOption( 'host', $config['LocalDatabaseHost'] );
    $command->addArgument( $config['LocalDatabaseName'] );
    $command->redirectOuputAfter( $dbdatafilename, '<' );
    if ( $verboseOption->value )
    {
        $output->outputLine( "Executing command <" . $command->getCommand() . ">" );
    }
    exec( $command->getCommand(), $out, $retval );
    unlink( $dbdatafilename );
}
if ( $cacheOption->value )
{
    $command = new xrowConsoleCommand( "php5 bin/php/ezcache.php" );
    $command->addLongOption( 'clear-all' );
    $command->addLongOption( 'purge' );
    exec( $command->getCommand(), $out, $retval );
}
$command = new xrowConsoleCommand( "chmod -Rf 777 var" );
exec( $command->getCommand(), $out, $retval );
$output->outputLine( 'Done.' );
exit( 1 );

function waitTillFileIsReady( $config, $file, $output, $sleep = 10 )
{
    global $connection;
    
    $ssh2 = new xrowSSH( $connection );
    
    $progress = new ezcConsoleProgressbar( $output, 10000, array( 
        'step' => $sleep 
    ) );
    $progress->options->emptyChar = '-';
    $progress->options->progressChar = '#';
    $progress->options->formatString = "Checking file <$file>: %act%/%max% sec passed [%bar%]\n";
    $i = 0;
    while ( $i ++ < 10001 )
    {
        $remotestatinfo = $ssh2->stat( $file );
        sleep( $sleep );
        $remotestatinfo2 = $ssh2->stat( $file );
        if ( $remotestatinfo['size'] == $remotestatinfo2['size'] )
        {
            break;
        }
        if ( $i == 10000 )
        {
            throw new Exception( "Waited 10000 rounds. Aborting." );
        }
        $progress->advance();
    }
    $progress->finish();

}
?>