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
class Menu_api extends Diafan{
    public function edit(){
        $show_in_menu = array();
        if (! $this->diafan->is_new){
            $show_in_menu = DB::query_fetch_value("SELECT cat_id FROM {menu} WHERE module_name='%h' AND element_id=%d AND element_type='%s' AND trash='0' AND [act]='1'", $this->diafan->module, $this->diafan->id, $this->diafan->element_type(), "cat_id");
        }
        $rows = DB::query_fetch_all("SELECT id, [name] FROM {menu_category} WHERE trash='0' AND [act]='1' ORDER BY sort ASC");
        $value = array();
        foreach ($rows as $row){
            $value[] = array("value"=> $row["id"], "name" => $row["name"], "status" => in_array($row["id"], $show_in_menu));
        }
        $this->diafan->data["menu_cat_ids[]"] = array("type"=>"multiple","name"=>$this->diafan->variable_name(),"value" => $value);
    }
}