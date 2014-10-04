<?php
namespace Erum;

/**
 * Abstract Data Access Object
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
     * @return ModelAbstract[]
     */
    abstract public static function getList();

    /**
     * Gets models list by ids
     *
     * @param array $ids
     * @return ModelAbstract[]
     */
    abstract public static function getListByIds( array $ids );

    /**
     * Get model count in storage
     * 
     * @param mixed $condition
     */
    abstract public static function getCount( $condition = false );

    /**
     * Retrieve model class from constant "model" or from DAO class name
     *
     * @param boolean $dieOnError - define if exception will be raised on error
     * @throws \Exception
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
