<?php
namespace Erum;

/**
 * Tool for handy cookie handle.
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
class Cookie
{
    const EXPIRE_NEVER = 0;
    const EXPIRE_SESSION = -1;

    /**
     * Cookie name
     *
     * @var string
     */
    public $name;

    /**
     * Cookie value
     *
     * @var string
     */
    public $value;

    /**
     * @param $name
     * @param string $value
     */
    public function __construct( $name, $value = '')
    {
        $this->name     = trim( $name );
        $this->value    = $value;
    }

    /**
     * Send cookie to client
     *
     * @param int $expire
     * @param string $path
     * @param null $domain
     * @param bool $secure
     * @param bool $httponly
     *
     * @return \Cookie
     *
     * @throws RuntimeException
     */
    public function store( $expire = 0, $path = '/', $domain = null, $secure = false, $httponly = false )
    {
        // should never expire
        if( $expire === self::EXPIRE_NEVER )
        {
            $expire = 2147483646;
        }
        // session cookie - live until browser will be closed
        elseif( $expire === self::EXPIRE_SESSION )
        {
            $expire = 0;
        }
        else
        {
            $expire += time();
        }

        if( false === setcookie( $this->name, $this->value, (int)$expire, $path, $domain, $secure, $httponly ) )
        {
            throw new \RuntimeException('Unable to set cookie ' . $this->name );
        }

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->value;
    }

    /**
     * Factory ( syntax shugar )
     *
     * @param string $name
     * @param string $value
     * @return Cookie
     */
    public static function factory( $name, $value )
    {
        return new Cookie( $name, $value );
    }

    /**
     * Get cookie by name
     *
     * @param string $name
     * @return Cookie|null
     */
    public static function get( $name )
    {
        $name = trim( $name );

        return isset( $_COOKIE[ $name ] ) ? new self( $name, $_COOKIE[ $name ] ) : null;
    }
}
