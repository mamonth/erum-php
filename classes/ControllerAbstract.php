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
     * constructor
     * 
     * @param \Erum\Router $router 
     */
    final public function __construct( \Erum\Router $router )
    {
        $this->request  = $router->request;
        $this->router   = $router;
        $this->response = \Erum\Response::factory();
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

    public function onBeforeAction()
    {
        
    }

    public function onAfterAction()
    {
        
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
        
        $actionType = $this->request->method;
        
        if ( !method_exists( $this, $action . $actionType ) )
            throw new \Exception( 'Action ' . $action . $actionType . ' not found in ' . get_class( $this ) );
        
        $this->onBeforeAction( $action, $actionType );
        
        $result = call_user_func_array( array( $this, $action . $actionType ), $args );
        
        $this->onAfterAction( $result );

        return $this->response;
    }

}
