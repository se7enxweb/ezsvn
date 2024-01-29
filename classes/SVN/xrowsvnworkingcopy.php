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
        return ezcBaseFile::calculateRelativePath( $dir, $dir2 );
    }
    function unrealpath( $final_dir, $start_dir, $dirsep = '/' )
    {
        //Directory separator consistency
        $start_dir = str_replace('/',$dirsep,$start_dir);
        $final_dir = str_replace('/',$dirsep,$final_dir);
        $start_dir = str_replace('\\',$dirsep,$start_dir);
        $final_dir = str_replace('\\',$dirsep,$final_dir);

        //'Splode!
        $firstPathParts = explode($dirsep, $start_dir);
        $secondPathParts = explode($dirsep, $final_dir);
      
        //Get the number of parts that are the same.
        $sameCounter = 0;
        for($i = 0; $i < min( count($firstPathParts), count($secondPathParts) ); $i++) {
            if( strtolower($firstPathParts[$i]) !== strtolower($secondPathParts[$i]) ) {
                break;
            }
            $sameCounter++;
        }
        //If they do not share any common directories/roots, just return 2nd path.
        if( $sameCounter == 0 ) {
            return $final_dir;
        }
        //init newpath.
        $newPath = '';
        //Go up the directory structure count(firstpathparts)-sameCounter times (so, go up number of non-matching parts in the first path.)
        for($i = $sameCounter; $i < count($firstPathParts); $i++) {
            if( $i > $sameCounter ) {
                $newPath .= $dirsep;
            }
            $newPath .= "..";
        }
        //if we did not have to go up at all, we're still in start_dir.
        if( strlen($newPath) == 0 ) {
            $newPath = ".";
        }
        //now we go down as much as needed to get to final_dir.
        for($i = $sameCounter; $i < count($secondPathParts); $i++) {
            $newPath .= $dirsep;
            $newPath .= $secondPathParts[$i];
        }
        //
        return $newPath;
    }
    function __construct( $dir, $basedir = false )
    {
        if( $basedir )
        {
            $dir = self::unrealpath( $dir, $basedir );
        }
        else
        {
            
            $dir = self::unrealpath( $dir, getcwd() );
        }

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