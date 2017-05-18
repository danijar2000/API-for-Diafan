<?php
if (! defined('DIAFAN')){
    $path = __FILE__; $i = 0;
    while(! file_exists($path.'/includes/404.php')){
        if($i == 10) exit; $i++;
        $path = dirname($path);
    }
    include $path.'/includes/404.php';
}
class Test_functions extends Diafan{
    public function test(){
        $this->diafan->map();
    }
}