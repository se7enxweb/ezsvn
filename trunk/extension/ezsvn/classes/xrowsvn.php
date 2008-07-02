<?php
#echo svn_client_version();
#svn_checkout('http://zev.ez.no/svn/extensions/ezdhtml/trunk/', dirname(__FILE__) . '/extension/ezdhtml');
#svn_update( dirname(__FILE__) . '/extension/ezdhtml');

#$result = svn_info(realpath(dirname(__FILE__) . '/extension/ezdhtml'));
#print_r($result[0] );

#$svn = new xrowSVN( 'svn.xml' ); 
#print_r( $svn );

#$svn->update();

class xrowSVN
{
    public $config;
    public $options;
    /**
     * 
     */
    function __construct( $XMLConfigFile = false )
    {
        
        if ( !function_exists( 'svn_auth_set_parameter' ) )
        {
            throw new Exception( "PECL SVN extension (http://pecl.php.net/package/svn) is required to use SVN functionality.\n" );
        }
        svn_auth_set_parameter(SVN_AUTH_PARAM_DONT_STORE_PASSWORDS, '1');

        if ( $XMLConfigFile )
        {
            if ( file_exists( $XMLConfigFile ) )
            {
                $this->config = simplexml_load_file( $XMLConfigFile );
            }
            else 
            {
                throw new Exception('File '.$XMLConfigFile.' not found.');
            }
        }
        else
        {
            $this->getConfigFromSOAP();
        }
    }
    function SVNClientVersion()
    {
        return svn_client_version();
    }
    function getConfigFromSOAP()
    {
        $ini = eZINI::instance( 'svn.ini' );
        if ( !$options['user'] )
	        $options['user'] = $ini->variable( 'Settings', 'User' );
        if ( !$options['password'] )
	        $options['password'] = $ini->variable( 'Settings', 'Password' );
        if ( !$options['server'] )
	        $options['server'] = $ini->variable( 'Settings', 'Server' );
        if ( !$options['server-port'] )
	        $options['server-port'] = $ini->variable( 'Settings', 'Port' );
        if ( !$options['config-id'] )
	        $options['config-id'] = $ini->variable( 'Settings', 'ConfigID' );
    	;
    }
    
    function update()
    {
        foreach ( $this->config->checkout as $checkout )
        {
            if ( is_numeric( $checkout['revision'] ) )
            {
                $revision = (int)$checkout['revision'];
            }
            else
            {
                $revision = SVN_REVISION_HEAD;
                if ( array_key_exists( 'revision', $checkout ) )
                {
                switch ( $checkout['revision'] )
                {
                    case 'HEAD':
                    {
                        $revision = SVN_REVISION_HEAD;
                    }break;
                    case 'BASE':
                    {
                        $revision = SVN_REVISION_BASE;
                    }break;
                    case 'COMMITTED':
                    {
                        $revision = SVN_REVISION_COMMITTED;
                    }break;
                    case 'PREV':
                    {
                        $revision = SVN_REVISION_PREV;
                    }break;
                    default:
                    {
                        $revision = SVN_REVISION_HEAD;
                    }break;
                }
                }
            }
            svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_USERNAME, parse_url( $checkout['url'], PHP_URL_USER ) );
            svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_PASSWORD, parse_url( $checkout['url'], PHP_URL_PASS ) );

            $url =  parse_url( $checkout['url'], PHP_URL_SCHEME ) . '://' . parse_url( $checkout['url'], PHP_URL_HOST ) . parse_url( $checkout['url'], PHP_URL_PATH );

            if ( is_dir( $checkout['path'] ) )
            {
                $return = svn_update( $checkout['path'], $revision );
                if ( $return === false )
                {
                    throw new Exception('Update on '.$url.' path '.$checkout['path'].' failed.');
                }
            }
            else
            {
                $return = svn_checkout( $url, $checkout['path'], $revision );
                if ( $return === false )
                {
                    throw new Exception('Checkout on '.$url.' path '.$checkout['path'].' failed.');
                }
            }
        }
    }
    
    /**
     * 
     */
    function __destruct()
    {
            svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_USERNAME, '' );
            svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_PASSWORD, '' );
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
}
?>