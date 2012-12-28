<?php
namespace Erum;

/**
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
class DebugPoint
{

    private $point;

    public function __construct( $point )
    {
        $this->point = $point;

        Debug::instance()->enterPoint( $this->point );
    }

    public function __destruct()
    {
        Debug::instance()->exitPoint( $this->point );
    }

}
