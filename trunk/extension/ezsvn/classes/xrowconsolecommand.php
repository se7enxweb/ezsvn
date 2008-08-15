<?php

class xrowConsoleCommand
{
    public $binary = false;
    public $pipe = false;
    public $options = array();

    function __construct( $binary, $os = false )
    {
        if ( $binary == false )
        {
            throw new Exception( "Please define a binary" );
        }
        $this->binary = $binary;
    }

    function addArgument( $value )
    {
        $this->options[] = new xrowConsoleCommandArgument( $value );
    }

    function addShortOption( $name, $value = false, $quote = true )
    {
        $this->options[] = new xrowConsoleCommandOption( $name, $value, true, $quote );
    }

    function addLongOption( $name, $value = false, $quote = true )
    {
        $this->options[] = new xrowConsoleCommandOption( $name, $value, false, $quote );
    }

    function redirectOuputAfter( $to, $type = '|' )
    {
        $this->pipe = $type . ' ' . $to;
    }

    function getCommand()
    {
        $cmd = '';
        $cmd .= $this->binary;
        if ( count( $this->options ) > 0 )
        {
            foreach ( $this->options as $option )
            {
                $cmd .= ' ' . $option->build();
            }
        }
        if ( $this->pipe )
        {
            $cmd .= ' ' . $this->pipe;
        }
        return $cmd;
    }

    function execute()
    {
        $cmd = $this->getCommand();
        exec( $cmd, $out, $retval );
    }

}