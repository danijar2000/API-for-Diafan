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
class Del_functions extends Diafan{	
    public function del($ids){
        foreach($ids as $id){
            $id = intval($id);
            if($id){
                $del_ids[] = $id;
            }
        }
        if(! empty($del_ids)){
            $element_type = $this->diafan->element_type();
            if($element_type == 'param'){
                $table = $this->diafan->module.'_param';
            }elseif($element_type == 'cat'){
                $table = $this->_frame->table;
            }else{
                $table = $this->diafan->table_element_type($this->module, $element_type);
            }
             
            foreach($del_ids as $del_id){
                $this->current_trash = 0;
                $this->current_trash = $this->del_or_trash($table, $del_id);
                $this->del_rows(array($del_id), $table, $this->module, $element_type);
            }
            if (! empty($this->cache["parent"])){
                foreach($this->cache["parent"] as $table){
                    $rows = DB::query_fetch_all("SELECT t.id, t.count_children, COUNT(p.id) as cnt FROM {".$table."} AS t LEFT JOIN {".$table."_parents}  AS p ON p.parent_id=t.id AND p.trash='0' WHERE t.trash='0' GROUP BY t.id");
                    foreach ($rows as $row){
                        if($row["count_children"] != $row["cnt"]){
                            DB::query("UPDATE {".$table."} SET count_children=%d WHERE id=%d", $row["cnt"], $row["id"]);
                        }
                    }
                }
            }
            if ($_POST["action"] == "trash"){
                $rows = DB::query_fetch_all("SELECT t.id, t.count_children, COUNT(p.id) as cnt FROM {trash} AS t INNER JOIN {trash_parents}  AS p ON p.parent_id=t.id GROUP BY t.id");
                foreach ($rows as $row){
                    if($row["count_children"] != $row["cnt"]){
                        DB::query("UPDATE {trash} SET count_children=%d WHERE id=%d", $row["cnt"], $row["id"]);
                    }
                }
            }
        }
    }
    public function del_or_trash($table, $del_id)
    {
        if ($_POST["action"] == "trash")
        {
            DB::query("UPDATE {".$table."} SET trash='1' WHERE id=%d", $del_id);
            $id = DB::query("INSERT INTO {trash} (table_name, module_name, element_id, created, parent_id, user_id) VALUES ('%s', '%s', '%d', '%d', '%d', '%d')", $table, $this->diafan->_admin->module, $del_id, time(), $this->current_trash, $this->diafan->_users->id);
            DB::query("INSERT INTO {trash_parents} (`element_id`, `parent_id`) VALUES (%d, %d)", $id, $this->current_trash);
            return $id;
        }
        else
        {
            $trash_id = DB::query_result("SELECT id FROM {trash} WHERE element_id=%d AND table_name='%s' LIMIT 1", $del_id, $table);
            DB::query("DELETE FROM {trash} WHERE id=%d", $trash_id);
            DB::query("DELETE FROM {trash_parents} WHERE parent_id=%d OR element_id=%d", $trash_id, $trash_id);
            DB::query("DELETE FROM {".$table."} WHERE id=%d", $del_id);
        }
        return true;
    }
    public function del_rows($ids, $table, $module_name, $element_type = 0)
    {
        if ($this->diafan->element_type() != $element_type)
        {
            if(! isset($this->cache["class_".$element_type]))
            {
                $e_type = '';
                if($element_type == 'cat')
                {
                    $e_type = 'category';
                }
                elseif($element_type == 'import')
                {
                    $e_type = 'importexport.element';
                }
                elseif($element_type == 'import_category')
                {
                    $e_type = 'importexport.category';
                }
                elseif($element_type != 'element')
                {
                    $e_type = $element_type;
                }
                Custom::inc('modules/'.$module_name.'/admin/'.$module_name.'.admin'.($e_type ? '.'.$e_type : '').'.php');
                $class = ucfirst($module_name).'_admin'.($e_type ? '_'.str_replace('.', '_', $e_type) : '');
                $this->cache["class_".$element_type] = new $class($this->diafan);
                $this->cache["class_".$element_type]->diafan->_frame = $this->cache["class_".$element_type];
            }
            $class = &$this->cache["class_".$element_type];
        }
        else
        {
            $class = &$this->diafan;
        }
    
        if ($class->variable_list("plus"))
        {
            $chs = DB::query_fetch_value("SELECT DISTINCT(element_id) FROM {%s_parents} WHERE parent_id IN (%s) AND element_id NOT IN (%s)", $table,  implode(",", $ids),  implode(",", $ids), "element_id");
            $ids = array_merge($ids, $chs);
            $this->del_or_trash_where($table, "id IN (".implode(",", $chs).")");
            $this->del_or_trash_where($table."_parents", "element_id  IN (".implode(",", $ids).")");
            if (empty($this->cache["parent"]) || ! in_array($table, $this->cache["parent"]))
            {
                $this->cache["parent"][] = $table;
            }
        }
        $this->include_modules('delete', array($ids, $module_name, $element_type));
    
        if($element_type != 'param')
        {
            $this->del_or_trash_where("rewrite", "element_id IN (".implode(",", $ids).") AND module_name='".$module_name."' AND element_type='".$element_type."'");
    
            $this->del_or_trash_where("redirect", "element_id IN (".implode(",", $ids).") AND module_name='".$module_name."' AND element_type='".$element_type."'");
    
            $this->del_or_trash_where("access", "element_id IN (".implode(",", $ids).") AND module_name='".$module_name."' AND element_type='".$element_type."'");
    
            $this->del_or_trash_where("site_dynamic_element", "element_id IN (".implode(",", $ids).") AND module_name='".$module_name."' AND element_type='".$element_type."'");
        }
        // функция удаления, описанная в модуле
        if(is_callable(array(&$class, 'delete')))
        {
            call_user_func_array(array(&$class, 'delete'), array($ids));
        }
    
        if($class->config("category"))
        {
            $cat_element_type = $element_type == 'cat' ? 'element' : str_replace('_category', '', $element_type);
            $cat_elements = DB::query_fetch_value("SELECT id FROM {%s} WHERE cat_id IN (".implode(",", $ids).")", str_replace('_category', '', $table), "id");
            if($cat_elements)
            {
                $this->del_or_trash_where(str_replace('_category', '', $table), "id IN (".implode(",", $cat_elements).")");
                $this->del_rows($cat_elements, str_replace('_category', '', $table), $module_name, $cat_element_type);
                if ($class->config("category_rel"))
                {
                    $this->del_or_trash_where($table."_category_rel", "cat_id IN (".implode(",", $ids).")");
                }
            }
        }
    
        if ($class->config("element_multiple"))
        {
            $this->del_or_trash_where($table."_category_rel", "element_id IN (".implode(",", $ids).") AND trash='0'");
        }
    
        // удаляет значения списка для полей конструктора
        if($element_type == 'param')
        {
            $this->del_or_trash_where($table."_element", "param_id IN (".implode(",", $ids).")");
            $select_ids = DB::query_fetch_value("SELECT id FROM {%s_select} WHERE param_id IN (".implode(",", $ids).")", $table, "id");
            if($select_ids)
            {
                $this->del_or_trash_where("rewrite", "element_id IN ('".implode(',', $select_ids).") AND module_name='".$module_name."' AND element_type='param'");
            }
    
            $this->del_or_trash_where($table."_select",  "param_id IN (".implode(",", $ids).")");
            if ($class->is_variable("category"))
            {
                $this->del_or_trash_where($table."_category_rel", "element_id IN (".implode(",", $ids).")");
            }
        }
    }
    public function include_modules($method, $args){
        if (! isset($this->cache["include_modules"])){
            $this->cache["include_modules"] = array();
            foreach ($this->diafan->installed_modules as $module){
                if (Custom::exists('modules/'.$module.'/admin/'.$module.'.admin.inc.php')){
                    Custom::inc('modules/'.$module.'/admin/'.$module.'.admin.inc.php');
                    $class = ucfirst($module).'_admin_inc';
                    if (method_exists($class, $method)){
                        $this->cache["include_modules"][] = new $class($this);
                    }
                }
            }
        }
        foreach ($this->cache["include_modules"] as &$obj){
            call_user_func_array (array(&$obj, $method), $args);
        }
    }
    public function del_or_trash_where($table, $del_where){
        if ($_GET["action"] == "trash"){
            $rows = DB::query_fetch_all("SELECT * FROM {".$table."} WHERE ".$del_where." AND trash='0'");
            foreach ($rows as $row){
                $id = DB::query("INSERT INTO {trash} (table_name, module_name, element_id, created, parent_id, user_id) VALUES ('%s', '%s', '%d', '%d', '%d', '%d')", $table, $table, $row["id"], time(), $this->current_trash, $this->diafan->_users->id);
                DB::query("INSERT INTO {trash_parents} (`element_id`, `parent_id`) VALUES (%d, %d)", $id, $this->current_trash);
            }
            DB::query("UPDATE {".$table."} SET trash='1' WHERE ".$del_where);
    
        }else{
            $del_ids = DB::query_result("SELECT GROUP_CONCAT(id SEPARATOR ',') FROM {".$table."} WHERE ".$del_where);
            if($del_ids){
                $trash_ids = DB::query_fetch_value("SELECT id FROM {trash} WHERE element_id IN (%s) AND table=%h", $del_ids, $table, "id");
                DB::query("DELETE FROM {trash} WHERE id IN (%s)", implode(",", $trash_ids));
                DB::query("DELETE FROM {trash_parents} WHERE parent_id IN (%s) OR element_id IN (%s)", implode(",", $trash_ids), implode(",", $trash_ids));
                DB::query("DELETE FROM {".$table."} WHERE ".$del_where);
            }
        }
    }
}
