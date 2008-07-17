<?php

class xrowSVNWorkingCopy
{
    static public $MAX_SEARCH_DEPTH = 6;
    public $path;
    public $url;
    public $revision;
    public $kind;
    public $repos;
    public $last_changed_rev;
    public $last_changed_date;
    public $last_changed_author;
    public $is_working_copy;
    static function savecalculateRelativePath( $dir, $dir2 )
    {
        if ( $dir == $dir2 )
        {
            return ".";
        }
        return ezcFile::calculateRelativePath( $dir, $dir2 );
    }
    function __construct( $dir, $basedir = false )
    {
        
        if( $basedir )
        {
            $dir = self::savecalculateRelativePath( $dir, $basedir );
        }
        else
        {
            
            $dir = self::savecalculateRelativePath( $dir, getcwd() );
        }

        $dir = str_replace( DIRECTORY_SEPARATOR, '/', $dir );
        
        $info = svn_info( $dir, false );
        if ( $info )
        {
            $status = svn_status( $dir, SVN_NON_RECURSIVE );
            if( count( $status ) == 1 )
            {
                $info = array_merge( $status[0], $info[0] );
            }
            else
            {
                $info = $info[0];
            }
            foreach( $info as $key => $item )
            {
               $this->$key = $item;
            }
            if ( !$this->is_working_copy )
            {
                throw new Exception( $dir .' Not a working copy.' );
            }
        }
        else
        {
            throw new Exception( $dir .' Not a working copy.' );
        }
    }
    function cleanup()
    {
        if ( is_dir( $this->path ) )
        {
            svn_cleanup( $this->path );
        }
    }
    function toArray()
    {
        $return = array();
        $class_vars = get_class_vars( get_class( $this ) );
        foreach ( $class_vars as $name => $value )
        {
            $return[$name] = $this->$name;
            /** @TODO Missing svn_status vars, they need to get defined in class*/
        }
        return $return;
    }
    static function getRootFromPath( $path = '.' )
    {
        $path = realpath( $path );
        if( is_dir( $path . DIRECTORY_SEPARATOR . '.svn' ) )
        {
            return new xrowSVNWorkingCopy( $path );
        }
        else
        {
            return false;
        }
    }
    static function getFromPath( $path = './extension' )
    {
        $path = realpath( $path );
        $GLOBALS['xrowSVNWorkingCopy']['startdir'] = $path;
        $GLOBALS['xrowSVNWorkingCopy']['workingcopies'] = array();

        $root = self::getRootFromPath( $path = '.' );
        if ( $root )
        {
            $GLOBALS['xrowSVNWorkingCopy']['root'] = $root;
        }
        $it = new RecursiveIteratorIterator( new xrowSVNRecursiveDirectoryIterator( $path ), RecursiveIteratorIterator::SELF_FIRST );
        for ( $it->rewind(); $it->valid(); $it->next() )
        {
            //echo "debug: " .$it->getPathname()."\n";
        }
        return $GLOBALS['xrowSVNWorkingCopy']['workingcopies'];
    }
}
?>