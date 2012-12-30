<?php
    namespace Erum;

    /**
     * Response handler
     * Just container for all response properties
     *
     * @package Erum
     * @subpackage Core
     * @author Andrew Tereshko <andrew.tereshko@gmail.com>
     *
     * @property-read array $data
     * @property-read int   $status
     * @property-read array $headers
     * @property-read array $body
     *
     */
    class Response
    {
        protected static $statusList = array(
            100 => "Continue",
            101 => "Switching Protocols",
            200 => "OK",
            201 => "Created",
            202 => "Accepted",
            203 => "Non-Authoritative Information",
            204 => "No Content",
            205 => "Reset Content",
            206 => "Partial Content",
            300 => "Multiple Choices",
            301 => "Moved Permanently",
            302 => "Found",
            303 => "See Other",
            304 => "Not Modified",
            305 => "Use Proxy",
            307 => "Temporary Redirect",
            400 => "Bad Request",
            401 => "Unauthorized",
            402 => "Payment Required",
            403 => "Forbidden",
            404 => "Not Found",
            405 => "Method Not Allowed",
            406 => "Not Acceptable",
            407 => "Proxy Authentication Required",
            408 => "Request Timeout",
            409 => "Conflict",
            410 => "Gone",
            411 => "Length Required",
            412 => "Precondition Failed",
            413 => "Request Entity Too Large",
            414 => "Request-URI Too Long",
            415 => "Unsupported Media Type",
            416 => "Requested Range Not Satisfiable",
            417 => "Expectation Failed",
            500 => "Internal Server Error",
            501 => "Not Implemented",
            502 => "Bad Gateway",
            503 => "Service Unavailable",
            504 => "Gateway Timeout",
            505 => "HTTP Version Not Supported",
            509 => "Bandwidth Limit Exceeded",
        );

        /**
         * Response clean data
         *
         * @var array
         */
        private $data   = array();

        /**
         * HTTP status code
         *
         * @var int
         */
        private $status = 200;

        /**
         * HTTP headers
         *
         * @var array
         */
        private $headers = array();

        /**
         * HTTP cookies
         *
         * @var array
         */
        private $cookies = array();

        /**
         * @var string
         */
        private $body = '';

        private $protocolName = 'HTTP';

        private $protocolVersion = '1.1';

        /**
         * Create new response instance
         *
         * @param array $data
         * @param int $status
         * @return Response
         */
        public static function factory( array $data = array(), $status = 200, array $headers = array() )
        {
            $response = new self();

            $response
                ->setData( $data )
                ->setStatus( $status );

            return $response;
        }

        /**
         * Set response data variable
         *
         * @param string $var
         * @param mixed $value
         * @param bool $ignoreExisted
         * @throws \Exception
         */
        public function set( $var, $value, $ignoreExisted = true )
        {
            if( $ignoreExisted !== true && isset( $this->data[ $var ] ) )
            {
                throw new \Exception('Var ' . $var . ' was already set before');
            }

            $this->data[ $var ] = $value;

            return $this;
        }

        /**
         * Replace entire response data
         *
         * @param array $data
         * @return Response
         */
        public function setData( array $data )
        {
            $this->data = $data;

            return $this;
        }

        /**
         * Add HTTP header to response
         *
         * @param string $name
         * @param string $value
         * @param bool $appendIfExists
         * @return Response
         */
        public function addHeader( $name, $value, $appendIfExists = false )
        {
            if( $appendIfExists === true && isset( $this->headers[ $name ] ) )
            {
                $this->headers[ $name ] .= ',' . $value;
            }
            else
            {
                $this->headers[ $name ] = $value;
            }

            return $this;
        }

        /**
         * @param $name
         * @param $value
         * @param int $expire
         * @param null $host
         * @param null $secured
         *
         * @return \Erum\Response
         */
        public function addCookie( $name, $value, $expire = 0, $host = null, $secured = null )
        {
            return $this;
        }

        /**
         * Set response HTTP status
         *
         * @param int $code
         *
         * @return \Erum\Response
         */
        public function setStatus( $code )
        {
            $this->status = (int)$code;

            return $this;
        }

        /**
         * Set response body
         *
         * @param $content
         * @return \Erum\Response
         */
        public function setBody( $content )
        {
            $this->body = (string)$content;

            return $this;
        }

        /**
         * Send all defined headers (including cookies)
         *
         * @return \Erum\Response
         */
        public function sendHeaders()
        {
            // If headers was already sent - skip
            if( !headers_sent() )
            {
                // cookies
                foreach ( $this->cookies as $name => $data )
                {
                    //setcookie();
                }

                // first - status header
                header( $this->protocolName . '/' . $this->protocolVersion . ' ' . $this->status . ' ' . @self::$statusList[ $this->status ] );

                foreach( $this->headers as $name => $value )
                {
                    header( $name . ':' . $value );
                }
            }

            return $this;
        }

        /**
         * Getter for easing access to internal properties
         *
         * @param $var
         * @return mixed
         * @throws \Exception
         */
        public function __get( $var )
        {
            if ( isset( $this->$var ) )
            {
                return $this->$var;
            }
            else
            {
                throw new \Exception( 'Requested variable "' . $var . '" not exist!' );
            }
        }
    }
