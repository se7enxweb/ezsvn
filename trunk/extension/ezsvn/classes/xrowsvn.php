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
        if ( ! function_exists( 'svn_auth_set_parameter' ) )
        {
            throw new Exception( "PECL SVN extension (http://pecl.php.net/package/svn) is required to use SVN functionality.\n" );
        }
        svn_auth_set_parameter( SVN_AUTH_PARAM_DONT_STORE_PASSWORDS, '1' );
        if ( $XMLConfigFile )
        {
            if ( file_exists( $XMLConfigFile ) )
            {
                $this->config = simplexml_load_file( $XMLConfigFile );
            }
            else
            {
                throw new Exception( 'File ' . $XMLConfigFile . ' not found.' );
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
        if ( ! $options['user'] )
            $options['user'] = $ini->variable( 'Settings', 'User' );
        if ( ! $options['password'] )
            $options['password'] = $ini->variable( 'Settings', 'Password' );
        if ( ! $options['server'] )
            $options['server'] = $ini->variable( 'Settings', 'Server' );
        if ( ! $options['server-port'] )
            $options['server-port'] = $ini->variable( 'Settings', 'Port' );
        if ( ! $options['config-id'] )
            $options['config-id'] = $ini->variable( 'Settings', 'ConfigID' );
        ;
    }

    function updateBase()
    {
        $revision = self::convertRevision( $this->config['revision'] );
        $url = self::convertURL( $this->config['url'] );
        if ( isset( $checkout['path'] ) )
        {
            $path = $checkout['path'];
        }
        else
        {
            $path = ".";
        }
        $return = svn_export( $url, realpath( $path ), false );
        if ( $return === false )
        {
            throw new Exception( "Export Base failed on '" . $url . "' path '" . $path . "' failed." );
        }
        return $return;
    }

    private static function convertURL( $url, $setAuth = true )
    {
        svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_USERNAME, parse_url( $url, PHP_URL_USER ) );
        svn_auth_set_parameter( SVN_AUTH_PARAM_DEFAULT_PASSWORD, parse_url( $url, PHP_URL_PASS ) );
        $url = parse_url( $url, PHP_URL_SCHEME ) . '://' . parse_url( $url, PHP_URL_HOST ) . parse_url( $url, PHP_URL_PATH );
        return $url;
    }

    private static function convertRevision( $revision )
    {
        if ( is_numeric( $revision ) )
        {
            $revision = (int) $revision;
        }
        elseif ( $revision )
        {
            $revision = SVN_REVISION_HEAD;
            
            switch ( $revision )
            {
                case 'HEAD':
                    {
                        $revision = SVN_REVISION_HEAD;
                    }
                    break;
                case 'BASE':
                    {
                        $revision = SVN_REVISION_BASE;
                    }
                    break;
                case 'COMMITTED':
                    {
                        $revision = SVN_REVISION_COMMITTED;
                    }
                    break;
                case 'PREV':
                    {
                        $revision = SVN_REVISION_PREV;
                    }
                    break;
                default:
                    {
                        $revision = SVN_REVISION_HEAD;
                    }
                    break;
            }
        }
        else
        {
            $revision = SVN_REVISION_HEAD;
        }
        return $revision;
    }

    static function update( $checkout )
    {
        if ( ! $checkout )
        {
            throw new Exception( 'Missing data for update.' );
        }
        $revision = self::convertRevision( $checkout['revision'] );
        $url = self::convertURL( $checkout['url'] );
        $path = (string)$checkout['path'];
        clearstatcache();
        if ( is_dir( $path ) )
        {
            $wc = new xrowSVNWorkingCopy( $path );
            if ( ( $url != $wc->url ) and ( $url.'/' != $wc->url ) and ( $url != $wc->url.'/' ) )
            {
                throw new xrowSVNDifferentPathException( "Current working copy '" . $wc->path . "' with url '" . $wc->url . "' belongs to a diffent url as '" . $url . "'.", $wc );
            }
            if ( $wc->lock == 1 )
            {
                throw new xrowSVNLockException( "Current working copy '" . $wc->path . "' is locked.", $wc );
            }
            /* @TODO maybe check here properly the status
             * $wc->status? != svn_wc_status_normal
             * */
            
            $return = svn_update( $path, $revision );
            if ( $return === false )
            {
                throw new xrowSVNUpdateException( 'Update on ' . $url . ' path ' . $path . ' failed.', $wc );
            }
        }
        else
        {
            $return = svn_checkout( $url, $path, $revision );
            if ( $return === false )
            {
                throw new Exception( 'Checkout on ' . $url . ' path ' . $path . ' failed.' );
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
        if ( ! is_dir( $path ) )
            return chmod( $path, $filemode );
        $dh = opendir( $path );
        while ( $file = readdir( $dh ) )
        {
            if ( $file != '.' && $file != '..' )
            {
                $fullpath = $path . '/' . $file;
                if ( ! is_dir( $fullpath ) )
                {
                    if ( ! chmod( $fullpath, $filemode ) )
                        return FALSE;
                }
                else
                {
                    if ( ! ezsvn::chmod_R( $fullpath, $filemode ) )
                        return FALSE;
                }
            }
        }
        closedir( $dh );
        if ( chmod( $path, $filemode ) )
            return TRUE;
        else
            return FALSE;
    }

    function buildXML( $path, array $workigncopies, xrowSVNWorkingCopy $base )
    {
        $doc = new DOMDocument( "1.0", "UTF-8" );
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $xml = $doc->createElement( "svn" );
        if ( isset( $base->url ) )
        {
            $xml->setAttribute( 'url', $base->url );
        }
        if ( isset( $base->revision ) )
        {
            $xml->setAttribute( 'revision', $base->revision );
        }
        foreach ( $workigncopies as $copy )
        {
            $checkout = $doc->createElement( 'checkout' );
            $checkout->setAttribute( 'url', $copy->url );
            $checkout->setAttribute( 'revision', $copy->revision );
            $checkout->setAttribute( 'path', str_replace( DIRECTORY_SEPARATOR, '/', $copy->path ) );
            $xml->appendChild( $checkout );
        }
        $doc->appendChild( $xml );
        return $doc->save( $path );
    }
}
?>