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
class Images_api extends Diafan{
    public function value($element_id, $param_id){
		$element_type = $this->diafan->element_type();
		if($element_type == 'order'){
			$element_type = 'element';
		}
		if($param_id){
			$module_variations = unserialize(DB::query_result("SELECT config FROM {%s} WHERE id=%d", $this->diafan->_frame->table.'_param', $param_id));
			$module_name = $this->diafan->_frame->table;
		}else{
			$module_variations = unserialize($this->diafan->configmodules('images_variations_'.$this->diafan->element_type()));
			$module_name = $this->diafan->module;
		}
		$variation_folder = DB::query_result("SELECT folder FROM {images_variations} WHERE id=%d LIMIT 1", $module_variations[0]['id']);
		
		$rows = DB::query_fetch_all("SELECT id, name, [alt], [title], folder_num, module_name FROM {images}"
			." WHERE module_name='%s' AND element_type='%s' AND element_id=%d AND param_id=%d"
			." ORDER BY sort ASC",
			$module_name, $element_type,
			$element_id, $param_id
		);
		$count = count($rows);
		$imgs = array();
		foreach ($rows as $row){
			if (! file_exists(ABSOLUTE_PATH.USERFILES."/small/".($row["folder_num"] ? $row["folder_num"].'/' : '').$row["name"])){
				DB::query("DELETE FROM {images} WHERE id=%d", $row["id"]);
				continue;
			}
			//$imgs[] = empty($variation_folder) ? BASE_PATH.USERFILES.'/'.$module_name.'/'.$variation_folder.'/'.($row["folder_num"] ? $row["folder_num"].'/' : '').$row["name"] : '';
			$imgs[] = BASE_PATH.USERFILES."/small/".($row["folder_num"] ? $row["folder_num"].'/' : '').$row["name"].'?'.rand(0, 9999);
		}
		return $imgs;
    }
    public function edit(){
        $value = $this->value($this->diafan->id, 0);
        $this->edit_view(0, $this->diafan->variable_name(), 'images');
        
    }
    public function edit_param($param_id, $name, $help)
	{
		$this->edit_view($param_id, $name, $help);
	}
	private function edit_view($param_id, $name, $help)
	{
	   // $this->diafan->data["images[]"] = array("type"=>"images","name"=>$name,"value" => $value);
	}
}
