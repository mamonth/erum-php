<?php
namespace Erum;

/**
 * CLI Process
 *
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
class CLIProcess
{
    /**
     * @var CLIProcess
     */
    protected static $current;

    protected $lockFile;

    protected $pid;

    public static function current()
    {
        if( !self::$current )
        {
            self::$current = new self( getmypid() );
        }

        return self::$current;
    }

    public function __construct( $pid )
    {
        $this->pid = $pid;

        register_shutdown_function( array( $this, 'onShutdown') );
    }

    public function getPid()
    {
        return $this->pid;
    }

    public function setLockFile( $fileName )
    {
        $this->lockFile = $fileName;

        return $this;
    }

    public function isLocked()
    {
        if( !$this->lockFile )
            throw new Exception('Lock file path must be provided.');

        if( file_exists( $this->lockFile ) )
        {
            $pidLocked  = trim( file_get_contents( $this->lockFile ) );
            $pidList    = explode( "\n", trim( `ps -e | awk '{print $1}'` ) );

            // If PID is still active, return true
            if( in_array( $pidLocked, $pidList ) )  return true;

            // Lock-file is stale, so kill it.  Then move on to re-creating it.
            unlink( $this->lockFile );
        }

        file_put_contents( $this->lockFile, $this->pid . PHP_EOL );
        return false;
    }

    public function onShutdown()
    {
        if( $this->lockFile && file_exists( $this->lockFile ) )
        {
            @unlink( $this->lockFile );
        }
    }
}
