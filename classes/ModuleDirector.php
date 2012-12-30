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
        $moduleDirectory = strtolower( $moduleName );

        // run only once for each module
        if( !isset( self::$registered[ $moduleDirectory ] ) )
        {
            $modulePath = \Erum::config()->application->modulesRoot . DS . $moduleDirectory;

            if ( !file_exists( $modulePath ) )
                throw new \Exception( 'Directory "' . $modulePath . '" for module "' . $moduleName . '" was not found.' );

            \Erum::addIncludePath( $modulePath . DS . 'classes' );

            // Check for module main class
            if( !class_exists( $moduleName, true ) )
            {
                throw new \Exception( 'Main module class ' . $moduleName . ' was not found.' );
            }

            // execute module bootstrap method ( init )
            $moduleName::init();

            self::$registered[strtolower( $moduleName )] = array( );
        }
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
            $moduleConfig = self::getModuleConfig( $moduleName, $configAlias );

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

    public static function getModuleConfig( $moduleName, $configAlias = null )
    {
        $moduleConfig = array();

        if ( \Erum::config( $configAlias )->get( 'modules' )->get( $moduleName, true ) )
        {
            $moduleConfig = \Erum::config( $configAlias )->get( 'modules' )->get( $moduleName );

            if ( !is_array( $moduleConfig ) )
            {
                throw new \Exception( 'Configuration "' . $configAlias . '" for module ' . $moduleName . ' must be an array. ' );
            }
        }
        
        return $moduleConfig;
    }

}
