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
 */
final class Request
{
    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';
    const CLI = 'CLI';

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
    public static function factory( $url = null, $method = null, $vars=null, $async = null )
    {
        //@TODO remove !
        if( null === $url ) return self::current();
        
        $urlData = parse_url( $url );
        
        if( !is_array($urlData) || !isset( $urlData['path'] ) )
        {
            throw new \Exception('Malformed url given');
        }

        $request = new self();

        $request->uri       = self::cleanUri( $urlData['path'] );
        $request->host      = ( isset($urlData['host'] ) && $urlData['host'] ) ? $urlData['host'] : $_SERVER["HTTP_HOST"];
        $request->post      = self::current()->escapeVarsArray(  ( null !== $vars && $method = self::POST ) ? $vars : $_POST );
        $request->get       = self::current()->escapeVarsArray( $vars ? $vars : $_GET );
        $request->method    = $method ? $method : $_SERVER['REQUEST_METHOD'];
        $request->async     = (null === $async) ? false : $async;
        $request->rawUrl    = $url;
        
        if( self::isInitial() )
        {
            $request->referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;

            $request->headers = self::currentHeaders();
        }
        else
        {
            $request->referer = self::current()->rawUrl;
        }

        if( null === self::$initial )
        {
            self::$initial = $request;
        }

        self::$current = $request;

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
            self::$current = new self();

            self::$current->secured =  ($_SERVER["SERVER_PORT"] == 443 );
            self::$current->method = $_SERVER['REQUEST_METHOD'];
            self::$current->host = $_SERVER["HTTP_HOST"];

            self::$current->post = self::$current->escapeVarsArray( $_POST );
            self::$current->get = self::$current->escapeVarsArray( $_GET );

            self::$current->uri = self::$current->cleanUri( $_SERVER["REQUEST_URI"] );

            self::$current->async = self::$current->isAsync();

            self::$current->referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
            
            self::$current->rawUrl = isset( $_SERVER['QUERY_STRING'] ) ? $_SERVER['QUERY_STRING'] : '';
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
     * Return uri without scheme or get params
     *
     * @param string $uri
     * @return string
     */
    protected function cleanUri( $uri )
    {
        $uriArr = explode( '?', $uri );
        
        return '/' . trim( $uriArr[0], '/');
    }

    /**
     * Return processed variables
     *
     * @todo Implement base checks and escape
     *
     * @param array $array
     * @return array
     */
    protected function escapeVarsArray( array $array )
    {
        return $array;
    }

    /**
     * 
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

        $router->performRequest();
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
