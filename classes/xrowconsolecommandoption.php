<?php

class xrowConsoleCommandOption
{
    public $short = false;
    public $value = false;
    public $name = false;
    public $quote = true;

    function __construct( $name, $value = false, $short = true, $quote = true )
    {
        $this->name = $name;
        $this->value = $value;
        $this->short = $short;
        $this->quote = $quote;
    }

    function build()
    {
        $option = '';
        if ( $this->short )
        {
            $option .= '-' . $this->name;
        }
        else
        {
            $option .= '--' . $this->name;
        }
        if ( $this->value and ! $this->short )
        {
            $option .= "=";
        }
        
        if ( $this->value and $this->quote )
        {
            $option .= "\"" . $this->value . "\"";
        }
        elseif ( $this->value and ! $this->quote )
        {
            $option .= $this->value;
        }
        return " " . $option;
    }
}