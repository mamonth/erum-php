<?php
namespace Erum;

/**
 * Erum view manager
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */

class ViewManager {

    private static $mimeViews = array();

    /**
     * @param string|array $mimeTypes
     * @param string $viewModule
     */
    public static function register( $mimeTypes, $viewModule )
    {
        // @TODO find out why interface check isn't working

//        if( !is_subclass_of( $viewModule, '\Erum\ViewInterface' ) )
//            throw new \InvalidArgumentException( 'Class must implement \Erum\ViewInterface' );

        foreach( (array)$mimeTypes as $mime )
        {
            self::$mimeViews[ trim( strtolower( $mime ) ) ] = $viewModule;
        }
    }

    /**
     * Return view module by mime type.
     * If array of mime types given - return first acceptable view
     *
     * @param atring|array $mimeTypes
     *
     * @return \Erum\ViewInterface|null
     */
    public static function getByMime( $mimeTypes )
    {
        $moduleName = null;

        foreach( (array)$mimeTypes as $mime )
        {
            $mime = trim( strtolower( $mime ) );

            // cutoff version
            $mime = explode(';', $mime )[0];

            if( isset( self::$mimeViews[ $mime ] ) )
            {
                $moduleName = self::$mimeViews[ $mime ];
                break;
            }
        }

        // init view module just for be sure
        ModuleDirector::init( $moduleName );

        return new $moduleName( ModuleDirector::getModuleConfig( $moduleName ) );
    }

    /**
     * @param \Erum\Request $request
     *
     * @return \Erum\ViewInterface
     */
    public static function getView( \Erum\Request $request )
    {
        $mime = array_map( 'trim', explode( ',', $request->headers['Accept'] ) );

        // @TODO rewrite this part to more accurate mime hadle
        if( $request->extension )
        {
            $extensionMime = null;

            switch( $request->extension )
            {
                case 'html':
                    $extensionMime = 'text/html';
                    break;
                case 'json':
                    $extensionMime = 'application/json';
                    break;
                case 'jsonp':
                    $extensionMime = 'application/jsonp';
                    break;
                case 'xml':
                    $extensionMime = 'application/xml';
                    break;
            }

            if( $extensionMime )
                array_unshift( $mime, $extensionMime );
        }

        $view = self::getByMime( $mime );

        return $view === null ? new ViewStub() : $view;
    }

    protected function __construct(){}

}
