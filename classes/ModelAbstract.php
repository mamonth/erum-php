<?php
namespace Erum;

/**
 * Abstract model class
 *
 * @property-read int|array $id
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
abstract class ModelAbstract
{
    protected $id;
    
    public final function __get( $variable )
    {
        $method = 'get' . ucfirst($variable);

        if( method_exists( $this, $method ) )
        {
            return $this->$method();
        }
        
	    if( property_exists( $this, $variable ) )
    	{
            return $this->$variable;
	    }
    
	    throw new UndeclaredArgumentException('Trying to get value of property "' . $variable . '" that undeclared in class ' . get_class($this) );
    }
	
    public final function __set( $variable, $value )
    {
        $method = 'set' . ucfirst($variable);

        if( method_exists( $this, $method ) )
        {
            return $this->$method( $value );
        }
        elseif( property_exists( $this, $variable ) )
        {
            $var = new \ReflectionProperty( $this, $variable );

            if( !$var->isPrivate() )
            {
                $this->$variable = $value;
                return true;
            }
            else
            {
                throw new \Exception('Trying to set value of private property "' . $variable . '" declared in class ' . get_called_class() );
            }
        }	
        
        throw new UndeclaredArgumentException('Trying to set value of property $' . $variable . ' that undeclared in class ' . get_called_class() );
    }

    public final function __isset( $variable )
    {
        return property_exists( $this, $variable ) || method_exists( $this, 'get' . ucfirst( $variable ) );
    }
    
    /**
     * Method should return all properties, that makes model unique ( like primary key ).
     * Return value will be treated as model signature.
     *
     * @return string|array
     */
    public static function identityProperty()
    {
        return 'id';
    }
    
    public final function getId()
    {
        if( isset($this->id) ) return $this->id;
        
        $modelName = get_called_class();
        $properties = (array)$modelName::identityProperty();
        
        $values = array();
        
        foreach( $properties as $property )
        {
            $val = $this->$property;
            
            if( $val !== null ) $values[] = $val;
        }
        
        return implode( ':', $values );
    }

    public function arrayFill( array $data, array &$errors = array(), $skipUndeclared = true )
    {
        foreach( $data as $key => &$value )
        {
            try
            {
                $this->__set( $key, $value );
            }
            catch( ValidateArgumentException $e )
            {
                $errors[ $key ] = $e->getMessage();
            }
            catch( UndeclaredArgumentException $e )
            {
                if( !$skipUndeclared )
                {
                    throw $e;
                }
            }
        }

        return empty( $errors );
    }
}

class ValidateArgumentException extends \Erum\Exception {};