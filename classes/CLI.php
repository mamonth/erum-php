<?php
namespace Erum;

/**
 * Erum command Line Interface tools
 *
 * Usage example:
 *
 * #./script.php -i 123 --bool, --string "Test string"
 *
 * CLI::registerOption( 'i', 0, CLI::PARAM_INT | CLI::PARAM_REQUIRED , 'Parameter for testing purpose.' );
 * CLI::registerOption( array( 'b', 'bool' ), 0, CLI::PARAM_BOOL, 'Second parameter for testing purpose.' );
 * CLI::registerOption( array( 's', 'string' ), 0, null, 'Third parameter for testing purpose.' );
 * CLI::fetchOptions();
 *
 * Alternative way:
 *
 * CLI::current()
 *      ->registerOption( 'i', 0, CLI::PARAM_INT | CLI::PARAM_REQUIRED , 'Parameter for testing purpose.' )
 *      ->registerOption( array( 'b', 'bool' ), 0, CLI::PARAM_BOOL, 'Second parameter for testing purpose.' )
 *      ->registerOption( array( 's', 'string' ), 0, null, 'Third parameter for testing purpose.' )
 *      ->fetchOptions();
 *
 * Get option values:
 *
 * CLI::getOption( 'bool' )
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
class CLI
{
    const PARAM_INT = 1;
    const PARAM_BOOL = 2;
    const PARAM_STR = 4;
    const PARAM_REQUIRED = 8;
    
    /**
     * Enter description here...
     *
     * @var CLI
     */
    private static $instance;
    
    /**
     * Enter description here...
     *
     * @var array
     */
    protected $params;
    
    /**
     * Enter description here...
     *
     * @return CLI
     */
    public static function current()
    {
        if( null === self::$instance )
        {
            self::$instance = new self;
        }
        
        return self::$instance;
    }
    
    /**
     * Registering CLI option
     *
     * @param mixed $name
     * @param mixed $default
     * @param int $type
     * @param string $description
     * @return CLI
     */
    protected function registerOption( $name, $default = null, $flags = self::PARAM_STR, $description = null )
    {
        if( $flags & self::PARAM_REQUIRED && $flags & self::PARAM_BOOL )
            throw new \Exception( 'Option can not be boolean and required on the same time!');

        $names = !is_array( $name ) ? array( $name ) : $name;
        
        $param = array(
            'short' => str_replace( array('-', ' '), '', trim($names[0]) ),
            'long' => isset( $names[1] ) ? str_replace( array('-', ' '), '', trim($names[1]) ) : null,
            'default' => $default,
            'flags' => (int)$flags,
            'description' => $description,
            'value' => $default
        );
        
        $this->params[ $param['short'] ] =& $param;
        
        if( $param['long'] ) $this->params[ $param['long'] ] =& $param;
                
        return $this;
    }
    
    /**
     * Obviously
     *
     * @param boolean $dieOnError
     * @return CLI
     */
    protected function fetchOptions( $dieOnError = true )
    {
        $shortParams = array();
        $longParams = array();
        
        foreach( $this->params as $param )
        {
            $paramOptions = !($param['flags'] & self::PARAM_BOOL) ? ':' : '';

            $shortParams[ $param['short'] . $paramOptions ] = true;
            
            if( !isset( $param['long'] ) || !$param['long'] ) continue;
            
            $longParams[ $param['long'] . $paramOptions ] = true;
        }
        
        $paramValues = getopt( implode( '', array_keys( $shortParams ) ), array_keys( $longParams ) );
        
        foreach ( $this->params as &$param )
        {
            if( 
                ($param['flags'] & self::PARAM_REQUIRED) && 
                ( !isset( $paramValues[ $param['short'] ] ) && !isset( $paramValues[ $param['long'] ] ) )
            )
            {
                if( $dieOnError ){ $this->displayHelp(); exit(); }
            }
            
            if( isset( $paramValues[ $param['short'] ] ) || isset( $paramValues[ $param['long'] ] ) )
            {
                $param['value'] = ( $param['flags'] & self::PARAM_BOOL ) ? 
                    true : 
                    (isset( $paramValues[ $param['short'] ] ) ?
                        $paramValues[ $param['short'] ] :
                        $paramValues[ $param['long'] ]);
            }

            if( $param['flags'] & self::PARAM_INT  )
            {
                $param['value'] = (int)$param['value'];
            }
        }
        
        return $this;
    }

    /**
     * Displays help message based on registered options data
     */
    protected function displayHelp()
    {
        $paramStr = array();
        
        foreach( $this->params as $param )
        {
            $paramStr[ $param['short'] ] = "   -" . $param['short'];

            if( isset( $param['long'] ) && $param['long'] )
                $paramStr[ $param['short'] ] .= ', --' . $param['long'];
                
            if( $param['flags'] & self::PARAM_INT )
            {
                $paramStr[ $param['short'] ] .= ' <int>';
            }
            elseif( $param['flags'] & self::PARAM_STR )
            {
                $paramStr[ $param['short'] ] .= ' <string>';
            }
            
            if( $param['flags'] & self::PARAM_REQUIRED )
                $paramStr[ $param['short'] ] .= ', Required';

            $strlen = strlen( $paramStr[ $param['short'] ] );
            
            
            $paramStr[ $param['short'] ] .= str_repeat( "\t", 4 - ceil( ($strlen - 5) / 8 ) ) . $param['description'];
        }

        echo sizeof( $this->params ) ? "Arguments: \n" . implode( "\n", $paramStr ) . "\n\n\n" : '';
    }
    
    /**
     * Returns script runtime parameter value
     *
     * @param string $option script runtime option (short or long)
     * @return mixed
     */
    protected function getOption( $option )
    {
        if( !isset( $this->params[ $option ] ) )
            throw new \Exception( 'Option "' . $option . '" was not registered for reading!' );

        return $this->params[ $option ]['value'];
    }
    
    /**
     * Enter description here...
     *
     * @param int $length
     * @return string
     */
    protected function read( $timeout = 60 )
    {
        // fgets( STDIN ); NOT WORK !
        $input = '';
        
        $timestart = time();
        do
        {
            echo ".";
            $chr = fread( STDIN, 1024 );
            
            if( $chr == "\n" ) break;
            
            $input .= $chr;
        }
        while( $timestart + $timeout <= time() );
        
        return $input;
    }
    
    public static function __callStatic( $method, array $args )
    {
        $reflection = new \ReflectionClass( __CLASS__ );
        
        if( !$reflection->hasMethod( $method ) )
            throw new \Exception( 'Method ' . __CLASS__ . '::' . $method . ' does not exist!' );
        
        if( $reflection->getMethod( $method )->isStatic() )
        {
            return call_user_func_array( __CLASS__ . '::' . $method, $args );
        }
        else
        {
            return call_user_func_array( array( self::current(), $method ), $args );
        }
    }
    
    public function __call( $method, array $args )
    {
        call_user_func_array( array( $this, $method ), $args );
    }
}

