<?php
namespace Erum;

interface ViewInterface extends ModuleInterface
{
    public function setTemplate( $templateName );

    public function getTemplate();

    public function setVar( $variable, $value );

    public function display();

    public function fetch();
}