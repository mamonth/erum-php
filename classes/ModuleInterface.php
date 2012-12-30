<?php
namespace Erum;

/**
 * Erum module interface
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
interface ModuleInterface
{
    /**
     * Constructor definition. Must accept array with current config
     *
     * @param array $config
     */
    public function __construct( array $config );

    /**
     * @param string $configAlias
     * @return \Erum\ModuleAbstract
     */
    public static function factory( $configAlias = 'default' );
}