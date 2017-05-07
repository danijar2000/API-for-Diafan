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
class Show_admin_api extends Show_admin
{
    public function list_row($id = 0, $first_level = true){
        $this->lang_act = $this->diafan->variable_multilang("act") ? _LANG : '';
        if($first_level)$this->prepare_paginator($id);
        $this->diafan->rows = $this->diafan->sql_query($id);
        $this->cache["prepare"] = array();
        $ids = array();
        foreach($this->diafan->rows as $row){
            $ids[] = $row["id"];
        }
        
        $this->diafan->rows_id = $ids;
        $i = 0;
        $hidden = array('checkbox','sort','adapt','plus','site','parent','hr');
        foreach ($this->diafan->rows as $row){
            $this->data[$i]['id'] = $row['id'];
            $this->data[$i]['parent'] = $this->diafan->list_variable_cat($row, array());
            $this->data[$i]['date_period'] = $this->diafan->list_variable_date_period($row, array());
            foreach($this->diafan->variables_list as $name => $var){
                $func = 'list_variable_'.preg_replace('/[^a-z_]+/', '', $name);
                if(method_exists($this, $func) && !in_array(preg_replace('/[^a-z_]+/', '', $name), $hidden)){
                    $this->data[$i][$name] = call_user_func_array(array(&$this, $func), array($row, $var));
                }
                if(! empty($var["type"]) && $var["type"] != 'none'){
                    switch($var["type"]){
                        case 'editor':
                        case 'text':
                            $this->data[$i][$name] = (! empty($row[$name]) ? $this->diafan->short_text($row[$name]) : '');
                            break;
                        case 'numtext':
                        case 'floattext':
                        case 'string':
                            $this->data[$i][$name] = (! empty($row[$name]) ? $row[$name] : '&nbsp;');
                            break;
                    }
                }
            }
            if ($this->diafan->variable_list('plus')){
                $this->list_row($row["id"], false);
            }
            $i++;
        }
        if($first_level){
            $this->diafan->data = $this->data;
        }
    }
    public function prepare_paginator($id){
		$this->diafan->count = DB::query_result("SELECT COUNT(DISTINCT e.id) FROM {".$this->diafan->table."} as e"
			.$this->diafan->join
			.($this->diafan->config("element_multiple") && $this->diafan->_route->cat ? " INNER JOIN {".$this->diafan->table."_category_rel} AS c ON e.id=c.element_id"
			." AND e.id=c.element_id AND c.cat_id='".$this->diafan->_route->cat."'" : '')
			." WHERE 1=1".( $this->diafan->variable_list('plus') ? " AND e.parent_id='".$id."'" : '')
			.($this->diafan->config("element") && !$this->diafan->config("element_multiple") && $this->diafan->_route->cat ? " AND e.cat_id='".$this->diafan->_route->cat."'" : '')
			.($this->diafan->config("element_site") && $this->diafan->_route->site && (! $this->diafan->config("element_multiple") || ! $this->diafan->_route->cat) ? " and e.site_id='".$this->diafan->_route->site."'" : '')
			.($this->diafan->where ? " ".$this->diafan->where : '')
			.($this->diafan->table == 'site' ? " AND e.id<>1" : '')
			.($this->diafan->variable_list('actions', 'trash') ? " AND e.trash='0'" : '')
			);
		$this->polog = $this->diafan->offset;
		$this->diafan->nastr = $this->diafan->limit;
		return ;
    }
    public function list_variable_cat($row, $var){
        $text = '';
        if ($this->diafan->config("element_multiple")){
            if(! isset($this->cache["prepare"]["parent_cats"])){
                $this->cache["prepare"]["parent_cats"] = DB::query_fetch_key_array(
                    "SELECT s.[name], c.element_id FROM {".$this->diafan->module."_category_rel} as c"
                    ." INNER JOIN {".$this->diafan->module."_category} as s ON s.id=c.cat_id"
                    ." WHERE element_id IN (%s)",
                    implode(",", $this->diafan->rows_id),
                    "element_id"
                    );
            }
            $cats = array();
            if(! empty($this->cache["prepare"]["parent_cats"][$row["id"]])){
                foreach($this->cache["prepare"]["parent_cats"][$row["id"]] as $cat){
                    $cats[] = $cat["name"];
                }
            }
            return implode(', ', $cats);
        }elseif ($this->diafan->config("element") && ! $this->diafan->cat_id){
            if(! isset($this->cache["prepare"]["parent_cats"]) && ! empty($this->diafan->categories)){
                foreach($this->diafan->categories as $cat){
                    $this->cache["prepare"]["parent_cats"][$cat["id"]] = $cat["name"];
                }
            }
            return (! empty($this->cache["prepare"]["parent_cats"][$row["cat_id"]]) ? $this->cache["prepare"]["parent_cats"][$row["cat_id"]] : '');
        }
        return '';
    }
	/**
	 * Формирует дату в списке
	 *
	 * @param array $row информация о текущем элементе списка
	 * @param array $var текущее поле
	 * @return string
	 */
    public function list_variable_created($row, $var){
        if(! empty($var["type"]) && $var["type"] == 'datetime'){
            $text = date("d.m.Y H:i", $row["created"]);
        }else{
            $text = date("d.m.Y", $row["created"]);
        }
        return $text;
    }

	/**
	 * Формирует изображение в списке
	 *
	 * @param array $row информация о текущем элементе списка
	 * @param array $var текущее поле
	 * @return string
	 */
    public function list_variable_image($row, $var){
        if(! isset($this->cache["prepare"]["image"])){
            $this->cache["prepare"]["image"] = DB::query_fetch_key("SELECT id, name, folder_num, element_id FROM {images}"
                ." WHERE module_name='%s' AND element_type='%s' AND element_id IN (%s)"
                ." AND trash='0' ORDER BY param_id DESC, sort DESC",
                $this->diafan->module,
                $this->diafan->element_type(),
                implode(",", $this->diafan->rows_id),
                "element_id"
                );
        }
        if (! empty($this->cache["prepare"]["image"][$row["id"]])){
            $r = $this->cache["prepare"]["image"][$row["id"]];
            if(file_exists(ABSOLUTE_PATH.USERFILES."/small/".($r["folder_num"] ? $r["folder_num"].'/' : '').$r["name"])){
                return 'http'.(IS_HTTPS ? "s" : '').'://'.BASE_URL.'/'.USERFILES.'/small/'.($r["folder_num"] ? $r["folder_num"].'/' : '').$r["name"];
            }
        }
        return '';
    }

	/**
	 * Выводит название элемента в списке элементов
	 *
	 * @param array $row информация о текущем элементе списка
	 * @param array $var текущее поле
	 * @return string
	 */
    public function list_variable_name($row, $var){
        $name  = '';
        if(! empty($var["variable"])){
            $name = $row[$var["variable"]];
        }
        if(! empty($var["text"])){
            $name = sprintf($this->diafan->_($var["text"],false), $name);
        }
        if (! $name){
            if(! empty($row["name"])){
                $name = $row["name"];
            }else{
                $name = $row['id'];
            }
        }
        return $name;
    }
    public function list_variable_date_period($row, $var){
        if(! $this->diafan->is_variable("date_period")) return;
        $text = '';
        if(! empty($row["date_start"]) || ! empty($row["date_finish"])){
            if($this->diafan->variable("date_start") == 'date'){
                $time = mktime(0,0,0);
            }else{
                $time = time();
            }
            if(! empty($row["date_start"])){
                $text .= date('d.m.Y', $row["date_start"]);
            }
            if(! empty($row["date_finish"])){
                if(empty($row["date_start"])){
                    $text .= '< ';
                }else{
                    $text .= ' - ';
                }
                $text .= date('d.m.Y', $row["date_finish"]);
            }
        }
        return $text;
    }

	/**
	 * Выводит кнопки действий над элементом
	 *
	 * @param array $row информация о текущем элементе списка
	 * @param array $var текущее поле
	 * @return string
	 */
    public function list_variable_actions($row, $var){
        $actions = array();
        if ($this->diafan->variable_list('actions', 'act') 
            && $this->diafan->_users->roles('edit', $this->diafan->_admin->rewrite)
            && $this->diafan->check_action($row, 'act')){
                $actions[] = 'setAct';//Togle act
        }
        if ($this->diafan->variable_list('actions', 'trash')
            && $this->diafan->_users->roles('del', $this->diafan->_admin->rewrite)
            && $this->diafan->check_action($row, 'del')){
                $actions[] = 'setDelete';
        }
        if ($this->diafan->variable_list('actions', 'del')
            && $this->diafan->_users->roles('del', $this->diafan->admin->rewrite)
            && $this->diafan->check_action($row, 'del')){
                $actions[] = 'setDelete';
        }
        return $actions;
    }
    /**
     * Формирует SQL-запрос для списка элементов
     * Добавил только из-за того что private $polog
     * 
     * @param integer $id родитель
     * @return array
     */
    public function sql_query($id)
    {
        if($this->diafan->is_variable("menu") && ! isset($this->cache["menu_noact"]))
        {
            $this->cache["menu_noact"] = DB::query_fetch_value("SELECT id FROM {menu_category} WHERE [act]='0' AND trash='0'", "id");
        }
    
        $this->diafan->where .= $this->diafan->is_variable("admin_id") && DB::query_result("SELECT only_self FROM {users_role} WHERE id=%d LIMIT 1", $this->diafan->_users->role_id) ? " AND (e.admin_id=0 OR e.admin_id=".$this->diafan->_users->id.")" : '';
        $fields = '';
        if($this->diafan->variables_list)
        {
            foreach ($this->diafan->variables_list as $name => $var)
            {
                if(empty($var["sql"]))
                    continue;
    
                    $fields .= ', e.'.($this->diafan->variable_multilang($name) ? '['.$name.']' : $name);
            }
        }
    
        return DB::query_fetch_all("SELECT e.id"
            .$fields
            .($this->diafan->variable_list('actions', 'act') ? ', e.act'.$this->lang_act.' AS act' : '' )
            .($this->diafan->variable_list('plus') ? ', e.parent_id, e.count_children' : '' )
            .($this->diafan->is_variable('date_period') ? ', e.date_start, e.date_finish' : '' )
            .($this->diafan->is_variable('readed') ? ', e.readed' : '' )
            .($this->diafan->is_variable('no_buy') ? ', e.no_buy' : '' )
            .($this->diafan->config("element_site") ? ', e.site_id' : '' )
            .($this->diafan->config("element") ? ', e.cat_id' : '' )
            .($this->diafan->is_variable("menu") ? ", COUNT(DISTINCT m.element_id) AS menu" : '' )
            ." FROM {".$this->diafan->table."} as e"
            .$this->diafan->join
            .($this->diafan->config("element_multiple") && $this->diafan->_route->cat ?
                " INNER JOIN {".$this->diafan->table."_category_rel} AS c ON e.id=c.element_id" .
                " AND c.cat_id='".$this->diafan->_route->cat."'" : '' )
            .($this->diafan->is_variable("menu") ?
                " LEFT JOIN {menu} AS m ON e.id=m.element_id" .
                " AND m.trash='0' AND m.element_type='".$this->diafan->element_type()."' AND m.module_name='".$this->diafan->_admin->module."'"
                .($this->cache["menu_noact"] ? " AND m.cat_id NOT IN (".implode(",", $this->cache["menu_noact"]).")" : "")
                : '' )
            . " WHERE 1=1"
            .($this->diafan->variable_list('plus') ? " AND e.parent_id='".$id."'" : '' )
            .($this->diafan->config("element") && ! $this->diafan->config("element_multiple") && $this->diafan->_route->cat ?
                " AND e.cat_id='".$this->diafan->_route->cat."'" : '' )
            .($this->diafan->config("element_site") && $this->diafan->_route->site && (! $this->diafan->config("element_multiple") || ! $this->diafan->_route->cat) ?
                " AND e.site_id='".$this->diafan->_route->site."'" : '' ).( $this->diafan->where ? " ".$this->diafan->where : '' )
            .($this->diafan->variable_list('actions', 'trash') ? " AND e.trash='0'" : '' )
            ." GROUP BY e.id"
            .$this->diafan->sql_query_order()
            .' LIMIT '.$this->polog.', '.$this->diafan->nastr);
    }
}