<?php
namespace Erum;

/**
 * Erum module abstract factory
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
abstract class ModuleAbstract implements ModuleInterface
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
        $moduleName = explode( '\\', get_called_class() )[0];

        return \Erum\ModuleDirector::get( $moduleName, $configAlias );
    }

    /**
     * Return module alias
     *
     * @return string
     */
    public static function getAlias()
    {
        return strtolower( get_called_class() );
    }

    /**
     * Bootstrap method.
     * Will be executed only on module first time init.
     * Do not use any application config here!
     *
     */
    public static function init()
    {

    }
}
