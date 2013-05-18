<?php
namespace Erum;

class Debug
{

    protected $points;
    protected $pointsMap;
    protected $pointId;

    static protected $instance;

    static public function instance()
    {
        if ( is_null( self::$instance ) )
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    static public function coverPrint( $var, $message = '', $return = true )
    {
        // workaround for "nesting level too deep" when using var_export
        ob_start();
        var_dump($var);
        $varDump = ob_get_clean();

        $output = highlight_string( "<?php\n" . str_replace( "=> \n", '=>', trim( $varDump, "'" ) ), true );
        $output = str_ireplace( '<span style="color: #0000BB">&lt;?php<br /></span>', '', $output );

        // Oh here comes the shit!
        $output = '<div style="padding: 12px;background-color:#999;margin: 12px auto;width: 800px;border-radius: 5px;box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);">
			<h4 style="width:100%;margin:0 auto;color:#FFF;text-align:center;">' . $message . '
			<br/>
			<br/>
			<a href="javascript:void(0);" style="font-size:12px;color:white;text-decoration:none;" onclick="
			var el = document.getElementById(\'' . md5( print_r( $var, true ) ) . '\');
			if(el.style.display!=\'none\'){el.style.display=\'none\';}else{el.style.display=\'\';}">show / hide trace</a>
			</h4>
			<div id="' . md5( print_r( $var, true ) ) . '" style="padding:12px;border-radius:5px;background-color:#FCFCFC;margin-top: 8px; display: none;">
			<pre style="margin:0px;">'
                . $output . '</pre></div></div>';

        if ( $return )
        {
            return $output;
        }
        else
        {
            echo $output;
        }
    }

    public function enterPoint( $point )
    {
        if ( !isset( $this->pointsMap[$point] ) )
        {
            ++$this->pointId;

            $this->pointsMap[$point] = $this->pointId;

            $this->points[$this->pointId] = $this->getPointState();
            $this->points[$this->pointId]['name'] = $point;
            $this->points[$this->pointId]['executed'] = 1;
        }
        else
        {
            $this->points[$this->pointsMap[$point]]['executed']++;
        }
    }

    public function exitPoint( $point )
    {
        if ( $this->points[$this->pointsMap[$point]]['executed'] == 1 )
        {
            $this->points[$this->pointsMap[$point]]
                    = array_merge_recursive( $this->points[$this->pointsMap[$point]], $this->getPointState() );
        }
    }

    public function echoDump()
    {
        $object['childs'] = $this->points;

        foreach ( $object['childs'] as &$point )
        {
            $point['time'] = round( $point['time'][1] - $point['time'][0], 6 );
            $point['mem'] = $point['mem'][1] - $point['mem'][0];
            $point['files'] = $point['files'][1] - $point['files'][0];
        }

        echo self::coverPrint( $object, __METHOD__ );
    }

    private function __construct()
    {
        
    }

    private function getPointState()
    {
        $pointState = array(
            'time' => round( microtime( 1 ), 6 ),
            'mem' => memory_get_usage(),
            'files' => sizeof( get_included_files() ),
        );

        return $pointState;
    }

}