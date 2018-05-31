<?php
function smarty_modifier_time_elapsed($ptime)
{
    $etime = time() - strtotime($ptime);
    
    if ($etime < 1) {
        return sprintf(Registry()->localizer->get('TIME_INTERVAL_STRING'),1,Registry()->localizer->get('TIME_INTERVAL','second'));
    }
    
    $a = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                30 * 24 * 60 * 60       =>  'month',
                24 * 60 * 60            =>  'day',
                60 * 60                 =>  'hour',
                60                      =>  'minute',
                1                       =>  'second'
                );
    
    foreach ($a as $secs => $str) {
        $d = $etime / $secs;
        if ($d >= 1) {
            $r = round($d);
            return sprintf(Registry()->localizer->get('TIME_INTERVAL_STRING'),$r,Registry()->localizer->get('TIME_INTERVAL',$str . ($r > 1 ? 's' : '')));
        }
    }
}

?>