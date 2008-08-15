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
	if ( file_exists('settings/override/mirror.ini') )
	{
		$file = 'settings/override/mirror.ini';
	}
	elseif( file_exists('mirror.ini') )
	{
		$file = 'mirror.ini';
	}
}
else 
{
	$file = $fileOption->value;
}
$ini =
$reader = new ezcConfigurationIniReader( $file );
$cfg = $reader->load();
if( $cfg->hasGroup( $mirrorOption->value ) )
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

if ( $databaseOption->value === true)
{

    $cmd = "cd " . $config['RemoteEZPublishRoot'] . ' && mysqldump -C --no-data --add-drop-table --single-transaction -n --user=' . $config['RemoteDatabaseUser'] . ' -p' . $config['RemoteDatabasePassword'] . ' --host=' . $config['RemoteDatabaseHost'] . ' ' . $config['RemoteDatabaseName'] . ' > ' . $dbtstructurefilename;
    $ssh->exec_cmd( $cmd );
    $output->outputLine( 'Database structure dumped.' );
    

    $ignoretables = array( 
        'ezsession' , 
        'ezsearch_object_word_link' , 
        'ezsearch_return_count' , 
        'ezsearch_search_phrase' , 
        'ezsearch_word' 
    );
    $ignoretablestr = '';
    foreach ( $ignoretables as $ignoretable )
    {
        $ignoretablestr .= ' --ignore-table=' . $config['RemoteDatabaseName'] . '.' . $ignoretable . ' ';
    }
    $cmd = "cd " . $config['RemoteEZPublishRoot'] . ' && mysqldump ' . $ignoretablestr . ' --no-create-info -C --add-drop-table --single-transaction -n --user=' . $config['RemoteDatabaseUser'] . ' -p' . $config['RemoteDatabasePassword'] . ' --host=' . $config['RemoteDatabaseHost'] . ' ' . $config['RemoteDatabaseName'] . ' > ' . $dbdatafilename;
    $ssh->exec_cmd( $cmd );
    $output->outputLine( 'Database data dumped.' );

}
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

$output->outputLine( "Start download." );
$connection = ssh2_connect($config['RemoteServer'], $config['RemotePort']);
ssh2_auth_password($connection, $config['RemoteUser'], $config['RemotePassword']);
ssh2_scp_recv($connection, $config['RemoteEZPublishRoot'].'/'.$archivefilename, $archivefilename);
$output->outputLine( "All data is downloaded." );

$ssh->unlink( $dbtstructurefilename );
$ssh->unlink( $dbdatafilename );
$ssh->unlink( $archivefilename );
unset( $ssh );
$output->outputLine( 'Remote session completed.' );
$output->outputLine( 'Extracting data.' );
if ( file_exists( $archivefilename ) )
{
	$tar = ezcArchive::open( $archivefilename );
	$tar->extract(".");
	unset( $tar );
	unlink( $archivefilename );
}
$output->outputLine( 'Injecting into database.' );
$cmd = 'mysql -u' . $config['LocalDatabaseUser'] . ' -p' . $config['LocalDatabasePassword'] . ' -h' . $config['LocalDatabaseHost'] . ' -e"CREATE DATABASE IF NOT EXISTS '.$config['LocalDatabaseName'].'"';
exec( $cmd, $out, $retval );
if ( file_exists( $dbtstructurefilename ) )
{
	$cmd = 'mysql -u' . $config['LocalDatabaseUser'] . ' -p' . $config['LocalDatabasePassword'] . ' -h' . $config['LocalDatabaseHost'] . ' ' . $config['LocalDatabaseName'] . ' < ' . $dbtstructurefilename;
	exec( $cmd, $out, $retval );
	unlink( $dbtstructurefilename );
}
if ( file_exists( $dbdatafilename ) )
{
	$cmd = 'mysql -u' . $config['LocalDatabaseUser'] . ' -p' . $config['LocalDatabasePassword'] . ' -h' . $config['LocalDatabaseHost'] . ' ' . $config['LocalDatabaseName'] . ' < ' . $dbdatafilename;
	exec( $cmd, $out, $retval );
	unlink( $dbdatafilename );
}
$output->outputLine( 'Done.' );
exit( 1 );
?>