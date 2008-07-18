<?php

class xrowSVNRecursiveDirectoryIterator extends RecursiveDirectoryIterator implements RecursiveIterator
{
    function getChildren()
    {
        return new xrowSVNRecursiveDirectoryIterator( realpath( $this->getPathname() ) );
    }

    function hasChildren()
    {
        if ( $this->isDir() )
        {
            $path = $this->getPathname() . DIRECTORY_SEPARATOR . '.svn';
            if ( file_exists( $path ) )
            {
                $wc = new xrowSVNWorkingCopy( $this->getPathname(), $GLOBALS['xrowSVNWorkingCopy']['startdir'] );
                if ( $GLOBALS['xrowSVNWorkingCopy']['root']->repos != $wc->repos )
                {
                    
                    $GLOBALS['xrowSVNWorkingCopy']['workingcopies'][] = $wc;
                }
                return false;
            }
        }
        return parent::hasChildren();
    }
}
?>