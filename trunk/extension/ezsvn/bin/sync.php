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
try
{
    $ssh = new xrowSSH( $config['RemoteUser'], $config['RemotePassword'], $config['RemoteServer'], $config['RemotePort'] );
}
catch ( ezcConsoleException $e )
{
    $output->outputText( $e->getMessage() );
    exit( 0 );
}

if ( $helpOption->value === true )
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

$output->outputLine( "Starting." );

if ( $databaseOption->value === true )
{
    
    $command = new xrowConsoleCommand( "cd " . $config['RemoteEZPublishRoot'] . " && mysqldump" );
    $command->addShortOption( 'C' );
    $command->addLongOption( 'no-data' );
    $command->addLongOption( 'add-drop-table' );
    $command->addLongOption( 'single-transaction' );
    $command->addShortOption( 'n' );
    $command->addLongOption( 'user', $config['RemoteDatabaseUser'] );
    $command->addLongOption( 'password', $config['RemoteDatabasePassword'] );
    $command->addLongOption( 'host', $config['RemoteDatabaseHost'] );
    $command->addArgument( $config['RemoteDatabaseName'] );
    $command->redirectOuputAfter( $dbtstructurefilename, '>' );
    $ssh->exec_cmd( $command->getCommand() );
    echo $command->getCommand();
    $output->outputLine( 'Database structure dumped.' );
    
    $ignoretables = array( 
        'ezsession' , 
        'ezsearch_object_word_link' , 
        'ezsearch_return_count' , 
        'ezsearch_search_phrase' , 
        'ezsearch_word' 
    );
    $ignoretablestr = '';
    $command = new xrowConsoleCommand( "cd " . $config['RemoteEZPublishRoot'] . " && mysqldump" );
    foreach ( $ignoretables as $ignoretable )
    {
        $command->addLongOption( 'ignore-table', $config['RemoteDatabaseName'] . '.' . $ignoretable );
    }
    $command->addLongOption( 'no-create-info' );
    $command->addLongOption( 'add-drop-table' );
    $command->addLongOption( 'single-transaction' );
    $command->addShortOption( 'n' );
    $command->addLongOption( 'user', $config['RemoteDatabaseUser'] );
    $command->addLongOption( 'password', $config['RemoteDatabasePassword'] );
    $command->addLongOption( 'host', $config['RemoteDatabaseHost'] );
    $command->addArgument( $config['RemoteDatabaseName'] );
    $command->redirectOuputAfter( $dbdatafilename, '>' );
    $ssh->exec_cmd( $command->getCommand() );
    $output->outputLine( 'Database data dumped.' );

}
waitTillFileIsReady( $config, $config['RemoteEZPublishRoot'] . '/' . $dbdatafilename, $output);
if ( $binaryOption->value === true and $databaseOption->value === true )
{
    $cmd = "cd " . $config['RemoteEZPublishRoot'] . " && tar -czf $archivefilename var/*/storage $dbdatafilename $dbtstructurefilename";
    $ssh->exec_cmd( $cmd );
}
elseif ( $databaseOption->value === true )
{
    $cmd = "cd " . $config['RemoteEZPublishRoot'] . " && tar -czf $archivefilename $dbdatafilename $dbtstructurefilename";
    $ssh->exec_cmd( $cmd );
}
waitTillFileIsReady( $config, $config['RemoteEZPublishRoot'] . '/' . $archivefilename, $output );
function waitTillFileIsReady( $config, $file, $output )
{


	$connection = ssh2_connect( $config['RemoteServer'], $config['RemotePort'] );
	ssh2_auth_password( $connection, $config['RemoteUser'], $config['RemotePassword'] );

	$output->outputText( "Waiting." );
	
	$sftp = ssh2_sftp($connection);

	for( $i=0; $i < 10001; $i++ )
	{
	$remotestatinfo = ssh2_sftp_stat($sftp, $file);
	sleep( 10 );
	$remotestatinfo2 = ssh2_sftp_stat($sftp, $file);
	if ( $remotestatinfo['size'] == $remotestatinfo2['size'])
	{
		break;
	}
		$output->outputText( "." );
		if ( $i == 10000)
		{
			throw new Exception( "Waited 10000 rounds. Aborting.");
		}
	}

}
	$connection = ssh2_connect( $config['RemoteServer'], $config['RemotePort'] );
	ssh2_auth_password( $connection, $config['RemoteUser'], $config['RemotePassword'] );
$sftp = ssh2_sftp($connection);
$remotestatinfo = ssh2_sftp_stat($sftp, $config['RemoteEZPublishRoot'] . '/' . $archivefilename);

$output->outputLine( "Start download." );


$sftp = ssh2_sftp($connection);
$remotestatinfo = ssh2_sftp_stat($sftp, $config['RemoteEZPublishRoot'] . '/' . $archivefilename);

$output->outputLine( "Download size:" .$remotestatinfo['size']. " bytes" );
ssh2_scp_recv( $connection, $config['RemoteEZPublishRoot'] . '/' . $archivefilename, $archivefilename );
$output->outputLine( "All data is downloaded." );

$ssh->unlink( $dbtstructurefilename );
$ssh->unlink( $dbdatafilename );
$ssh->unlink( $archivefilename );
unset( $ssh );
$output->outputLine( 'Remote session completed.' );

$localstatinfo = stat( $archivefilename );

$output->outputLine( "Downloaded size:" .$localstatinfo['size']. " bytes" );
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
    exec( $command->getCommand(), $out, $retval );
    echo $command->getCommand();
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
?>