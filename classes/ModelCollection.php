<?php
namespace Erum;

/**
 * Base model collection
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * @package Erum
 * @subpackage Core
 *
 */
class ModelCollection implements \Iterator, \ArrayAccess, \Countable, \JsonSerializable
{
    /**
     * Model class name
     *
     * @var string
     */
    protected $modelClass;

    /**
     * Item collection storage
     *
     * @var array
     */
    protected $items = array();

    /**
     * Item keys list
     *
     * @var array
     */
    protected $keys = array();

    /**
     * Map id => item key
     *
     * @var array
     */
    protected $id2key = array();

    /**
     * Current key position storage
     *
     * @var int
     */
    protected $position = 0;

    /**
     *
     * @param array $modelList
     * @param string $className
     * @return ModelCollection
     */
    public static function factory( array $modelList, $className = '\Erum\ModelAbstract' )
    {
        if( is_subclass_of( get_called_class(), __CLASS__ ) )
        {
            $collection = new static( $className );
        }
        else
        {
            $collection = new self( $className );
        }

        $collection->append( $modelList );

        return $collection;
    }

    /**
     * @param string $modelClass
     * @throws Exception
     */
    public function __construct( $modelClass = '\Erum\ModelAbstract' )
    {
        if( !class_exists( $modelClass ) )
            throw new \Erum\Exception('Class "' . $modelClass . '" not found.');

        if( !is_a( $modelClass, '\Erum\ModelAbstract' , true ) )
            throw new \Erum\Exception('Class "' . $modelClass . '" must be equal or successor of \Erum\ModelAbstract.');

        $this->modelClass = $modelClass;
    }

    /**
     * Append array of models
     *
     * @param array $modelList
     * @internal param array $items
     * @return ModelCollection
     */
    public function append( array $modelList )
    {
        foreach( $modelList as $model )
        {
            $this->offsetSet( null, $model );
        }

        return $this;
    }

    /**
     * Get collection internal keys
     *
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * Order collection by model property
     *
     * @param $propertyName
     * @param bool $asc
     * @return ModelCollection
     */
    public function orderBy( $propertyName, $asc = true )
    {
        $tmp = array();

        foreach( $this->keys as $key )
        {
            $tmp[$key] = $this->items[$key]->$propertyName;
        }

        if( $asc === true )
        {
            asort( $tmp );
        }
        else
        {
            arsort( $tmp );
        }

        $this->keys = array_keys( $tmp );

        return $this;
    }

    /**
     * Filter collection by check any of conditions values
     * match model properties, specified by condition keys.
     *
     * @param array $conditions
     * @return ModelCollection
     */
    public function applyFilterAny( $conditions )
    {
        $keys = array();

        foreach( $this->keys as $key )
        {
            foreach( $conditions as $property => $value )
            {
                if( $this->items[$key]->$property === $value )
                {
                    $keys[] = $key;
                    break;
                }
            }
        }

        $this->keys = $keys;

        return $this;
    }

    /**
     * Filter collection by check all conditions values
     * match model properties, specified by condition keys.
     *
     * @param array $conditions
     * @return ModelCollection
     */
    public function applyFilterAll( $conditions )
    {
        $keys = array();

        foreach( $this->keys as $key )
        {
            foreach( $conditions as $property => $value )
            {
                if( $this->items[$key]->$property != $value ) continue 2;
            }

            $keys[] = $key;
        }

        $this->keys = $keys;

        return $this;
    }

    /**
     * Filter collection, by applying $callback to each model
     *
     *
     * @param $callback
     * @throws \Erum\Exception
     * @return ModelCollection
     */
    public function filter( $callback )
    {
        if( !is_callable( $callback ) )
            throw new \Erum\Exception( 'Callable must be provided' );

        $this->keys = array();

        $this->rewind();

        reset( $this->items );

        while( list( $key, $item ) = each( $this->items ) )
        {
            if( call_user_func( $callback, $key, $item ) )
            {
                $this->keys[] = $key;
            }
        }

        return $this;
    }

    /**
     * Drop all filters and orders
     *
     * @return ModelCollection
     */
    public function reset()
    {
        $this->keys = array_keys( $this->items );

        $this->rewind();

        return $this;
    }

    /**
     * @param $id
     * @return \Erum\ModelAbstract|null
     */
    public function getById( $id )
    {
        $id = ModelAbstract::id2string( $id );

        return isset( $this->id2key[ $id ] ) ? $this->items[ $this->id2key[ $id ] ] : null;
    }

    public function unsetById( $id )
    {
        $id = ModelAbstract::id2string( $id );

        $key = isset( $this->id2key[ $id ] ) ? $this->id2key[ $id ] : false;

        if( false !== $key ) $this->offsetUnset( $key );

        return $key ? true : false;
    }

    public function getIds()
    {
        return array_keys( $this->id2key );
    }

    /**
     * @param ModelAbstract $model
     */
    public function push( ModelAbstract $model )
    {
        $this->offsetSet( null, $model );
    }

    public function pop()
    {
        $key = array_pop( $this->keys );

        $item = $this->items[ $key ];

        unset( $this->items[ $key ] );

        return $item;
    }

    public function unshift( $item )
    {
        $key = max($this->keys);

        array_unshift( $this->keys, $key );

        $this->items[ $key ] = $item;
    }

    // implement ArrayAccess interface

    public function offsetGet( $key )
    {
        return isset( $this->items[$key] ) ? $this->items[$key] : null;
    }

    /**
     * @param mixed $key
     * @param \Erum\ModelAbstract $model
     * @throws \InvalidArgumentException
     */
    public function offsetSet( $key, $model )
    {
        if( false === ( $model instanceof $this->modelClass ) )
            throw new \InvalidArgumentException('$model must be instance of ' . $this->modelClass );

        $id = $model->getId();

        if( null === $key && isset( $this->id2key[ $id ] ) )
        {
            $key = $this->id2key[ $id ];
        }

        if( null === $key )
        {
            $key = sizeof( $this->keys ) ? max( $this->keys ) + 1 : 0;
        }

        if( !isset( $this->items[$key] ) ) $this->keys[] = $key;

        if( $id ){

            $this->id2key[ $id ] = $key;
        }

        $this->items[ $key ] = $model;

        $this->reset();
    }

    public function offsetExists( $key )
    {
        return isset( $this->items[ $key ] );
    }

    public function offsetUnset( $key )
    {
        $key = (int)$key;
        $isset = isset( $this->items[ $key ] );

        if( $isset )
        {
            if( false !== ( $key = array_search( $key, $this->keys, true ) ) ) unset( $this->keys[ $key ] );

            if( ( $id = $this->items[ $key ]->getId() ) ) unset( $this->id2key[ $id ] );

            unset( $this->items[ $key ] );

            $this->rewind();
        }

        return $isset;
    }

    // implements Countable interface

    public function count()
    {
        return sizeof( $this->keys );
    }

    // implements Iterator interface

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->items[ $this->key() ];
    }

    public function key()
    {
        return $this->keys[ $this->position ];
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        if( !isset( $this->keys[ $this->position ] ) ) return false;

        return isset( $this->items[ $this->keys[ $this->position ] ] );
    }


    public function jsonSerialize()
    {
        $array = array();

        foreach( $this->keys as &$key )
        {
            $array[ $key ] = $this->items[ $key ];
        }

        return $array;
    }
}