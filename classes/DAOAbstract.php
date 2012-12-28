<?php
namespace Erum;

/**
 * Abstract Data Access Object implementation
 *
 * WARNING : just implementation example here, no working code !
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 * @package erum
 */
abstract class DAOAbstract
{
    const model = null;
    
    /**
     * Gets single model data by Id.
     * 
     * @param mixed $modelId
     * @return ModelAbstract
     */
    abstract public static function get( $modelId );
//    {
//        if ( !$modelId ) return null;
//        
//            $className = self::getModelClass( true );
//
//        $properties = (array) $className::identityProperty();
//
//        $modelId = (array) $modelId;
//
//        if ( sizeof( $properties ) != sizeof( $modelId ) )
//            throw new Exception( 'Model identity properties count do not equal count of values given.' );
//
//        $model = ModelWatcher::instance()->get( $className, $modelId );
//
//        if ( null === $model )
//        {
//            // Here comes implementation
//        }
//        
//        if( null !== $model )
//        {
//            ModelWatcher::instance()->bind( $model );
//        }
//
//        return $model;
//    }

    /**
     * Delete model data from storage by model Id.
     *
     * @param ModelAbstract $model
     * @return boolean
     */
    abstract public static function delete( ModelAbstract $model );

    /**
     * Gets models list
     *
     * @param string $condition
     * @return array
     */
    abstract public static function getList();
//    {
//            $className = self::getModelClass( true );
//
//        $table = self::getModelTable( $className );
//
//        // Here we are get list from some storage
//        $sourceList = array();
//        
//        $list = array( );
//
//        foreach( $sourceList as $model )
//        {
//           ModelWatcher::instance()->bind( $model );
//        }
//
//        return $list;
//    }

    /**
     * Gets models list by ids
     *
     * @param mixed $condition
     * @return array
     */
    abstract public static function getListByIds( array $ids );

    /**
     * Get model count in storage
     * 
     * @param mixed $condition
     */
    abstract public static function getCount( $condition = false );

    /**
     * @return string
     */
    public static function getModelClass( $dieOnError = false )
    {
        $className = false;
        
        if ( static::model )
        {
            $className = static::model;
        }
        else
        {
            $className = str_ireplace( 'DAO', '', get_called_class() );
        }
        
        if( $dieOnError && !$className )
            throw new \Exception('Can not find class for DAO ' . get_called_class() . '.' );
        
        if ( $dieOnError && !class_exists( $className ) )
            throw new \Exception( 'Class "' . $className . '" can not be found !' );
        
        return $className;
    }

    /**
     * Private constructor. To avoid instance creating.
     *
     */
    private function __construct()
    {
        
    }

}
