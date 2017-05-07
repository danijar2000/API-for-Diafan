<?php
if (! defined('DIAFAN'))
{
	$path = __FILE__; $i = 0;
	while(! file_exists($path.'/includes/404.php'))
	{
		if($i == 10) exit; $i++;
		$path = dirname($path);
	}
	include $path.'/includes/404.php';
}
class API extends Controller{
    public function init(){
        if(isset($_REQUEST["action"])){
            $this->model->chek();
            switch($_REQUEST["action"]){
                case 'getInfo':     $this->model->getInfo();        break;
                case 'getMenus':    $this->model->getMenus();       break;
                case 'getTopMenus': $this->model->getTopMenus();    break;
                case 'getLanguages':$this->model->getLanguages();   break;
                
                case 'getList':     $this->model->getList();        break;
                case 'getEdit':     $this->model->getEdit();        break;
                case 'setSave':     $this->model->setSave();        break;
                
                case 'setAct':      $this->model->setAct();         break;
                case 'setDelete':   $this->model->setDelete();      break;
                
                case 'setAuth':     $this->model->setAuth();        break;
                case 'setLogout':   $this->model->setLogout();      break;
            }
            $this->model->out();
        }
    }
}