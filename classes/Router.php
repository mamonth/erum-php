<?php
namespace Erum;

/**
 * Handles route routine
 * 
 * @package Erum
 * @subpackage Core
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * 
 * @property \Erum\Request $request
 * @property string $section
 * @property string $controller Current controller full class name
 * @property string $action Current action name
 * @property array $requestRemains
 */
class Router
{
    /**
     * Current request object
     * 
     * @var \Erum\Request
     */
    protected $request;

    /**
     * Controller class name
     *
     * @var string
     */
    protected $controller;

    /**
     * Action (method) name
     *
     * @var string
     */
    protected $action;

    /**
     * Array of uri parts - left after controller and action exclude
     *
     * @var array
     */
    protected $requestRemains = array( );

    /**
     * Map request object hast to router
     *
     * @var Router[]
     */
    private static $requestsMap = array();

    /**
     * Creates new router for request, or, return existed
     *
     * @param Request $request
     * @return Router
     */
    public static function factory( Request $request )
    {
        $requestHash = spl_object_hash( $request );

        if( !isset( self::$requestsMap[ $requestHash ] ) )
        {
            self::$requestsMap[ $requestHash ] = new self( $request );
        }

        return self::$requestsMap[ $requestHash ];
    }

    /**
     * Get router for current request
     *
     * @return Router
     */
    public static function current()
    {
        return self::factory( Request::current() );
    }

    public function __construct( Request $request )
    {
        $this->request = $request;
    }

    /**
     * Performs a request by given Request object data
     *
     * @return \Erum\Response
     */
    public function performRequest()
    {
        if ( isset( \Erum::config()->routes[$this->request->uri] ) )
        {
            self::redirect( '/' . trim( \Erum::config()->routes[$this->request->uri], '/' ), false, 200 );
        }

        $response = \Erum\Response::factory();

        if( false === ( @list( $this->controller, $this->action ) = self::getController( $this->request->uri, $this->requestRemains ) ) )
        {
            $response->setStatus( 404 );

            $response->set('message', 'Page not found');
        }
        else
        {
            /* @var $controller ControllerAbstract */
            $controller = new $this->controller( $this, $response );

            if( null === $this->action )
            {
                $this->action = $controller->getDefaultAction();
            }

            try
            {
                $controller->execute( $this->action, $this->requestRemains );
            }
            catch( \Exception $e )
            {
                $response->setStatus( 502 );

                // Handle application exceptions
                if ( \Erum::config()->get( 'application' )->get( 'debug' ) )
                {
                    $response
                        ->set('message', 'Exception: "' . $e->getMessage() . '"')
                        ->set('file', $e->getFile() )
                        ->set('line', $e->getLine() )
                        ->set('trace', $e->getTrace() );
                }

                $controller->onAfterAction();
            }

            if( $controller->view )
            {
                foreach( $response->data as $var => $value )
                {
                    $controller->view->setVar( $var, $value );
                }

                $response->setBody( $controller->view->fetch() );

                $response->addHeader( 'Content-type', $controller->view->getContentType() );
            }

            unset( $controller );
        }

        return $response;
    }
    
    /**
     * Find valid controller class name and action from uri.
     * Returns array ( 0 => Controller class, 1=> action name )
     * 
     * @param type $uri
     * @param array $remains
     * @return array | false
     */
    public static function getController( $uri, array &$remains = null )
    {
        $remains    = array();
        $controller = null;
        $action     = null;
        
        list( $uri ) = explode( '?', $uri );
        
        $requestArr = array_filter( explode( '/', trim( $uri, '/' ) ) );

        if( empty( $requestArr ) )
        {
            $requestArr[] = 'index';
        }

        $namespace = '\\' . \Erum::config()->application['namespace'] . '\\';
        
        while ( $chunk = array_shift( $requestArr ) )
        {
            $chunkNormalized = ucfirst( $chunk );
            $chunkNamespaced = $namespace . $chunkNormalized;

            if ( class_exists( $chunkNamespaced . 'Controller' ) )
            {
                $controller = $chunkNamespaced . 'Controller';
            }
            elseif( class_exists( $chunkNamespaced . '\\IndexController' ) )
            {
                $controller = $chunkNamespaced . '\\IndexController';
            }
            elseif ( null !== $controller )
            {
                $remains[] = $chunk;
                $remains = array_merge( $remains, $requestArr );
                break;
            }
            else
            {
                if( empty( $requestArr ) )
                {
                    $controller = $namespace . 'IndexController';
                    break;
                }
            }
            
            $namespace .= $chunkNormalized . '\\';
        }

        if( $controller )
        {
            if ( !empty( $remains ) )
            {
                $action = array_shift( $remains );
            }
            else
            {
                $action = $chunk;
            }

            // methods can't be numeric
            if( is_numeric( $action ) )
            {
                array_unshift($remains, $action);
                $action = null;
            }

            return array( $controller, $action );
        }
        
        return false;
    }

    /**
     * Build correct uri path by controller
     *
     * @param ControllerAbstract || string $controller - may be controller instance, or name
     * @param string $action
     * @param array  $args
     *
     * @throws Exception
     * @return string
     */
    public static function getPath( $controller, $action = null, array $args = null )
    {
        if( is_object( $controller ) )
        {
            if( ! $controller instanceof \Erum\ControllerAbstract )
                throw new \Erum\Exception( 'Controller must be string or \Erum\ControllerAbstract instance.');
        }
        elseif( !is_string( $controller ) )
        {
            throw new \Erum\Exception( 'Controller must be string or \Erum\ControllerAbstract instance, ' . gettype( $controller ) . ' given.'  );
        }
        else
        {
            $controller = new $controller( new self( \Erum\Request::current() ) );
        }
        
        $pathArray = array_filter( explode( '\\', strtolower( get_class( $controller ) ) ) );
        
        // if given controller not from current application namespace - ignoring.
        // @TODO review this part
        if( strtolower( \Erum::config()->application['namespace'] ) != array_shift( $pathArray ) )
        {
            return false;
        }
        
        $controllerName = str_ireplace( 'Controller', '', array_pop( $pathArray ) );
        
        if( strtolower( $controllerName ) != 'index' )
        {
            array_push( $pathArray, $controllerName );
        }
        
        if( null !== $action && $action != $controller->getDefaultAction() )
        {
            array_push( $pathArray, $action );
        }
        
        if( null !== $args )
        {
            $pathArray += $args;
        }
        
        return '/' . implode( '/', $pathArray );
        
    }

    /**
     * Redirect.
     *
     * @param string  $url
     * @param boolean $isExternal
     * @param int     $statusCode
     *
     * @throws Exception
     * @throws \Exception
     */
    public static function redirect( $url, $isExternal = false, $statusCode = 200 )
    {
        $statusCode = (int)$statusCode;
        $statusText = Response::getStatusText( $statusCode );

        if ( $statusText )
        {
            header( 'HTTP/1.0 ' . $statusCode . ' ' . $statusText  );
        }

        if ( $isExternal )
        {
            header( 'Location: ' . $url );
            exit();
        }

        // Cycling check
        if( Request::initial() !== Request::current() && $url === Request::current()->rawUrl )
        {
            throw new \Erum\Exception( 'Infinity loop detected.' );
        }

        try
        {
            $response = \Erum\Request::factory( $url, Request::current()->method )->execute();

            $response->sendHeaders();

            echo $response->body;
        }
        catch ( \Exception $e )
        {
            throw new \Exception( $e->getMessage()
                    . ' on line ' . $e->getLine()
                    . ' in file "' . $e->getFile() . '"', (int)$e->getCode(), $e );
        }

        exit( 0 );
    }

    public function __get( $var )
    {
        if ( property_exists( $this, $var ) )
        {
            return $this->$var;
        }
        else
        {
            throw new \Exception( 'Requested variable "' . $var . '" not exist!' );
        }
    }

}
