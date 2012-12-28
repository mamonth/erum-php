<?php

namespace Erum;

class ModelWatcher
{

    private static $instance;
    private $container;

    private function __construct()
    {
        
    }

    private function __clone()
    {
        
    }

    /**
     * Enter description here...
     *
     * @return ModelWatcher
     */
    public static function instance()
    {
        if ( null === self::$instance )
            self::$instance = new self;

        return self::$instance;
    }

    /**
     * Enter description here...
     *
     * @param ModelAbstract $model
     * @param mixed $property
     */
    public function bind( ModelAbstract $model, $property = null )
    {
        $modelClass = get_class( $model );

        if ( null === $property )
            $properties = (array) $modelClass::identityProperty();
        else
            $properties = (array) $property;

        $propertyKey = implode( ':', $properties );

        $valueKey = array( );

        foreach ( $properties as $prop )
        {
            $valueKey[] = $model->$prop;
        }

        $valueKey = implode( ':', $valueKey );

        if ( !isset( $this->container[$modelClass] ) )
            $this->container[$modelClass] = array( );

        if ( !isset( $this->container[$modelClass][$propertyKey] ) )
            $this->container[$modelClass][$propertyKey] = array( );

        $this->container[$modelClass][$propertyKey][$valueKey] = $model;
    }

    /**
     * Enter description here...
     *
     * @param string $modelClass
     * @param mixed $value
     * @param mixed $property
     * 
     * @return ModelAbstract
     */
    public function get( $modelClass, $value, $property = null )
    {
        $model = null;

        $key = $this->getKeys( $modelClass, $value, $property );

        if ( isset( $this->container[ $modelClass ][ $key['property'] ][ $key['value'] ] ) )
        {
            $model = $this->container[ $modelClass ][ $key['property'] ][ $key['value'] ];
        }

        return $model;
    }

    public function unbind( $modelClass, $value, $property = null )
    {
        $key = $this->getKeys( $modelClass, $value, $property );

        if ( isset( $this->container[ $modelClass ][ $key['property'] ][ $key['value'] ] ) )
        {
            unset( $this->container[ $modelClass ][ $key['property'] ][ $key['value'] ] );
 
            return true;
        }

        return false;
    }

    protected function getKeys( $modelClass, $value = null, $property = null )
    {
        if ( !class_exists( $modelClass ) )
            throw new \Exception( 'Could not find class ' . $modelClass );
        
        if ( !in_array( 'Erum\ModelAbstract', class_parents( $modelClass ) ) )
            throw new \Exception( 'Class ' . $modelClass . ' is not correct erum model.' );

        if ( null === $property )
        {
            $properties = (array) $modelClass::identityProperty();
        }
        else
        {
            $properties = (array) $property;
        }

        return array(
            'property' => implode( ':', $properties ),
            'value' => implode( ':', (array) $value )
        );
    }

}
