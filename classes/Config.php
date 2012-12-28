<?php
namespace Erum;

/**
 * Description of Config
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * @package Erum
 */
class Config implements \ArrayAccess
{
    /**
     *
     * @var array
     */
    protected $storage;

    /**
     * 
     * 
     * @param array $config
     * @param boolean $recursive 
     */
    public function __construct( array $config, $recursionLevel = 0 )
    {
        while( list( $key, $conf ) = each( $config ) )
        {
            $this->storage[ $key ] = ( is_array( $conf ) && $recursionLevel > 0 ) ? new self( $conf, $recursionLevel - 1 ) : $conf;
        }
    }
    
    /**
     * Get config variable
     * 
     * @param string $var
     */
    public function get( $var, $silent = false )
    {
        if( !isset( $this->storage[ $var ] ) )
        {
            if( !$silent ) throw new \Exception( 'Trying access to non exist property "' . $var . '"' );
            
            return null;
        }
        else
        {
            return $this->storage[ $var ];
        }
    }

    public function __get( $var )
    {
        return $this->get( $var );
    }
    
    // ArrayAccess implementation
    
    public function offsetGet( $var )
    {
        return $this->get( $var );
    }
    
    public function offsetExists( $var )
    {
        return isset( $this->storage[ $var ] );
    }
    
    public function offsetSet( $var, $value )
    {
        throw new Exception( 'Cannot redeclare or set properties in config.' );
    }
    
    public function offsetUnset( $var )
    {
        throw new Exception( 'Cannot unset properties in config.' );
    }
}
