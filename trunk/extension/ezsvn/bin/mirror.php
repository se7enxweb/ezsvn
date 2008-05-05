<?php

include_once( 'lib/ezutils/classes/ezcli.php' );
include_once( 'kernel/classes/ezscript.php' );
include_once( 'kernel/common/i18n.php' );
include_once( 'kernel/classes/ezcache.php' );
include_once( 'extension/ezsvn/classes/ezsvn.php' );

$cli = eZCLI::instance();
$script = eZScript::instance( array( 'description' => ( "Allow variable data to me mirrored from a remote maschine \n" .
                                                         "\n" .
                                                         "./extension/ezsvn/bin/mirror.php --name=MyWebsite" ),
                                      'use-session' => false,
                                      'use-modules' => false,
                                      'use-extensions' => true ) );

$script->startup();

$options = $script->getOptions( "[name:][skip-binary][skip-database]",
                                "",
                                array( 'name' => 'Name or alias of base sources',
                                       'skip-binary' => 'Skip copying binary files',
                                       'skip-database' => 'Skip copying database',
                                
                                ) );
$script->initialize();

$sys = eZSys::instance();
$mirror = eZINI::instance( 'mirror.ini' );
$siteini = eZINI::instance( 'site.ini' );
$settings = $mirror->group( 'Settings' );
if ( !$options['name'] and $settings['DefaultMirror'] )
    $options['name'] = $settings['DefaultMirror'];
if (  !$options['name'] or !$mirror->hasGroup( "Mirror-" . $options['name'] ) )
{
    $script->showHelp();
    return $script->shutdown();
}

$config = $mirror->group( "Mirror-" . $options['name'] );
$localdb = $siteini->group( "DatabaseSettings" );
if ( eZSys::osType() == 'win32' and !$options['skip-binary'] )
{   
    $settings['RsyncExecutable'] = eZDir::cleanPath( eZExtension::baseDirectory() . "/ezsvn/rsyncbinaries/cwrsync/bin/rsync.exe", EZ_DIR_SEPARATOR_DOS );
    #$settings['RsyncExecutable'] = false;
    $warningMsg = 'Rsync not avialable for Windows at full extend. Win rsync currently still has the old non WideChar function implemented. This causes problems with long paths.';
    $cli->warning( $warningMsg );
    $cli->warning( "You have 10 seconds to break the script (press Ctrl-C)." );
    sleep( 10 );
/* works partly though
extension\ezsvn\rsyncbinaries\cwrsync\bin\rsync.exe -rtp -e
 "extension\ezsvn\rsyncbinaries\cwrsync\bin\ssh -q -o StrictHostKeyChecking=no -
i var/cache/key.priv.tmp"  --ignore-existing --delete-after --numeric-ids --stat
s user@example.com:/path/var/storage path/var
*/
}
if ( $settings['RsyncExecutable'] and !$options['skip-binary'] )
{
    $cli->output( 'Running Rsync' );
    $source = "";
    if ( $config['RemoteUser'] )
        $source .= $config['RemoteUser'] . "@";
    if ( $config['RemoteServer'] )
        $source .= $config['RemoteServer'] . ":";
    if ( $config['RemoteEZPublishRoot'] )
        $source .= eZDir::cleanPath( $config['RemoteEZPublishRoot']  ) . '/var/storage';
    $destination = eZSys::rootDir() . "/" . eZSys::varDirectory();

    $tempfile = eZSys::cacheDirectory() . '/key.priv.tmp';
    if ( file_exists( $tempfile ) )
	unlink( $tempfile );
    if ( $config['PrivateKey'] )
    {
       eZFileHandler::copy( $config['PrivateKey'] , $tempfile );
       chmod( $tempfile , 0600 );
    }

    $cmd = $settings['RsyncExecutable'] . ' -rtp -e "ssh -q -o StrictHostKeyChecking=no -i ' . $tempfile . '"  --ignore-existing --delete-after --numeric-ids --stats ' . $source . ' ' . $destination;
    $cli->output( "shell> " . $cmd );
    $last_line = exec( $cmd, $output, $retval );
    writeOutput( $output );
    unlink( $tempfile );
    isError( $retval );
}

while ( $settings['MysqldumpExecutable'] and $settings['MysqlExecutable'] and !$options['skip-database'] )
{
    $cli->output( 'Running database dump and inject' );
    $cli->output( 'Dumping structure...' );
    $time = time();
    $tmpfile = eZSys::cacheDirectory() . "/database_" . $time . ".structure.sql";
    $cmd = $settings['MysqldumpExecutable'] . ' -C --no-data --add-drop-table --single-transaction -n --user=' . $config['RemoteDatabaseUser'] . ' -p' . $config['RemoteDatabasePassword'] . ' --host=' . $config['RemoteDatabaseHost'] . ' ' . $config['RemoteDatabaseName'] . ' > ' . $tmpfile;
    $output = null;
    $cli->output( $cmd );
    $last_line = exec( $cmd, $output, $retval );
    writeOutput( $output );
    if ( isError( $retval, false ) )
        break;
    $cli->output( 'Dumping data...' );
    $tmpdatafile = eZSys::cacheDirectory() . "/database_" . $time . ".data.sql";
    $ignoretables = array( 'ezsession', 'ezsearch_object_word_link', 'ezsearch_return_count', 'ezsearch_search_phrase', 'ezsearch_word' );
    $ignoretablestr ='';
    foreach ( $ignoretables as $ignoretable )
    {
        $ignoretablestr .= ' --ignore-table='.$config['RemoteDatabaseName'].'.' . $ignoretable . ' ';
    }
    $cmd = $settings['MysqldumpExecutable'] . $ignoretablestr .' --no-create-info -C --add-drop-table --single-transaction -n --user=' . $config['RemoteDatabaseUser'] . ' -p' . $config['RemoteDatabasePassword'] . ' --host=' . $config['RemoteDatabaseHost'] . ' ' . $config['RemoteDatabaseName'] . ' > ' . $tmpdatafile;
    $output = null;
    $cli->output( $cmd );
    $last_line = exec( $cmd, $output, $retval );
    writeOutput( $output );
    if ( isError( $retval, false ) )
        break;

    $db = eZDB::instance();
    
    $cli->output( 'Injecting...' );
    $cmd = $settings['MysqlExecutable'] . ' -u' . $localdb['User'] . ' -p' . $localdb['Password'] . ' -h' . $localdb['Server'] . ' ' . $localdb['Database'] . ' < ' . $tmpfile;
    $last_line = exec( $cmd, $output, $retval );
    writeOutput( $output );
    unlink( $tmpfile );
    isError( $retval );
    $cmd = $settings['MysqlExecutable'] . ' -u' . $localdb['User'] . ' -p' . $localdb['Password'] . ' -h' . $localdb['Server'] . ' ' . $localdb['Database'] . ' < ' . $tmpdatafile;
    $last_line = exec( $cmd, $output, $retval );
    writeOutput( $output );
    unlink( $tmpdatafile );
    isError( $retval );
    break;
}

$cli->output( 'Running SVN update' );
require( "extension/ezsvn/bin/ezsvn.php" );

$cli->output( 'Success' );
return $script->shutdown();
function writeOutput( &$output )
{   
    $cli = eZCLI::instance();
    if ( $output )
        $cli->output( implode( $cli->endlineString(), $output ) );
    $output = null;
}
function isError( $retval, $shutdown = true )
{
    global $script;
    $mirror = eZINI::instance( 'mirror.ini' );
    $cli = eZCLI::instance();
    if( $retval !== 0 and $mirror->variable( 'Settings', 'StopOnError' ) == 'true' )
    {
        if ( $shutdown )
        {
            $cli->output( 'Failure' );
            $script->shutdown($retval);
        }
        return true;
    }
    return false;
}
?>
