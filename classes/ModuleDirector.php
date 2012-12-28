<?php

namespace Erum;

/**
 * Module registry
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
final class ModuleDirector
{

    private static $registered = array( );

    public static function init( $moduleName )
    {
        $modulePath = \Erum::config()->application->modulesRoot . DS . strtolower( $moduleName );

        if ( !file_exists( $modulePath ) )
            throw new \Exception( 'Directory "' . $modulePath . '" for module "' . $moduleName . '" was not found.' );

        // Trying to load module bootstrap file ( init.php )
        if ( file_exists( $modulePath . DS . 'init.php' ) && !isset( self::$registered[$moduleName] ) )
        {
            include_once $modulePath . DS . 'init.php';
        }

        \Erum::addIncludePath( $modulePath . DS . 'classes' );

        self::$registered[strtolower( $moduleName )] = array( );
    }

    public static function get( $moduleName, $configAlias = 'default' )
    {
        $moduleClass = '\\' . $moduleName;
        $moduleAlias = strtolower( $moduleName );

        if ( !isset( self::$registered[$moduleAlias] ) )
        {
            self::init( $moduleName );
        }

        if ( !isset( self::$registered[$moduleAlias][$configAlias] ) )
        {
            $moduleConfig = self::getModuleConfig( $moduleAlias, $configAlias );

            self::$registered[$moduleAlias][$configAlias] = new $moduleClass( $moduleConfig );

            unset( $moduleClass, $moduleConfig );
        }

        return self::$registered[$moduleAlias][$configAlias];
    }

    /**
     * Not implemented
     *
     * @param string $moduleName
     */
    public static function isExist( $moduleName )
    {
        
    }

    /**
     * Provides registered module names list
     * 
     * @return array
     */
    public static function getRegistered()
    {
        return array_keys( self::$registered );
    }

    public static function getModuleConfig( $moduleAlias, $configAlias = null )
    {
        $moduleConfig = array();
        
        if ( \Erum::config( $configAlias )->get( 'modules' )->get( $moduleAlias, true ) )
        {
            $moduleConfig = \Erum::config( $configAlias )->get( 'modules' )->get( $moduleAlias );

            if ( !is_array( $moduleConfig ) )
            {
                throw new \Exception( 'Configuration "' . $configAlias . '" for module ' . $moduleAlias . ' must be an array. ' );
            }
        }
        
        return $moduleConfig;
    }

}
