<?php
namespace Erum;

/**
 * Request manager
 * 
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * @package Erum
 * @subpackage Core
 * 
 * @property-read string $referer
 * @property-read bool $secured
 * @property-read string $host
 * @property-read string $uri
 * @property-read string $rawUrl
 * @property-read bool $async
 * @property-read array $headers
 * @property-read string $extension
 * @property-read array $post
 * @property-read array $get
 * @property-read string $method
 */
final class Request
{
    const GET       = 'GET';
    const POST      = 'POST';
    const PUT       = 'PUT';
    const DELETE    = 'DELETE';
    const OPTIONS   = 'OPTIONS';
    const CLI       = 'CLI';

    /**
     * Request method ( POST, GET, PUT, DELETE, CLI )
     *
     * @var string
     */
    private $method;

    /**
     * Is request scheme secured ( http or https )
     *
     * @var bool
     */
    private $secured = false;

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
     * @deprecated
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
     * @param int $method
     * @param array $vars
     * @param array $headers
     *
     * @throws \Exception @param int $method
     *
     * @return \Erum\Request
     */
    public static function factory( $url = null, $method = null, array $vars = null, array $headers = null )
    {
        if( null === $url )
        {
            $url = $_SERVER["REQUEST_URI"];

            if( isset( $_SERVER['HTTP_HOST'] ) && $_SERVER['HTTP_HOST'] )
            {
                $url = $_SERVER['HTTP_HOST'] . $url;

                if( isset( $_SERVER['HTTPS'] ) )
                {
                    $url = 'http' . ( ($_SERVER['HTTPS'] && $_SERVER['HTTPS'] !== 'off') ? 's' : '' ) . '://' . $url;
                }
            }
        }

        $urlData = parse_url( $url );

        if( !is_array($urlData) || !isset( $urlData['path'] ) )
        {
            var_dump( $url );
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

        if( isset($urlData['host'] ) && $urlData['host'] )
        {
            $request->host = $urlData['host'];
        }
        elseif( isset( $_SERVER['HTTP_HOST'] ) )
        {
            $request->host = $_SERVER['HTTP_HOST'];
        }

        $request->uri       = $uri;
        $request->method    = $method ? $method : $_SERVER['REQUEST_METHOD'];
        $request->rawUrl    = $url;
        $request->headers   = $headers === null ? self::currentHeaders() : $headers;

        $request->get       = self::escapeVarsArray( $vars ? $vars : $_GET );



        if( $request->method === self::PUT )
        {
            if( null === $vars )
            {
                $inputData = file_get_contents("php://input");

                if( preg_match('/boundary=(.*)$/', $request->getHeader('Content-Type', ''), $matches) )
                {
                    // grab multipart boundary from content type header
                    $boundary = $matches[1];

                    // split content by boundary and get rid of last -- element
                    $inputBlocks = preg_split("/-+$boundary/", $inputData );
                    array_pop( $inputBlocks );

                    // loop data blocks
                    foreach ( $inputBlocks as $id => $block)
                    {
                        if ( empty( $block ) ) continue;

                        // you'll have to var_dump $block to understand this and maybe replace \n or \r with a visibile char

                        // parse uploaded files
                        if ( false !== strpos( $block, 'application/octet-stream' ) )
                        {
                            // match "name", then everything after "stream" (optional) except for prepending newlines
                            preg_match("/name=\"([^\"]*)\".*stream[\n|\r]+([^\n\r].*)?$/s", $block, $matches);
                        }
                        // parse all other fields
                        else
                        {
                            // match "name" and optional value in between newline sequences
                            preg_match('/name=\"([^\"]*)\"[\n|\r]+([^\n\r].*)?\r$/s', $block, $matches);
                        }

                        $inputBlocks[ $matches[1] ] = $matches[2];
                    }

                    $request->post = $inputBlocks;

                    unset( $inputBlocks, $boundary, $id, $block );
                }
                else
                {
                    parse_str( $inputData, $request->post );
                }

                unset( $inputData, $matches );
//                var_dump( $request->post );
//                exit();
            }
        }
        elseif( $request->method === self::POST )
        {
            $request->post  = self::escapeVarsArray( null !== $vars ? $vars : $_POST );
        }

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

        if ( null === $value && self::GET !== $method )
        {
            $value = isset( $this->post[$var] ) ? $this->post[$var] : null;
        }

        return $value;
    }

    /**
     * @param string $header
     * @param bool   $defaultValue
     *
     * @return mixed
     */
    public function getHeader( $header, $defaultValue = false )
    {
        //normalize header name
        $header = str_replace( ' ', '-', ucwords( strtolower( str_replace( array('_', '-'), ' ', $header ) ) ) );

        return isset( $this->headers[ $header ] ) ? $this->headers[ $header ] : $defaultValue;
    }

    /**
     *
     * @param string $var
     * @param mixed $value
     * @param string $method
     *
     * @throws \InvalidArgumentException
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
     * Drop variable and its value from request
     *
     * @param string    $var
     * @param null|int  $method
     * @return \Erum\Request
     */
    public function unsetVar( $var, $method = null )
    {
        if( ( $method === self::GET || null === $method ) && isset( $this->get[ $var ] ) )
        {
            unset( $this->get[ $var ] );
        }

        if( $method === self::POST || $method === self::PUT || $method === self::DELETE || null === $method )
        {
            if( isset( $this->post[ $var ] ) ) unset( $this->post[ $var ] );
        }

        return $this;
    }

    public function issetVar( $var, $method = null )
    {
        $isset = false;

        if( ( $method === self::GET || null === $method ) && isset( $this->get[ $var ] ) )
        {
            $isset = true;
        }

        if( $method === self::POST || $method === self::PUT || $method === self::DELETE || null === $method )
        {
            if( isset( $this->post[ $var ] ) ) $isset = true;
        }

        return $isset;
    }

    /**
     * Executes request
     *
     * @todo implement remote requests
     */
    public function execute()
    {
        return \Erum\Router::factory( $this )->performRequest();
    }

    /**
     * Accessor to internal variables
     *
     * @param string $var
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function __get( $var )
    {
        if ( property_exists( $this, $var ) )
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
