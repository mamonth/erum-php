<?php
namespace Erum;

/**
 * Description of Config
 *
 * @TODO get rid of it
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * @package Erum
 */
class Config implements \ArrayAccess, \IteratorAggregate
{
    /**
     * internal storage array
     *
     * @var array
     */
    protected $storage;

    /**
     * Constructor
     * 
     * @param array $config
     * @param int $recursionLevel
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
     * @param string  $var
     * @param bool    $silent
     *
     * @throws \Exception
     *
     * @return mixed
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

    // IteratorAggregator implementation
    public function getIterator()
    {
        return new \ArrayIterator( $this->storage ? $this->storage : array() );
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
