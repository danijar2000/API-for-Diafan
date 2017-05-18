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
class List_functions extends Diafan{	
    public function list_row(&$data, $id = 0, $first_level = true){
        $this->lang_act = $this->diafan->variable_multilang("act") ? _LANG : '';
        if($first_level)$this->prepare_paginator($id);
        $this->rows = $this->sql_query($id);
        $ids = array();
        foreach($this->rows as $row){
            $ids[] = $row["id"];
        }
        $this->rows_id = $ids;
        $i = 0;
        foreach ($this->rows as $row){
            $data[$i]['id'] = $row['id'];
            $data[$i]['site'] = $this->list_variable_site($row, array());
            $data[$i]['parent'] = $this->list_variable_cat($row, array());
            $data[$i]['date_period'] = $this->list_variable_date_period($row, array());
            foreach($this->diafan->_frame->variables_list as $name => $var){
                $func = 'list_variable_'.preg_replace('/[^a-z_]+/', '', $name);
                if(method_exists($this, $func)){
                    $data[$i][$name] = call_user_func_array(array(&$this, $func), array($row, $var));
                }
                if(! empty($var["type"]) && $var["type"] != 'none'){
                    switch($var["type"]){
                        case 'editor':
                        case 'text':
                            $data[$i][$name] = (! empty($row[$name]) ? $this->diafan->short_text($row[$name]) : '');
                            break;
                        case 'numtext':
                        case 'floattext':
                        case 'string':
                            $data[$i][$name] = (! empty($row[$name]) ? $row[$name] : '&nbsp;');
                            break;
                    }
                }
            }
            if ($this->diafan->variable_list('plus')){
                $this->list_row($data[$i]["sub"],$row["id"], false);
            }
            $i++;
        }
    }
    public function prepare_paginator($id){
        $count = DB::query_result("SELECT COUNT(DISTINCT e.id) FROM {".$this->diafan->_frame->table."} as e"
            .($this->diafan->config("element_multiple") && $this->diafan->cat_id ? " INNER JOIN {".$this->diafan->_frame->table."_category_rel} AS c ON e.id=c.element_id"
                ." AND e.id=c.element_id AND c.cat_id='".$this->cat_id."'" : '')
            ." WHERE 1=1".( $this->diafan->variable_list('plus') ? " AND e.parent_id='".$id."'" : '')
            .($this->diafan->config("element") && !$this->diafan->config("element_multiple") && $this->diafan->cat_id ? " AND e.cat_id='".$this->diafan->cat_id."'" : '')
            .($this->diafan->config("element_site") && $this->diafan->site_id && (! $this->diafan->config("element_multiple") || ! $this->diafan->cat_id) ? " and e.site_id='".$this->diafan->site_id."'" : '')
            .($this->diafan->_frame->where ? " ".$this->diafan->_frame->where : '')
            .($this->diafan->_frame->table == 'site' ? " AND e.id<>1" : '')
            .($this->diafan->variable_list('actions', 'trash') ? " AND e.trash='0'" : '')
            );
        if($count > $this->diafan->limit){
            $this->diafan->page = floor($count/$this->diafan->limit);
        }
    }
    public function prepare_variables(){
        if ($this->diafan->variable_list('plus') && $this->diafan->parent && empty($this->diafan->parent_parents)){
            $this->parent_parents = $this->diafan->get_parents($this->parent, $this->diafan->_frame->table);
            $this->parent_parents[] = $this->parent;
        }
        if($this->diafan->config('element')){
            $cats = DB::query_fetch_all(
                "SELECT id, "
                .($this->diafan->config('category_no_multilang') ? "name" : "[name]")
                .(! $this->diafan->config('category_flat') ? ", parent_id" : "")
                .($this->diafan->config("element_site") ? ", site_id" : "")
                ." FROM {".$this->diafan->_frame->table."_category} WHERE trash='0'"
                .($this->diafan->config("element_site") && $this->diafan->site_id ? " AND site_id='".$this->diafan->site_id."'" : "")
                ." ORDER BY "
                .($this->diafan->config("element_site") ? "sort" : "id")
                ." ASC LIMIT 1000"
                );
            if(count($cats)){
                $this->diafan->_frame->not_empty_categories = true;
            }
            if(count($cats) == 1000){
                $this->diafan->_frame->categories = array();
            }else{
                $this->diafan->_frame->categories = $cats;
            }
        }
        if($this->diafan->config('element_site')){
            $sites = DB::query_fetch_all("SELECT id, [name], parent_id FROM {site} WHERE trash='0' AND module_name='%s' ORDER BY sort ASC", $this->diafan->module);
            if(count($sites)){
                $this->diafan->_frame->not_empty_site = true;
            }
            foreach($sites as $site){
                $this->cache["parent_site"][$site["id"]] = $site["name"];
            }
            if(count($sites) == 1){
                if (DB::query_result("SELECT id FROM {%s} WHERE trash='0' AND site_id<>%d LIMIT 1", $this->diafan->_frame->table, $sites[0]["id"])){
                    $sites[] = 0;
                }else{
                    $this->diafan->site_id = $sites[0]["id"];
                }
            }
            $this->sites = $sites;
        }
    }
    public function list_variable_created($row, $var){
        if(! empty($var["type"]) && $var["type"] == 'datetime'){
            $text = date("d.m.Y H:i", $row["created"]);
        }else{
            $text = date("d.m.Y", $row["created"]);
        }
        return $text;
    }
    public function list_variable_image($row, $var){
        if(! isset($this->cache["prepare"]["image"])){
            $this->cache["prepare"]["image"] = DB::query_fetch_key("SELECT id, name, folder_num, element_id FROM {images}"
                ." WHERE module_name='%s' AND element_type='%s' AND element_id IN (%s)"
                ." AND trash='0' ORDER BY param_id DESC, sort DESC",
                $this->diafan->module,
                $this->diafan->element_type(),
                implode(",", $this->rows_id),
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
    public function list_variable_site($row, $var){
        if ($this->diafan->config("element_site") && $this->diafan->_frame->not_empty_site){
            if(count($this->sites) > 1){
                if(! empty($this->cache["parent_site"][$row["site_id"]])){
                    return $this->cache["parent_site"][$row["site_id"]];
                }
            }
        }
        return '';
    }
    public function list_variable_cat($row, $var){
        $text = '';
        if ($this->diafan->config("element_multiple")){
            if(! isset($this->cache["prepare"]["parent_cats"])){
                $this->cache["prepare"]["parent_cats"] = DB::query_fetch_key_array(
                    "SELECT s.[name], c.element_id FROM {".$this->diafan->module."_category_rel} as c"
                    ." INNER JOIN {".$this->diafan->module."_category} as s ON s.id=c.cat_id"
                    ." WHERE element_id IN (%s)",
                    implode(",", $this->rows_id),
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
        }elseif ($this->diafan->config("element") && ! $this->cat_id){
            if(! isset($this->cache["prepare"]["parent_cats"]) && ! empty($this->diafan->categories)){
                foreach($this->diafan->categories as $cat){
                    $this->cache["prepare"]["parent_cats"][$cat["id"]] = $cat["name"];
                }
            }
            return (! empty($this->cache["prepare"]["parent_cats"][$row["cat_id"]]) ? $this->cache["prepare"]["parent_cats"][$row["cat_id"]] : '');
        }
        return '';
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
    public function list_variable_actions($row, $var){
        $actions = array();
        //act
        if ($this->diafan->variable_list('actions', 'act') && $this->diafan->_users->roles('edit', $this->diafan->rewrite)
            && $this->diafan->_frame->check_action($row, 'act')){
                $actions[] = $row['act'] == '1' ? 'block' : 'unblock';//Togle act
        }
        //trash
        if ($this->diafan->variable_list('actions', 'trash')
            && $this->diafan->_users->roles('del', $this->diafan->rewrite)
            && $this->diafan->_frame->check_action($row, 'del')){
                $actions[] = 'trash';
        }
        //del
        if ($this->diafan->variable_list('actions', 'del')
            && $this->diafan->_users->roles('del', $this->diafan->rewrite)
            && $this->diafan->_frame->check_action($row, 'del')){
                $actions[] = 'delete';
        }
        return $actions;
    }
    private function sql_query($id){
        $this->diafan->_frame->where .= $this->diafan->is_variable("admin_id") && DB::query_result("SELECT only_self FROM {users_role} WHERE id=%d LIMIT 1", $this->diafan->_users->role_id)
        ? " AND (e.admin_id=0 OR e.admin_id=".$this->diafan->_users->id.")" : '';
        $fields = '';
        if($this->diafan->_frame->variables_list){
            foreach ($this->diafan->_frame->variables_list as $name => $var){
                if(empty($var["sql"]))continue;
                $fields .= ', e.'.($this->diafan->variable_multilang($name) ? '['.$name.']' : $name);
            }
        }
        $query = "SELECT e.id"
            .$fields
            .($this->diafan->variable_list('actions', 'act') ? ', e.act'.$this->lang_act.' AS act' : '' )
            .($this->diafan->variable_list('plus') ? ', e.parent_id, e.count_children' : '' )
            .($this->diafan->is_variable('date_period') ? ', e.date_start, e.date_finish' : '' )
            .($this->diafan->is_variable('readed') ? ', e.readed' : '' )
            .($this->diafan->is_variable('no_buy') ? ', e.no_buy' : '' )
            .($this->diafan->config("element_site") ? ', e.site_id' : '' )
            .($this->diafan->config("element") ? ', e.cat_id' : '' )
            ." FROM {".$this->diafan->_frame->table."} as e"
            .($this->diafan->config("element_multiple") && $this->diafan->cat_id ?
            " INNER JOIN {".$this->_frame->table."_category_rel} AS c ON e.id=c.element_id" .
            " AND c.cat_id='".$this->diafan->cat_id."'" : '' )
            . " WHERE 1=1"
            .($this->diafan->variable_list('plus') ? " AND e.parent_id='".$id."'" : '' )
            .($this->diafan->config("element") && ! $this->diafan->config("element_multiple") && $this->diafan->cat_id ?
            " AND e.cat_id='".$this->diafan->cat_id."'" : '' )
            .($this->diafan->config("element_site") && $this->diafan->site_id && (!$this->diafan->config("element_multiple") || ! $this->diafan->cat_id) ?
            " AND e.site_id='".$this->diafan->site_id."'" : '' ).( $this->diafan->_frame->where ? " ".$this->diafan->_frame->where : '' )
            .($this->diafan->variable_list('actions', 'trash') ? " AND e.trash='0'" : '' )
            ." GROUP BY e.id"
            .$this->sql_query_order()
            .' LIMIT '.$this->diafan->offset.', '.$this->diafan->limit;
        return DB::query_fetch_all($query);
    }
    public function sql_query_order(){
        return " ORDER BY "
            .($this->diafan->is_variable("prior") ? 'e.prior DESC, ' : '' )
            .($this->diafan->is_variable("readed") ? " e.readed ASC, " : '')
            .(($this->diafan->variable_list("created") && ! $this->diafan->variable_list('sort')) ? 'e.created DESC, ' : '' )
            .($this->diafan->variable_list('actions', 'act') ? 'e.act'.$this->lang_act.' DESC, ' : '' )
            .($this->diafan->variable_list('sort') ?
            ($this->diafan->variable_list('sort', 'desc') ? 'e.sort DESC, e.id DESC' : 'e.sort ASC, e.id ASC')
            : 'e.id DESC' );
    }
}
