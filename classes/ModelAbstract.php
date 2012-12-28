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
        $class = new \ReflectionClass( get_called_class() );
		
        if( $class->hasMethod( 'get' . ucfirst($variable) ) )
        {
            return $this->{'get' . ucfirst($variable)}();
        }
        
	    if( $class->hasProperty( $variable ) )
    	{
            return $this->$variable;
	    }
    
	    throw new \Exception('Trying to get value of property "' . $variable . '" that undeclared in class ' . get_class($this) );
    }
	
    public final function __set( $variable, $value )
    {
        $class = new \ReflectionClass( get_called_class() );

        if( $class->hasMethod( 'set' . ucfirst($variable) ) )
        {
            $this->{'set' . ucfirst($variable)}( $value );
        }
        elseif( $class->hasProperty( $variable ) )
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
        
        throw new \Exception('Trying to set value of property $' . $variable . ' that undeclared in class ' . get_called_class() );
    }
    
    /**
     * Enter description here...
     *
     * @return mixed
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
}
