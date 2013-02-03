<?php
namespace Erum;

/**
 * CLI Message
 * 
 * Class supose to manage message output in command line mode.
 * Support write messages with custom flag (status) to log file.
 * 
 * Usage example:
 * 
 * $debug = true;
 * 
 * $msg = new CLIMessage( $debug );
 * 
 * $msg->setLog( 'errorLog' , CLIMessage::L_ERROR );
 * 
 * // Message will display only
 * $msg->message( 'simple message' );
 * 
 * // Message will be displayed and stored in 'errorLog' file.
 * $msg->message( 'error mesage' ) 
 *
 * 
 * @author Andrew Tereshko <andrew.tereshko@gmail.com>
 */
class CLIMessage
{
    const L_MESSAGE = 0;
    const L_NOTICE = 1;
    const L_WARNING = 2;
    const L_ERROR = 3;

    /**
     *
     * @var boolean
     */
    private $display;

    private $displayLevel;

    private $logFile;

    private $logLevel;

    private $lastMessage;

    private $displayDublicated;

    /**
     * constructor
     *
     * @param boolean $display
     */
    public function __construct( $display = false, $level = self::L_MESSAGE, $displayDublicated = false )
    {
        $this->display = $display;
        $this->displayLevel = $level;
        $this->displayDublicated = $displayDublicated ? true : false;
    }

    /**
     * Sets log file destination and message level
     *
     * @param string $file
     * @param integer $messageLevel
     */
    public function setLog( $file, $level = self::L_ERROR )
    {
        if( !file_exists( dirname( $file ) ) )
            throw new Exception( 'Log file directory is not available!' );

        if( !is_writable( dirname( $file ) ) )
            throw new Exception( 'Log file directory is not writable!' );

        if( file_exists( $file ) && !is_writable( $file ) )
            throw new Exception( 'Log file already exists and not writable!' );

        $this->logFile = $file;
        $this->logLevel = $level;
    }

    public function message( $message, $level = self::L_NOTICE )
    {
        if( !$this->displayDublicated && trim( $message ) == $this->lastMessage ) return;
        
        $this->lastMessage = trim( $message );

        if( $this->display && $level >= $this->displayLevel )
        {
            $cmessg = "";

            switch ( $level )
            {
                case self::L_NOTICE: $cmessg .= "\x1b[32m"; break;
                case self::L_WARNING: $cmessg .= "\x1b[33m"; break;
                case self::L_ERROR: $cmessg .= "\x1b[31m"; break;
            }

            $cmessg .= $message . "\x1b[0m\n";

            echo $cmessg;
        }

        if( $this->logFile && $level >= $this->logLevel )
        {
            $lmessg = date( 'Y-m-d H:i:s', time() ) . " ";

            switch ( $level )
            {
                case self::L_NOTICE: $lmessg .= "[NOTICE]"; break;
                case self::L_WARNING: $lmessg .= "[WARNING]"; break;
                case self::L_ERROR: $lmessg .= "[ERROR]"; break;
            }

            $lmessg .= ' ' . $message . "\n";
            
            error_log( $lmessg , 3, $this->logFile );
        }
    }

    public function __invoke( $message, $level = self::L_NOTICE )
    {
        $this->message( $message, $level );
    }
}
