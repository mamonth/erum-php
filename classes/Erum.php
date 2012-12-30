<?php

if ( !defined( 'DS' ) )
    define( 'DS', DIRECTORY_SEPARATOR );

final class Erum
{

    /**
     * Current core instance
     *
     * @var Erum
     */
    private static $instance;
    /**
     * Current default config alias
     * 
     * @var string
     */
    private $configAlias = 'default';
    /**
     * Configs storage
     * 
     * @var \Erum\Config
     */
    private $config;
    /**
     * 
     * @var Exceptionizer
     */
    private $exceptionWatcher;
    /**
     * only for fast autoloading purpose
     * 
     * @var array
     */
    private $namespaceToPath = array( );

    /**
     * constructor.
     */
    private function __construct()
    {
        spl_autoload_register( __CLASS__ . '::autoload', true, true );

        $this->exceptionWatcher = new Exceptionizer();
    }

    /**
     * Retuns current application core instance
     *
     * @deprecated because instance() looks more nice for me
     * @return \Erum
     */
    public static function getInstance()
    {
        return self::instance();
    }

    /**
     * Retuns application core instance
     * 
     * @return \Erum 
     */
    public static function instance()
    {
        if ( null === self::$instance )
        {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Attach application config to core
     * You may attach more than one config to core for autoloading classes from multiple app 
     * or having access to multiple config params.
     * 
     * @param array $config
     * @param string $alias
     */
    public static function attachConfig( array $config, $alias = null )
    {
        if ( null === $alias )
            $alias = self::instance()->configAlias;

        if ( isset( self::instance()->config[$alias] ) )
        {
            throw new Exception( 'Config with alias "' . $alias . '" already set.' );
        }

        // Check for required params
        if ( !isset( $config['application'] ) )
            throw new Exception( 'Provided config "' . $alias . '" does not have required "application" section.' );

        if ( !isset( $config['application']['namespace'] ) )
            throw new Exception( 'Provided config "' . $alias . '" does not have  required param "namespace" in "application" section.' );

        if ( !isset( $config['application']['root'] ) )
            throw new Exception( 'Provided config "' . $alias . '" does not have  required param "root" in "application" section.' );

        // Merge application part with default
        $config['application'] = array_merge( include( dirname( __DIR__ ) . DS . 'configs' . DS . 'application.default.php' ), $config['application'] );

        self::addIncludePath( $config['application']['libsRoot'] );

        // Store app namespace for autoloading
        self::instance()->namespaceToPath[$config['application']['namespace']] = $config['application']['root'];

        // Normalize modules config
        if( !isset( $config['modules'] ) ) $config['modules'] = array();

        // Normalize view config
        if( !isset( $config['views'] ) ) $config['views'] = array();

        self::instance()->config[$alias] = new \Erum\Config( $config, 1 );

        // preinit modules
        foreach( $config['modules'] as $moduleName => $moduleCfg )
        {
            \Erum\ModuleDirector::init( $moduleName );
        }

        // register views
        foreach( $config['views'] as $moduleName => $mimeTypes )
        {
            \Erum\ViewManager::register( $mimeTypes, $moduleName );
        }

    }

    /**
     * Returns config by alias, or currently set as default
     * 
     * @return \Erum\Config
     */
    public static function config( $alias = null )
    {
        if ( null === $alias )
            $alias = self::instance()->configAlias;

        if ( !isset( self::instance()->config[$alias] ) )
        {
            throw new Exception( 'Config with alias "' . $alias . '" was not been attached.' );
        }

        return self::instance()->config[$alias];
    }

    /**
     * Application run point.
     * 
     * @param string $configAlias
     */
    public static function run( $configAlias = null )
    {
        if ( null !== $configAlias )
            self::instance()->configAlias = $configAlias;

        try
        {
            $response = \Erum\Request::current()->execute();

            $response->sendHeaders();

            echo $response->body;
        }
        catch ( Exception $e )
        {
            // Debug output
            if ( self::config()->get( 'application' )->get( 'debug' ) )
            {
                if ( \Erum\Request::current()->async )
                {
                    print_r( array(
                        'Exception' => $e->getMessage(),
                        'File' => $e->getFile(),
                        'Line' => $e->getLine(),
                        'Trace' => $e->getTrace(),
                    ) );
                }
                else
                {
                    \Erum\Debug::coverPrint( $e->getTrace(), $e->getMessage() . ' on ' . $e->getFile() . ' in line ' . $e->getLine(), false );
                }

                exit( 1 );
            }
            // Do not allow infinite loops
            elseif ( \Erum\Request::current()->isInitial() )
            {
                exit( 1 );
            }
            else
            {
                \Erum\Router::redirect( '/notfound', false, 404 );
            }
        }
    }

    /**
     * Add path to php_include_path.
     * 
     * @param string $path
     */
    public static function addIncludePath( $path )
    {
        set_include_path( get_include_path() . PATH_SEPARATOR . $path );
    }

    /**
     * Handles autoloading for application, modules and core classes
     * 
     * @param string $className
     * @return boolean
     */
    public static function autoload( $className )
    {
        $includePath = '';

        $classChunks = array_filter( explode( '\\', trim( $className, '\\' ) ) );

        $className = array_pop( $classChunks );

        if ( sizeof( $classChunks ) )
        {
            $namespace = array_shift( $classChunks );

            if ( $namespace == 'Erum' )
            {
                array_unshift( $classChunks, __DIR__ );
            }
            elseif ( array_key_exists( $namespace, self::instance()->namespaceToPath ) )
            {
                array_unshift( $classChunks, self::instance()->namespaceToPath[$namespace] . DS . 'classes' );
            }
            elseif ( in_array( strtolower( $namespace ), \Erum\ModuleDirector::getRegistered() ) )
            {
                $modulesDir = self::instance()->config()->get( 'application' )->get( 'modulesRoot' );

                array_unshift( $classChunks, $modulesDir . DS . strtolower( $namespace ) . DS . 'classes' );
            }
            else
            {
                array_unshift( $classChunks, $namespace );
            }

            $includePath = strtolower( implode( DS, $classChunks ) );
        }

        try
        {
            include_once ( $includePath ? $includePath . DS : '' ) . $className . '.php';
        }
        catch( E_WARNING $i )
        {
            return false;
        }

        return true;
    }

}
