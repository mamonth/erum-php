<?php
namespace Erum;

/**
 * Abstract controller implementation.
 *
 * @package Erum
 * @subpackage Core
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
abstract class ControllerAbstract
{
    /**
     * Current request object
     *
     * @var \Erum\Request
     */
    protected $request;
    
    /**
     * Current router object
     *
     * @var \Erum\Router
     */
    protected $router;

    /**
     * Response object
     *
     * @var \Erum\Response
     */
    protected $response;

    /**
     * @var ViewInterface
     */
    public $view;

    /**
     * constructor
     *
     * @param \Erum\Router      $router
     * @param \Erum\Response    $response
     */
    final public function __construct( \Erum\Router $router, \Erum\Response $response )
    {
        $this->request  = $router->request;
        $this->router   = $router;
        $this->response = $response;

        $this->view     = \Erum\ViewManager::getView( $this->request );
    }

    /**
     * Defines name of action which will be fired by default
     * 
     * @return string
     */
    public function getDefaultAction()
    {
        return '';
    }

    public function onBeforeAction( $action )
    {

    }

    public function onAfterAction()
    {
        
    }

    public function getMethod( $action )
    {
        $requestMethod  = $this->request->issetVar('_method') ? strtoupper( $this->request->getVar('_method') ) : $this->request->method;
        $methodName     = $action . $requestMethod;

        return method_exists( $this, $methodName ) ? $methodName : false;
    }

    /**
     * Execute action
     *
     * @param string $action
     * @param array $args
     * @return \Erum\Response
     * @throws \Exception
     */
    final public function execute( $action, array $args = null )
    {
        if( null === $args ) $args = array();

        $method = $this->getMethod( $action );

        if( !$method )
        {
            $this->response->setStatus( 404 );

            if( \Erum::config()->get( 'application' )->get( 'debug' ) )
            {
                throw new \Exception( 'Method ' . $method . ' was not found on ' . get_class( $this ) );
            }

            $action = 'notFound';
        }

        if( false !== $this->onBeforeAction( $action ) )
        {
            $result = call_user_func_array( array( $this, $method ), $args );

            $this->onAfterAction( $result );
        }

        return $this->response;
    }
}
