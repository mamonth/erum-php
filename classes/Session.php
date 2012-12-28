<?php

namespace Erum;

class Session
{
    /**
     *
     * @var \Erum\Session
     */
    private static $instance;

    /**
     *
     * @return \Erum\Session
     */
    public static function current()
    {
        if( null === self::$instance )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    private function __construct()
    {
        session_start();
    }

    public function get( $var )
    {
        if( !isset( $_SESSION[ $var ] ) )
        {
            return null;
        }

        return $_SESSION[ $var ];
    }

    /**
     *
     * @param string $var
     * @return mixed
     */
    public function __get( $var )
    {
        return $this->get( $var );
    }

    public function set( $var, $value )
    {
        $_SESSION[ $var ] = $value;
    }

    public function __set( $var, $value )
    {
        $this->set( $var, $value );
    }
}
