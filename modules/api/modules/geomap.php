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
class Geomap_api extends Diafan
{
	public function edit()
	{
		$element_type = $this->diafan->element_type();
		if (! $this->diafan->configmodules("geomap_".$element_type))
			return;

		$result["point"] = '';
		if(! $this->diafan->is_new)
		{
			$result["point"] = DB::query_result("SELECT point FROM {geomap} WHERE element_id=%d AND module_name='%s' AND element_type='%s' AND trash='0' LIMIT 1", $this->diafan->id, $this->diafan->_admin->module, $element_type);
		}
		$result["config"] = $this->diafan->configmodules("config", "geomap");
		if($result["config"])
		{
			$result["config"] = unserialize($result["config"]);
		}
		echo '<div class="unit" id="geomap">
			<div class="infofield">'.$this->diafan->variable_name().$this->diafan->help().'</div>';
			
			$backend = $this->diafan->configmodules("backend", "geomap");
			if($backend)
			{
				include(Custom::path('modules/geomap/backend/'.$backend.'/geomap.'.$backend.'.view.add.php'));
			}
			echo '
		</div>';
	}
}