<?php
class eZSVN
{
    function eZSVN ()
    {

    }
    function chmod_R( $path, $filemode )
    {
       if ( !is_dir($path) )
           return chmod($path, $filemode);

       $dh = opendir( $path );
       while ( $file = readdir($dh) )
       {
           if( $file != '.' && $file != '..' )
           {
               $fullpath = $path.'/'.$file;
               if( !is_dir( $fullpath) )
               {
                   if ( !chmod( $fullpath, $filemode ) )
                      return FALSE;
               } 
               else 
               {
                   if ( !ezsvn::chmod_R( $fullpath, $filemode ) )
                      return FALSE;
               }
           }
       }

        closedir($dh);

        if( chmod( $path, $filemode) )
            return TRUE;
        else
            return FALSE;
    }
    function execute( $params, $flushOutput = false )
    {
        set_time_limit( 0 );
        $execute=true;
        $sys = & eZSys::instance();
        $sys->init();

        $ini = & eZINI::instance('svn.ini');
        $executeablearray = $ini->variable( 'Settings', 'executeable' );
        $executeable = $executeablearray[$sys->osType()];

        if( empty($executeable) )
        {
            eZDebug::writeWarning( 'Extension might not be activated.','eZSVN::execute()' );
            $ini = & eZINI::instance('svn.ini.append.php', 'extension/ezsvn/settings' );
            $executeablearray = $ini->variable( 'Settings', 'executeable' );
            $executeable = $executeablearray[$sys->osType()];
        }
        $executeable = eZDIR::convertSeparators( $executeable, EZ_DIR_SEPARATOR_LOCAL );
        $cmd="";
        switch ($params['type'])
        {
            case 'export':
                $cmd .= " export --force";
            break;
            case 'co':
                $cmd .= " co";
            break;
            default:
                $cmd .= " --help";
                $execute=false;
            break;
        }
        if ( isset( $params['user'] ) and isset( $params['password'] ) )
        {
            $cmd .= " --no-auth-cache --username ".$params['user']." --password ".$params['password'];
        }
        if ( isset( $params['revision'] ) )
        {
            $cmd .= " --revision ".$params['revision'];
        }
        if ( $params['url'] )
        {
            $cmd .= " ".$params['url'];
        }

        if ( $params['placement'] )
        {
            $path = "./" . $params['placement'];
            $path = eZDir::cleanPath( $path, EZ_DIR_SEPARATOR_UNIX );
        }
        else
        {
            $path = "./";
        }
        $cmd .= " " . $path;
        if ( empty( $params['url'] ) )
        {
            $execute=false;
        }
        if ( $execute==true )
        {
                $cmd = $executeable.$cmd;
                eZDebug::writeNotice( $cmd, "eZSVN::execute" );
                $retval = null;
				if ( $flushOutput )
				{
					$last_line = system( $cmd, $retval );
					$output = null;
				}
				else
				{
					$last_line = exec( $cmd, $output, $retval );
					
				}
                // fix  permissions
		ezsvn::chmod_R( $path, 0777 );

                /* disabled autoclean feature
                if ( $params['type'] == 'export' and array_key_exists( "name", $params ) and $ini->variable( 'Settings', 'AutoClean' ) == 'enabled' and $ini->hasVariable( 'AutoClean', $params['name'] ) )
                {
                    $items = $ini->variable( 'AutoClean', $params['name'] );
                    foreach ( $items as $item )
                    {
                        if ( empty( $item ) or $item[0] == "/" or $item == "." or $item == "..")
                            continue;
                        if ( is_dir( $item ) )
                        {
                            eZDir::recursiveDelete( $item );
                        }
                        elseif ( file_exists( $item ) )
                        {
                            unlink( $item );
                        }
                    }
                }
                */
				$return = array( 'output' => $output, 'command' => $cmd, 'return' => $retval );
                return $return;
        }
        else
        {
            return false;    
        }
    }
    function update ( $repositories )
    {    
        foreach ( $repositories as $repository )
        {
            if ( eZSVN::execute( $repository ) === false )
				return false;
        }
    }
}
?>
