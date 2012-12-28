<?php
namespace Erum;

/**
 * Erum module abstract factory
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
abstract class ModuleAbstract
{
    /**
     * Constructor definition. Must accept array with current config
     *
     * @param array $config
     */
    abstract public function __construct( array $config );

    /**
     * @param string $configAlias
     * @return \Erum\ModuleAbstract
     */
    public static function factory( $configAlias = 'default' )
    {
        $moduleName = array_pop( explode( '\\', get_called_class() ) );

        return \Erum\ModuleDirector::get( $moduleName, $configAlias );
    }
    
    public static function getAlias( $module = null )
    {
        return strtolower( $module ?  $module : get_called_class() );
    }
}
