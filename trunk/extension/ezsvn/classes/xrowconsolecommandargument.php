<?php

class xrowConsoleCommandArgument
{
    public $value = false;
    public $quote = false;

    function __construct( $value, $quote = false )
    {
        $this->value = $value;
        $this->quote = $quote;
    }

    function build()
    {
        return " " . $this->value;
    }

}