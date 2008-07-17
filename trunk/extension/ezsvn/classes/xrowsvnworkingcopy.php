<?php

class xrowSVNWorkingCopy
{
    public $path;
    public $url;
    public $revision;
    public $kind;
    public $repos;
    public $last_changed_rev;
    public $last_changed_date;
    public $last_changed_author;
    public $is_working_copy;
    function __construct( $dir )
    {
        $info = svn_info( $dir, false );
        if ( $info )
        {
            foreach( $info[0] as $key => $item )
            {
               $this->$key = $item;
            }
        }
        else
        {
            throw new Exception( 'Not a working copy' );
        }
    }

    function getFromPath( $path = './extension' )
    {
        $it = new RecursiveDirectoryIterator( $path );
        // RecursiveIteratorIterator accepts the following modes:
        //     LEAVES_ONLY = 0  (default)
        //     SELF_FIRST  = 1
        //     CHILD_FIRST = 2
        $pattern = preg_quote( DIRECTORY_SEPARATOR . '\.svn', DIRECTORY_SEPARATOR );
        foreach ( new RecursiveIteratorIterator( $it, 2 ) as $path )
        {
            if ( strpos( $path, '.svn', strlen( $path ) - 4 ) and $path->isDir() )
            {
                $files[] = $path;
            }
        }
        $workingcopies = array();
        foreach ( $files as $key => $file )
        {
            $files[$key] = realpath( $file );
            $elements = explode( DIRECTORY_SEPARATOR, $files[$key] );
            array_pop( $elements );
            $workigncopydir = implode( DIRECTORY_SEPARATOR, $elements );
            array_pop( $elements );
            $workigncopytestdir = implode( DIRECTORY_SEPARATOR, $elements );
            if ( svn_info( $workigncopytestdir, false ) === false )
            {
                $info = svn_info( $workigncopydir, false );
                if ( is_array( $info ) )
                {
                    $workigncopydir = ezcFile::calculateRelativePath( $workigncopydir, getcwd() );
                    $workingcopies[] = new xrowSVNWorkingCopy( $workigncopydir );
                }
            }
        }
        return $workingcopies;
    }
}
?>