<?php
namespace Erum;

/**
 * Request manager
 * 
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * @package Erum
 * @subpackage Core
 * 
 * @property string $referer
 * @property bool $secured
 * @property string $host
 * @property string $uri
 * @property string $rawUrl
 * @property bool $async
 * @property-read array $headers
 * @property-read string $extension
 */
final class Request
{
    const GET       = 'GET';
    const POST      = 'POST';
    const PUT       = 'PUT';
    const DELETE    = 'DELETE';
    const CLI       = 'CLI';

    /**
     * Request method ( POST,GET,PUT, DELETE, CLI )
     *
     * @var int
     */
    private $method;

    /**
     * Is request scheme secured ( http or https )
     *
     * @var bool
     */
    private $secured;

    /**
     * POST variables array
     *
     * @var array
     */
    private $post = array( );

    /**
     * GET variables array
     *
     * @var array
     */
    private $get = array( );

    /**
     * HTTP headers
     *
     * @var array
     */
    private $headers = array();

    /**
     * HTTP_REFERER if exists
     * ( including internal requests )
     *
     * @var string
     */
    private $referer = null;

    /**
     *
     * @var string
     */
    private $host;

    /**
     * current request uri
     *
     * @var string
     */
    private $uri;
    
    /**
     * Raw request url
     * 
     * @var string
     */
    private $rawUrl;

    /**
     * Is request async. ( X-Requested-With : XMLHttpRequest )
     *
     * @var boolean
     */
    private $async = false;

    /**
     * Request uri extension ( html, json, etc... )
     *
     * @var string|null
     */
    private $extension = false;

    /**
     * Current request object
     *
     * @var \Erum\Request
     */
    private static $current = null;

    /**
     * Initial request object
     *
     * @var \Erum\Request
     */
    private static $initial = null;

    private function __construct()
    {
    }

    /**
     * Deprecated.
     *
     * @deprecated
     * @return Request
     */
    public static function getCurrent()
    {
        return self::$current;
    }

    /**
     * Factory method to create new request. By default (if $url is null) returns current request
     *
     * @param string $url
     * @return \Erum\Request
     */
    public static function factory( $url = null, $method = null, array $vars = null, array $headers = null )
    {
        if( null === $url )
        {
            $url = $_SERVER["REQUEST_URI"];
        }

        $urlData = parse_url( $url );

        if( !is_array($urlData) || !isset( $urlData['path'] ) )
        {
            throw new \Exception('Malformed url given');
        }

        $request = new self();

        // save initial request
        if( null === self::$initial )
        {
            self::$initial = $request;
        }

        // save current request
        self::$current = $request;

        // normalize uri
        $uri = '/' . trim( explode( '?', $urlData['path'] )[0], '/');

        // Cut extension if exists. Do never assign variables in conditions !!! Here just fastest way
        if( false !== ( $pos = strripos( $uri, '.' ) ) )
        {
            $request->extension = strtolower( substr( $uri, $pos + 1 ) );

            $uri = substr( $uri, 0, - ( strlen( $request->extension ) + 1 ) );
        }

        $request->uri       = $uri;
        $request->host      = ( isset($urlData['host'] ) && $urlData['host'] ) ? $urlData['host'] : $_SERVER["HTTP_HOST"];
        $request->post      = self::escapeVarsArray(  ( null !== $vars && $method = self::POST ) ? $vars : $_POST );
        $request->get       = self::escapeVarsArray( $vars ? $vars : $_GET );
        $request->method    = $method ? $method : $_SERVER['REQUEST_METHOD'];
        $request->rawUrl    = $url;
        $request->headers   = $headers === null ? self::currentHeaders() : $headers;

        if( $request->isInitial() )
        {
            $request->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
        }
        else
        {
            $request->referer = self::current()->rawUrl;
        }

        return $request;
    }

    /**
     * Returns current Request object
     * 
     * @return \Erum\Request
     */
    public static function current()
    {
        if ( !( self::$current instanceof self ) )
        {
            self::factory();
        }

        return self::$current;
    }

    /**
     * Retrieve initial request
     *
     * @return \Erum\Request
     */
    public static function initial()
    {
        if( null === self::$initial )
        {
            self::current();
        }

        return self::$initial;
    }

    /**
     *
     * @return boolean
     */
    public function isInitial()
    {
        return self::$initial === self::$current;
    }

    /**
     * @deprecated use headers instead
     *
     * @return boolean
     */
    protected function isAsync()
    {
        if ( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' )
        {
            return true;
        }

        return false;
    }

    /**
     *
     * @param string $var
     * @param int $method
     * @return mixed 
     */
    public function getVar( $var, $method = null )
    {
        $value = null;

        if ( null === $method || self::GET === $method )
        {
            $value = isset( $this->get[$var] ) ? $this->get[$var] : null;
        }

        if ( ( null === $value || self::POST === $this->method ) && ( null === $method || self::GET !== $method ) )
        {
            $value = isset( $this->post[$var] ) ? $this->post[$var] : null;
        }

        return $value;
    }

    /**
     * @param string $var
     * @param mixed $value
     * @param string $method
     *
     * @return \Erum\Request
     */
    public function setVar( $var, $value, $method = self::GET )
    {
        if( $method === self::GET )
        {
            $this->get[ $var ] = $value;
        }
        elseif( $method === self::POST || $method === self::PUT || $method === self::DELETE )
        {
            $this->post[ $var ] = $value;
        }
        else
        {
            throw new \InvalidArgumentException( 'Method ' . $method . ' is not supported for setting variables.' );
        }

        return $this;
    }

    /**
     * Executes request
     *
     * @todo implement remote requests
     */
    public function execute()
    {
        $router = new \Erum\Router( $this );

        return $router->performRequest();
    }

    /**
     * Accessor to internal variables
     *
     * @param string $var
     * @return mixed
     */
    public function __get( $var )
    {
        // @TODO if $var is null "isset" function returns false !
        if ( isset( $this->$var ) )
        {
            return $this->$var;
        }
        else
        {
            throw new \Exception( 'Requested variable $' . $var . ' not exists!' );
        }
    }

    /**
     * Return processed variables
     *
     * @todo Implement base checks and escape
     *
     * @param array $array
     * @return array
     */
    public static function escapeVarsArray( array $array )
    {
        return $array;
    }


    /**
     * @return array
     */
    public static function currentHeaders()
    {
        $headers = array();

        if( isset( $_SERVER ) && is_array( $_SERVER ) && !empty( $_SERVER ) )
        {
            foreach( $_SERVER as $key => $value )
            {
                if ( substr( $key, 0, 5 ) == "HTTP_" )
                {
                    $key = str_replace( " ", "-", ucwords( strtolower( str_replace( "_", " ", substr( $key, 5 ) ) ) ) );
                }

                $headers[ $key ] = $value;
            }
        }

        return $headers;
    }
}
