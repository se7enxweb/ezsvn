<?php
$Module = array( "name" => "SVN" );

$ViewList = array();
$ViewList["configserver"] = array( "script" => "configserver.php" );
$ViewList["client"] = array(
    'default_navigation_part' => 'ezadmin', 
    'functions' => array( 'client' ),
    "script" => "client.php" );

$FunctionList['client'] = array( );
?>