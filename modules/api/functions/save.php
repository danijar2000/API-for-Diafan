<?php
if (! defined('DIAFAN')){
	$path = __FILE__; $i = 0;
	while(! file_exists($path.'/includes/404.php')){
		if($i == 10) exit; $i++;
		$path = dirname($path);
	}
	include $path.'/includes/404.php';
}
class Save_functions extends Diafan{
    public $query;
    public $value;
    public $save_parent_id;
    public $save_cat_id;
    public $save_sort;
    public $is_save_rewrite = false;
    public $is_new = false;
    public $table = '';
    private $done = false;
    public function save(){
        $this->table = $this->table;
        if(empty($_GET["id"]) && ! $this->diafan->config('only_edit') && ! $this->diafan->config('config')){
            $this->save_new();
            $this->is_new = true;
        }else{
            $this->id = $this->diafan->filter($_GET, "int", "id");
            $this->diafan->values("id");
        }
        
        foreach ($this->diafan->_frame->variables as $title => $variable_table){
            $this->prepare_new_values($variable_table);
        }
        if ($this->diafan->config("config")){
            for ($q = 0; $q < count($this->value); $q++){
                $this->value[$q] = str_replace("\n", '', $this->value[$q]);
            }
            DB::query("DELETE FROM {config} WHERE module_name='%s' AND site_id=%d AND (lang_id="._LANG." OR lang_id=0)", $this->diafan->module, $this->diafan->site_id);
            for ($q = 0; $q < count($this->value); $q++){
                list( $name, $mask ) = explode('=', $this->query[$q]);
                $name = str_replace('`', '', $name);
                if (! $this->diafan->site_id && $this->value[$q] || $this->diafan->site_id &&
                    DB::query_result("SELECT value FROM {config} WHERE module_name='%s' AND site_id=0 AND name='%h'".($this->variable_multilang($name) ? " AND lang_id='"._LANG."'" : '' )." LIMIT 1", $this->module, $name) != $this->value[$q]){
                    DB::query("INSERT INTO {config} (name, module_name, value, site_id, lang_id) 
                        VALUES ('%h', '%h', ".$mask.", '%d', '%d')", $name, $this->diafan->module, $this->value[$q], $this->diafan->site_id, ($this->variable_multilang($name) ? _LANG : 0));
                }
            }
            $this->done = true;
            $this->diafan->_cache->delete("configmodules", "site");
        }else{
            if (! DB::query("UPDATE {".$this->table."} SET ".implode(', ', $this->query)." WHERE id = %d", array_merge($this->value, array($this->id)))){
                return;
            }
        }
    }
    public function save_new(){
        $def = array ();
        if ($this->variable_list('plus')){
            $def['parent_id'] = "'".$this->parent_id."'";
        }
        if ($this->config("element_site")){
            $def['site_id'] = "'".$this->diafan->filter($_POST, "int", "site_id")."'";
        }
        if ($this->config("element")){
            $def['cat_id'] = "'".$this->diafan->filter($_POST, "int", "cat_id")."'";
        }
        $this->id = DB::query("INSERT INTO {".$this->_frame->table."} (".implode(',', array_keys($def)).") VALUES (".implode(',', $def).")");
        if (! $this->id){
            $this->error_code = "not_save";
            $this->error = $this->diafan->_('Не удалось добавить новый элемент в базу данных. Возможно, таблица '.DB_PREFIX.$this->table.' имеет неправильную структуру.', false);
            $this->diafan->outData();
        }
    }
    private function prepare_new_values($variable_table){
        foreach ($variable_table as $key => $type_value){
            if(is_array($type_value)){
                if(! empty( $type_value["disabled"])){continue;}
                if(! empty( $type_value["no_save"])){continue;}
                $type_value = $type_value["type"];
            }else{
                //$type_value = $type_value;
            }
            $name = "`".$key.( ! $this->diafan->config("config") && $this->diafan->variable_multilang($key) ? _LANG : '' )."`";
            $func = 'save'. ( $this->diafan->config("config") ? '_config' : '' ).'_variable_'.str_replace('-', '_', $key);
            if(method_exists($this, $func)){
                $this->$func();
            }else{
                if ($type_value == 'module'){
                    /*if (in_array($key, $this->diafan->installed_modules)
                     && Custom::exists('modules/'.$key.'/admin/'.$key.'.admin.inc.php'))
                    {
                    Custom::inc('modules/'.$key.'/admin/'.$key.'.admin.inc.php');
                    $func = 'save'.( $this->diafan->config("config") ? '_config' : '' );
                    $class = ucfirst($key).'_admin_inc';
                    if (method_exists($class, $func))
                    {
                    $module_class = new $class($this->diafan);
                    call_user_func_array (array(&$module_class, $func), array());
                    }
                    }*/
                }elseif ($type_value == 'date' || $type_value == 'datetime'){
                    $this->query[] = $name."='%d'";
                    $this->value[] = $this->diafan->unixdate($_POST[$key]);
                }elseif ($type_value == 'hr' || $type_value == 'title'){
                    continue;
                }elseif ($type_value == 'floattext'){
                    $this->value[] = str_replace(',', '.', ! empty( $_POST[$key] ) ? $_POST[$key] : 0);
                    $this->query[] = $name."='%f'";
                }elseif($type_value == 'editor'){
                    $this->value[] = $this->save_field_editor($key);
                    $this->query[] = $name."='%s'";
                }else{
                    $this->value[] = ! empty( $_POST[$key] ) ? $_POST[$key] : '';
                    if ($type_value == 'text' || $type_value == 'select' || $type_value == 'email'){
                        $this->query[] = $name."='%h'";
                    }elseif ($type_value == 'checkbox' || $type_value == 'numtext'){
                        $this->query[] = $name."='%d'";
                    }else{//textarea,none,function,password...
                        $this->query[] = $name."='%s'";
                    }
                }
            }
        }
    }
    public function save_field_editor($key){
        $text = ! empty($_POST[$key]) ? $_POST[$key] : '';
        // типограф
        if (! empty($_POST[$key."_typograf"]))
        {
            include_once (ABSOLUTE_PATH."plugins/remotetypograf.php");
    
            $remoteTypograf = new RemoteTypograf();
    
            $remoteTypograf->htmlEntities();
            $remoteTypograf->br(false);
            $remoteTypograf->p(true);
            $remoteTypograf->nobr(3);
            $remoteTypograf->quotA('laquo raquo');
            $remoteTypograf->quotB('bdquo ldquo');
    
            $text = $remoteTypograf->processText($text);
        }
        // подключение/отключение визуального редактора
        if($this->diafan->_users->htmleditor)
        {
            if($this->diafan->is_new)
            {
                $hide_htmleditor = array();
            }
            else
            {
                $hide_htmleditor = explode(",", $this->diafan->configmodules("hide_".$this->table."_".$this->diafan->id, "htmleditor"));
            }
            if(! empty($_POST[$key."_htmleditor"]) && ! in_array($key, $hide_htmleditor))
            {
                $hide_htmleditor[] = $key;
                $hide_htmleditor = array_diff($hide_htmleditor, array("", 0));
                $this->diafan->configmodules("hide_".$this->table."_".$this->diafan->id, "htmleditor", false, false, implode(",", $hide_htmleditor));
            }
            elseif(empty($_POST[$key."_htmleditor"]) && in_array($key, $hide_htmleditor))
            {
                $hide_htmleditor = array_diff($hide_htmleditor, array("", 0, $key));
                $this->diafan->configmodules("hide_".$this->table."_".$this->diafan->id, "htmleditor", false, false, implode(",", $hide_htmleditor));
            }
        }
        // ссылки заменяем на id
        $text = $this->diafan->_route->replace_link_to_id($text);
    
        // копирование внешних изображений
        if ($this->diafan->_users->copy_files && ! IS_DEMO)
        {
            if(preg_match_all('/\<img[^\>+]src=\"http*:\/\/([^\"]+)\"/', $text, $m))
            {
                foreach ($m[1] as $i => $src)
                {
                    $src = 'http://'.$src;
                    $url = parse_url($src);
                    if ($url["host"] != getenv("HTTP_HOST"))
                    {
                        $extension = substr(strrchr($src, '.'), 1);
                        $name = md5($src).'.'.$extension;
                        File::copy_file($src, USERFILES.'/upload/'.$name);
                        $text = str_replace('src="'.$src.'"', 'src="BASE_PATH'.USERFILES.'/upload/'.$name.'"', $text);
                    }
                }
            }
        }
        return $text;
    }
    public function save_rewrite_redirect(){
        $site_id = $this->get_site_id();
        if ($site_id != $this->diafan->values("site_id")){
            if ($this->diafan->config("category")){
                $child = $this->get_children($this->id, $this->table);
                DB::query("UPDATE {".str_replace("_category", "", $this->_frame->table)."}"
                    ." SET site_id=%d WHERE cat_id IN (%s)",
                    $site_id, implode(",", $child));
            }
        }
        if (! $_POST["rewrite_redirect"]){
            DB::query("DELETE FROM {redirect} WHERE module_name='%s' AND element_id=%d AND element_type='%s'",
                $this->diafan->module, $this->id, $this->diafan->element_type());
            return;
        }
        if (! $this->is_new){
            $row = DB::query_fetch_array("SELECT * FROM {redirect} WHERE module_name='%s' AND element_id=%d AND element_type='%s' LIMIT 1",
                $this->diafan->module, $this->id, $this->diafan->element_type());
        }
        if (! empty($row["id"])){
            if($row["redirect"] == $_POST["rewrite_redirect"] && $row["code"] == $_POST["rewrite_code"])
                return;
                DB::query("UPDATE {redirect} SET redirect='%s', code=%d WHERE id=%d",
                    $_POST["rewrite_redirect"], $_POST["rewrite_code"], $row["id"]);
        }else{
            DB::query("INSERT INTO {redirect} (redirect, code, module_name, element_id, element_type)"
                ." VALUES ('%s', %d, '%s', %d, '%s')",
                $_POST["rewrite_redirect"], $_POST["rewrite_code"], $this->diafan->module,
                $this->id, $this->diafan->element_type());
        }
    }
	public function save_variable_cat_id(){
		$this->diafan->save_cat_id = $this->diafan->filter($_POST, 'int', 'cat_id');
		$this->set_query("cat_id=%d");
		$this->set_value($this->diafan->save_cat_id);
		if ($this->diafan->config("element_multiple"))
		{
			DB::query("DELETE FROM {%s_category_rel} WHERE element_id=%d", $this->diafan->module, $this->diafan->id);
			DB::query("INSERT INTO {%s_category_rel} (element_id, cat_id) VALUES('%d', '%d')", $this->diafan->module, $this->diafan->id, $this->diafan->save_cat_id);

			if (! empty( $_POST["cat_ids"] ) && ! empty($_POST["user_additional_cat_id"]) && is_array($_POST["cat_ids"]))
			{
				foreach ($_POST["cat_ids"] as $cat_id)
				{
					if($cat_id != 'all' && $cat_id != $_POST["cat_id"])
					{
						DB::query("INSERT INTO {%s_category_rel} (element_id, cat_id) VALUES('%d', '%d')", $this->diafan->module, $this->diafan->id, $cat_id);
					}
				}
			}
		}elseif ($this->diafan->variable_list('plus') && ! $this->diafan->is_new && DB::query_result("SELECT cat_id FROM {%h} WHERE id=%d LIMIT 1", $this->table, $this->id) != $this->diafan->save_cat_id){
			$children = $this->diafan->get_children($this->diafan->id, $this->table);
			if ($children){
				DB::query("UPDATE {%h} SET cat_id=%d WHERE id IN (%h)", $this->table, $this->diafan->save_cat_id, implode(",", $children));
			}
		}
	}
	public function save_variable_site_id(){
		$site_id = $this->diafan->get_site_id();
		$this->set_query("site_id='%d'");
		$this->set_value($site_id);
		if ($this->diafan->variable_list('plus') && ! $this->diafan->is_new){
			$children = $this->diafan->get_children($this->diafan->id, $this->table);
			if($children){
				DB::query("UPDATE {".$this->table."} SET site_id=%d WHERE id IN (%h)", $site_id, implode(",", $children));
			}
		}
	}
	public function get_site_id()
	{
		if(! isset($this->cache["site_id"]))
		{
			if($this->table == 'site')
			{
				$this->cache["site_id"] = $this->diafan->id;
			}
			else
			{
				$this->cache["site_id"] = $this->diafan->filter($_POST, 'int', 'site_id');
				if(! $this->cache["site_id"])
				{
					if(! $this->diafan->config("element_site"))
					{
						$this->cache["site_id"] = DB::query_result("SELECT id FROM {site} WHERE module_name='%s' AND trash='0' AND [act]='1' LIMIT 1", $this->diafan->module);
					}
					else
					{
						if ($this->diafan->config("element"))
						{
							$this->cache["site_id"] = DB::query_result("SELECT site_id FROM {".$this->table."_category} WHERE id=%d LIMIT 1", $_POST["cat_id"]);
						}
						elseif ($this->diafan->config("category"))
						{
							if ($this->diafan->variable_list('plus') && $_POST["parent_id"])
							{
								$this->cache["site_id"] = DB::query_result("SELECT site_id FROM {".$this->table."} WHERE id=%d LIMIT 1", $_POST["parent_id"]);
							}
						}
					}
				}
			}
		}
		return  $this->cache["site_id"];
	}
	public function save_variable_site_ids(){
		$this->update_table_rel($this->table."_site_rel", "element_id", "site_id", ! empty($_POST['site_ids']) ? $_POST['site_ids'] : array(), $this->id, $this->is_new);
	}
	public function update_table_rel($table, $element_id_name, $rel_id_name, $rels, $save_id, $new){
	    if(in_array("all", $rels)){
	        $rels = array();
	    }
	    if($rels){
	        if(! $new){
	            $add = array();
	            $del = array();
	            $values = array();
	            $rows = DB::query_fetch_all("SELECT id, %s FROM {%s} WHERE %s=%d AND trash='0'", $rel_id_name, $table, $element_id_name, $save_id);
	            foreach ($rows as $row){
	                if(! in_array($row[$rel_id_name], $rels)){
	                    $del[] = $row["id"];
	                }
	                $values[] = $row[$rel_id_name];
	            }
	            foreach ($rels as $row){
	                if(! in_array($row, $values)){
	                    $add[] = $row;
	                }
	            }
	            if($del){
	                DB::query("DELETE FROM {%s} WHERE id IN (%s)", $table, implode(",", $del));
	            }
	        }else{
	            $add = $rels;
	        }
	
	        foreach ($add as $row){
	            DB::query("INSERT INTO {%s} (%s, %s) VALUES (%d, %d)", $table, $element_id_name, $rel_id_name, $save_id, $row);
	        }
	    }else{
	        if(! $new){
	            DB::query("DELETE FROM {%s} WHERE %s=%d", $table, $element_id_name, $save_id);
	        }
	        DB::query("INSERT INTO {%s} (%s, %s) VALUES (%d, 0)", $table, $element_id_name, $rel_id_name, $save_id);
	    }
	}
	public function save_variable_sort(){
		$this->get_sort();
		$this->set_query("sort=%d");
		$this->set_value($this->save_sort);
	}
	public function get_sort(){
		if (! $this->diafan->variable_list('sort')){
			return;
		}
		if ($this->save_sort){
			return;
		}
		if ($this->is_new){
			$this->save_sort = $this->id;
			return;
		}
		$sort_old = $this->diafan->values("sort", $this->id);

		$lang_act = ($this->diafan->variable_multilang("act") ? _LANG : '');

		//не сортируются неактивные элементы
		if ($this->diafan->is_variable("act") && (! $this->diafan->values("act".$lang_act) || empty($_POST["act"]))){
			$this->save_sort = $sort_old;
			return;
		}
		//переменная $_POST["sort"] - id элемента, перед которым должен выводится редактируемый элемент
		if (empty($_POST["sort"]) || $_POST["sort"] == $this->id){
			$this->save_sort = $sort_old;
			return;
		}

		$sort_new = '';
		//"down" - установить ниже всех
		if (! empty($_POST["sort"]) && $_POST["sort"] == "down"){
			if($this->diafan->variable_list('sort', 'desc')){
				$this->save_sort = 1;
				DB::query("UPDATE {".$this->table."} sort=sort+1"
				." WHERE 1=1"
				.($this->diafan->variable_list('plus') ? " AND parent_id='".$_POST["parent_id"]."'" : '')
				.($this->diafan->config("element") ? ' AND cat_id="'.$_POST["cat_id"].'"' : '')
				.($this->diafan->variable_list('actions', 'trash') ? " AND trash='0'" : '')
				.($this->diafan->is_variable("act") ? " AND act".$lang_act."='1'" : '')) + 1;
			}else{
				$this->save_sort = DB::query_result("SELECT MAX(sort) FROM {".$this->table."}"
				." WHERE 1=1"
				.($this->diafan->variable_list('plus') ? " AND parent_id='".$_POST["parent_id"]."'" : '')
				.($this->diafan->config("element") ? ' AND cat_id="'.$_POST["cat_id"].'"' : '')
				.($this->diafan->variable_list('actions', 'trash') ? " AND trash='0'" : '')
				.($this->diafan->is_variable("act") ? " AND act".$lang_act."='1'" : '')) + 1;
			}
			return;
		}elseif (! empty($_POST["sort"])){
			$this->save_sort = DB::query_result("SELECT sort FROM {".$this->table."} WHERE id=%d LIMIT 1", $_POST["sort"]);
			if(! $this->save_sort){
				DB::query("UPDATE {".$this->table."} SET sort=sort+1");
				if($this->diafan->variable_list('sort', 'desc')){
					$this->save_sort = 2;
				}else{
					$this->save_sort = 1;
				}
				$sort_old++;
			}elseif($this->save_sort == $sort_old){
				if($this->diafan->variable_list('sort', 'desc')){
					$s = $_POST["sort"];
				}else{
					$s = $this->diafan->id;
				}
				DB::query("UPDATE {".$this->table."} SET sort=sort+1 WHERE sort>=%d AND id<>%d", $sort_old, $s);
				return;
			}
		}

		if ($sort_old > $this->save_sort){
			DB::query("UPDATE {".$this->table."} SET sort=sort+1 WHERE sort>%d AND id<>%d"
			.($this->diafan->variable_list('plus') ? " AND parent_id='".$_POST["parent_id"]."'" : '')
			.($this->diafan->config("element") ? ' AND cat_id="'.$_POST["cat_id"].'"' : '')
			.($this->diafan->variable_list('actions', 'trash') ? " AND trash='0'" : '')
			.($this->diafan->is_variable("act") ? " AND act".$lang_act."='1'" : ''),
			$this->save_sort, $this->diafan->id);
		}else{
			if(! $this->diafan->variable_list('sort', 'desc')){
				$this->save_sort--;
			}
			DB::query("UPDATE {".$this->table."} SET sort=sort-1 WHERE sort>%d AND sort<=%d AND id<>%d"
			.($this->diafan->variable_list('plus') ? " AND parent_id='".$_POST["parent_id"]."'" : '')
			.($this->diafan->config("element") ? ' AND cat_id="'.$_POST["cat_id"].'"' : '')
			.($this->diafan->variable_list('actions', 'trash') ? " AND trash='0'" : '')
			.($this->diafan->is_variable("act") ? " AND act".$lang_act."='1'" : ''),
			$sort_old, $this->save_sort, $this->diafan->id);
		}
	}
	public function save_variable_rewrite(){
		if (! $this->is_save_rewrite){
			$this->is_save_rewrite = true;
            $site_id = $this->get_site_id();
            // если изменился раздел сайта, к которому прикреплен элемент
            if ($site_id != $this->diafan->values("site_id")){
                if ($this->diafan->config("category")){
                    if($this->variable_list('plus')){
                        $child = $this->diafan->get_children($this->diafan->id, $this->_frame->table);
                        // меняем раздел сайта у всех вложенных категорий
                        DB::query("UPDATE {".$this->_frame->table."} SET site_id=%d WHERE id IN (%s)",
                            $site_id,
                            implode(",", $child));
                        $child[] = $this->diafan->id;
                        // меняем раздел сайта у всех элементов, принадлежащих текущей и вложенным категориям
                        DB::query("UPDATE {".str_replace("_category", "", $this->_frame->table)."} SET site_id=%d WHERE cat_id IN (%s)",
                            $site_id,
                            implode(",", $child));
                    }else{
                        // меняем раздел сайта у всех элементов, принадлежащих текущей категории
                        DB::query("UPDATE {".str_replace("_category", "", $this->_frame->table)."}"
                            ." SET site_id=%d WHERE cat_id=%d",
                            $site_id,
                            $this->diafan->id);
                    }
                }
            }
            $rewrite = $_POST["rewrite"];
            $text = $_POST[$this->diafan->variable_list("name", "variable") ? $this->diafan->variable_list("name", "variable") : 'name'];
            $element_id = $this->id;
    		$module_name = $this->diafan->module;
    		$element_type = $this->diafan->element_type();
    		if($element_type == 'cat'){
    		    $element_id = 0;
    		    $cat_id = $this->id;
    		}else{
    		    $element_id = $this->id;
    		    $cat_id = ! empty($_POST["cat_id"]) ? $_POST["cat_id"] : 0;
    		}
    		$cat_id = ! empty($_POST["cat_id"]) ? $_POST["cat_id"] : 0;
    		$parent_id = ! empty($_POST["parent_id"]) ? $_POST["parent_id"] : 0;
    		$add_parents = $this->is_new || ! empty($_POST["is_new"]) ? true : false;
    		$change_children = ! $this->is_new && $this->diafan->variable_list('plus');
    		$this->diafan->_route->save($rewrite, $text, $element_id, $module_name, $element_type, $site_id, $cat_id, $parent_id, $add_parents, $change_children);
    		$this->save_rewrite_redirect();
		}
	}
	public function save_variable_parent_id(){
		$this->diafan->save_parent_id = $this->diafan->filter($_POST, 'int', 'parent_id');

		$this->set_query("parent_id='%d'");
		$this->set_value($_POST["parent_id"]);

		// если пункт новый, просто добавляем всех его родителей в table_parents и увеличиваем у родителей количество детей
		if ($this->diafan->is_new){
			if ($_POST["parent_id"]){
				$parents = $this->diafan->get_parents($_POST["parent_id"], $this->table);
				$parents[] = $_POST["parent_id"];
				foreach ($parents as $parent_id){
					DB::query("UPDATE {".$this->table."} SET count_children=count_children+1 WHERE id=%d", $parent_id);
					DB::query("INSERT INTO {".$this->table."_parents} (element_id, parent_id) VALUES (%d, %d)", $this->diafan->id, $parent_id);
				}
			}
			return;
		}
		// если родитель не изменился, уходим
		if ($this->diafan->values("parent_id") == $_POST["parent_id"]){return;}

		$children = $this->diafan->get_children($this->diafan->id, $this->table);
		$children[] = $this->diafan->id;
		$count_children = count($children);

		// если родитель был, у текущего элемента и его детей удаляем всех старых родителей, вышего текущего элемента
		// у старых родителей выше текущего элемента уменьшаем количество детей
		if ($this->diafan->values("parent_id"))
		{
			$old_parents = $this->diafan->get_parents($this->diafan->id, $this->table);
			foreach ($old_parents as $parent_id)
			{
				DB::query("DELETE FROM {".$this->table."_parents} WHERE element_id IN (%h) AND parent_id=%d", implode(",", $children), $parent_id);
				DB::query("UPDATE {".$this->table."} SET count_children=count_children-%d WHERE id=%d", $count_children, $parent_id);
			}
		}
		// если новый родитель задан, то текущему элементу и его детям прибавляем новых родителей и увеличиваем у родителей количество детей
		if ($_POST["parent_id"]){
			$parents = $this->diafan->get_parents($_POST["parent_id"], $this->table);
			$parents[] = $_POST["parent_id"];
			foreach ($parents as $parent_id){
				DB::query("UPDATE {".$this->table."} SET count_children=count_children+%d WHERE id=%d", $count_children, $parent_id);
				foreach ($children as $child){
					DB::query("INSERT INTO {".$this->table."_parents} (element_id, parent_id) VALUES (%d, %d)", $child, $parent_id);
				}
			}
		}
	}
	public function save_variable_timeedit(){
		$this->set_query("timeedit='%s'");
		$this->set_value(time());
	}
	public function save_variable_act(){
		$lang = $this->diafan->variable_multilang("act") ? _LANG : '';
		$_POST["act"] = ! empty($_POST["act"]) ? '1' : '0';
		if($this->diafan->values('act') == $_POST["act"]){return;}
		if ($this->diafan->variable_list('plus')){
			$ids = $this->diafan->get_children($this->id, $this->table, array (), false);
		}
		$ids[] = $this->id;
		if ($ids){
			DB::query("UPDATE {".$this->table."} SET act".$lang."='".( !empty( $_POST["act"] ) ? "1".( $this->diafan->is_variable("timeedit") ? "', timeedit='".time() : '' ) : '0' )."' 
			    WHERE id IN (".implode(',', $ids).")");
		}
		if ($this->diafan->config('category') && $this->diafan->values('act') != $_POST["act"]){
			DB::query("UPDATE {".str_replace('_category', '', $this->table)."} SET act".$lang."='".( !empty( $_POST["act"] ) ? "1".( $this->diafan->is_variable("timeedit") ? "', timeedit='".time() : '' ) : '0' )."' 
			    WHERE cat_id IN (".implode(',', $ids).")");
		}
		foreach ($this->diafan->installed_modules as $module){
			if (Custom::exists('modules/'.$module.'/admin/'.$module.'.admin.inc.php')){
				Custom::inc('modules/'.$module.'/admin/'.$module.'.admin.inc.php');
				$func = 'act';
				$class = ucfirst($module).'_admin_inc';
				if (method_exists($class, 'act')){
					$admin_act = new $class($this->diafan);
					$admin_act->act($this->table, $ids, !empty( $_POST["act"] ) ? 1 : 0);
				}
			}
		}
	}
	public function save_variable_access(){
		$roles = array ();
		$old_roles = array ();
		$new_roles = array ();
		$element_id = $this->diafan->id;
		$element_type = $this->diafan->element_type();

		$roles = DB::query_fetch_value("SELECT id FROM {users_role} WHERE trash='0'", "id");
		$roles[] = 0;
		if ($this->diafan->values('access')){
			$old_roles = DB::query_fetch_value("SELECT role_id FROM {access} WHERE element_id=%d AND element_type='%s' AND module_name='%s'", $element_id, $element_type, $this->diafan->module, "role_id");
		}

		foreach ($_POST['access_role'] as $role_id){
			$new_roles[] = intval($role_id);
		}
		if (empty( $_POST["access"])){
			$new_roles = array();
		}
		$this->set_query("access='%d'");
		$this->set_value($new_roles ? 1 : 0);
		if($new_roles){
			$this->diafan->configmodules('where_access_'.$element_type, $this->diafan->module, 0, 0, true);
			$this->diafan->configmodules('where_access', 'all', 0, 0, true);
		}elseif($this->diafan->configmodules('where_access_'.$element_type, $this->diafan->module, 0)){
			if(! DB::query_result("SELECT id FROM {access} WHERE module_name='%s' AND element_id<>%d AND element_type='%s' LIMIT 1", $this->diafan->_frame->module, $element_id, $element_type)){
				$this->diafan->configmodules('where_access_'.$element_type, $this->diafan->module, 0, 0, 0);
				if(! DB::query_result("SELECT id FROM {config} WHERE module_name<>'all' AND value='1' AND name LIKE 'where_access%' LIMIT 1"))
				{
					$this->diafan->configmodules('where_access', 'all', 0, 0, 0);
				}
				else
				{
					$this->diafan->configmodules('where_access', 'all', 0, 0, true);
				}
			}
		}
		if (! array_diff($new_roles, $old_roles) && ! array_diff($old_roles, $new_roles)){
			return true;
		}
		$diff_new_roles = array_diff($new_roles, $old_roles);
		foreach ($diff_new_roles as $role_id){
			DB::query("INSERT INTO {access} (element_id, element_type, module_name, role_id) VALUES (%d, '%s', '%s', %d)", $element_id, $element_type, $this->diafan->module, $role_id);
		}
		$diff_old_roles = array_diff($old_roles, $new_roles);
		if ($diff_old_roles){
			DB::query("DELETE FROM {access} WHERE element_id=%d AND element_type='%s' AND module_name='%s' AND role_id IN (%s)", $element_id, $element_type, $this->diafan->module, implode(",", $diff_old_roles));
		}
		if ($this->diafan->config('category_rel')){
			$rows = DB::query_fetch_all("SELECT id, access FROM {".$this->diafan->module."} WHERE cat_id=%d", $cat_id);
			foreach ($rows as $row)
			{
				$old_roles_el = array ();
				if ($row["access"]){
					$old_roles_el = DB::query_fetch_value("SELECT role_id FROM {access} WHERE element_id=%d AND module_name='%s' AND element_type='element'", $row["id"], $this->diafan->module, "role_id");
				}
				if (!array_diff($old_roles_el, $old_roles) && !array_diff($old_roles, $old_roles_el)){
					foreach ($diff_new_roles as $role_id)
					{
						DB::query("INSERT INTO {access} (element_id, element_type, module_name, role_id) VALUES (%d, 'element', '%s', %d)", $row["id"], $this->diafan->module, $role_id);
					}
					if ($diff_old_roles)
					{
						DB::query("DELETE FROM {access} WHERE element_id=%d AND module_name='%s' AND element_type='element' AND role_id IN (%s)", $row["id"], $this->diafan->module, implode(",", $diff_old_roles));
					}
					if (! $new_roles && $row["access"] || $new_roles && !$row["access"])
					{
						DB::query("UPDATE {".$this->diafan->module."} SET access='%d' WHERE id=%d", $row["access"] ? 0 : 1, $row["id"]);
					}
				}
				else
				{
					$diff_old_roles_el = array_diff($old_roles_el, $new_roles);
					foreach ($diff_new_roles_el as $role_id)
					{
						DB::query("DELETE FROM {access} WHERE element_id=%d AND module_name='%s' AND element_type='element' AND role_id=%d", $row["id"], $this->diafan->module, $role_id);
					}
				}
			}

			$children = $this->diafan->get_children($cat_id, $this->table);
			if ($children){
				$rows = DB::query_fetch_all("SELECT id, access FROM {".$this->table."} WHERE id IN (%s)", implode(",", $children));
				foreach ($rows as $row){
					$old_roles_el = array ();
					if ($row["access"]){
						$old_roles_el = DB::query_fetch_value("SELECT role_id FROM {access} WHERE element_id=%d AND module_name='%s' AND element_type='cat'", $row["id"], $this->diafan->module,"role_id");
					}
					if (!array_diff($old_roles_el, $old_roles) && !array_diff($old_roles, $old_roles_el)){
						foreach ($diff_new_roles as $role_id){
							DB::query("INSERT INTO {access} (element_id, module_name, element_type, role_id) VALUES (%d, '%s', 'cat', %d)", $row["id"], $this->diafan->module, $role_id);
						}
						if ($diff_old_roles){
							DB::query("DELETE FROM {access} WHERE element_id=%d AND module_name='%s' AND element_type='cat' AND role_id IN (%s)", $row["id"], $this->diafan->module, implode(",", $diff_old_roles));
						}
						if (! $new_roles && $row["access"] || $new_roles && !$row["access"]){
							DB::query("UPDATE {".$this->table."} SET access='%d' WHERE id=%d", $row["access"] ? 0 : 1, $row["id"]);
						}
					}else{
						$diff_old_roles_el = array_diff($old_roles_el, $new_roles);
						foreach ($diff_new_roles_el as $role_id){
							DB::query("DELETE FROM {access} WHERE element_id=%d AND module_name='%s' AND element_type='cat' AND role_id=%d", $row["id"], $this->diafan->module, $role_id);
						}
					}
				}
			}
			if(! DB::query_result("SELECT id FROM {access} WHERE module_name='%s' AND element_type='element' LIMIT 1", $this->diafan->module))
			{
				$this->diafan->configmodules('where_access_element', $this->diafan->module, 0, 0, 0);
				if(! DB::query_result("SELECT id FROM {config} WHERE module_name<>'all' AND value='1' AND name LIKE 'where_access%' LIMIT 1"))
				{
					$this->diafan->configmodules('where_access', 'all', 0, 0, 0);
				}else{
					$this->diafan->configmodules('where_access', 'all', 0, 0, true);
				}
			}else{
				$this->diafan->configmodules('where_access_element', $this->diafan->module, 0, 0, 1);
				$this->diafan->configmodules('where_access', 'all', 0, 0, true);
			}
		}
	}
	public function save_variable_date_period(){
		$this->set_query("date_start=%d");
		$this->set_value($this->diafan->unixdate($_POST["date_start"]));
		$this->set_query("date_finish=%d");
		$this->set_value($this->diafan->unixdate($_POST["date_finish"]));
		$element_type = $this->diafan->element_type();

		if(! empty($_POST["date_start"]) || ! empty($_POST["date_finish"])){
			$this->diafan->configmodules('where_period_'.$element_type, $this->diafan->module, 0, 0, true);
			$this->diafan->configmodules('where_period', 'all', 0, 0, true);
		}elseif($this->diafan->configmodules('where_period_'.$element_type, $this->diafan->module, 0)){
			if(! DB::query_result("SELECT id FROM {%s} WHERE (date_start>0 OR date_finish>0) AND id<>%d LIMIT 1", $this->table, $this->id))
			{
				$this->diafan->configmodules('where_period_'.$element_type, $this->diafan->module, 0, 0, 0);
				if(! DB::query_result("SELECT id FROM {config} WHERE module_name<>'all' AND value='1' AND name LIKE 'where_period%%' LIMIT 1"))
				{
					$this->diafan->configmodules('where_period', 'all', 0, 0, 0);
				}else{
					$this->diafan->configmodules('where_period', 'all', 0, 0, true);
				}
			}
		}
	}
	public function get_date_period(){
		if($this->diafan->is_variable('created')){
			if($this->diafan->unixdate($_POST["created"]) > time()){
				if(empty($_POST["date_start"])){
					$_POST["date_start"] = $_POST["created"];
				}
			}else{
				if($this->diafan->unixdate($_POST["date_start"]) == $this->diafan->values("created") && empty($_POST["date_finish"])){
					$_POST["date_start"] = '';
				}
			}
		}
	}
	public function save_variable_dynamic(){
		$element_type = $this->diafan->element_type();
		$dynamic = DB::query_fetch_all("SELECT b.* FROM {site_dynamic} AS b"
			." INNER JOIN {site_dynamic_module} AS m ON m.dynamic_id=b.id"
			." WHERE b.trash='0'"
			." AND (m.module_name='%h' OR m.module_name='') AND (m.element_type='%h' OR m.element_type='')"
			." GROUP BY b.id",
			$this->diafan->module, $element_type
		);

		if($this->is_new){
			$element_dynamic = array();
		}else{
			$element_dynamic = DB::query_fetch_key("SELECT * FROM {site_dynamic_element} WHERE element_id=%d AND element_type='%s' AND module_name='%s'", $this->diafan->id, $element_type, $this->diafan->module, "dynamic_id");
		}
		$dynamic_ids = array();
		foreach($dynamic as $d){
			$not_empty_multitext = false;
			if(in_array($d["type"], array('text', 'textarea', 'editor'))){
				foreach($this->diafan->_languages->all as $l){
					if($l["id"] != _LANG && ! empty($element_dynamic[$d["id"]]["value".$l["id"]])){
						$not_empty_multitext = true;
					}
				}
			}
			
			$dynamic_ids[] = $d["id"];
			if(! empty($_POST["dynamic".$d["id"]]) || $not_empty_multitext){
				$value = $_POST["dynamic".$d["id"]];
				$multilang = false;
				switch($d["type"]){
					case 'text':
						$mask = "'%h'";
						$multilang = true;
						break;

					case 'email':
						$mask = "'%h'";
						break;

					case 'textarea':
					case 'editor':
						$mask = "'%s'";
						$multilang = true;
						$value = $this->save_field_editor("dynamic".$d["id"]);
						break;

					case 'numtext':
						$mask = "'%d'";
						break;

					case 'floattext':
						$mask = "'%f'";
						break;

					case 'date':
					case 'datetime':
						$value = $this->diafan->unixdate($value);
						$mask = "'%d'";
						break;
				}
				$parent = 0;
				if($this->diafan->variable_list('plus') && ! empty($_POST["dynamic_parent".$d["id"]])){
					$parent = 1;
				}
				$category = 0;
				if($this->diafan->config("category") && ! empty($_POST["dynamic_category".$d["id"]])){
					$category = 1;
				}
				if($value){
					if(! empty($element_dynamic[$d["id"]])){
						DB::query("UPDATE {site_dynamic_element} SET ".($multilang ? "[value]" : "value".$this->diafan->_languages->site)."=".$mask.", parent='%d', category='%d' 
						    WHERE id=%d", $value, $parent, $category, $element_dynamic[$d["id"]]["id"]);
					}else{
						DB::query("INSERT INTO {site_dynamic_element} (dynamic_id, module_name, element_id, element_type, ".($multilang ? "[value]" : "value".$this->diafan->_languages->site).", parent, category) 
						    VALUES (%d, '%s', %d, '%s', ".$mask.", '%d', '%d')", $d["id"], $this->diafan->module, $this->id, $element_type, $value, $parent, $category);
					}
				}
			}elseif(! empty($element_dynamic[$d["id"]])){
				$del_ids[] = $element_dynamic[$d["id"]]["id"];
			}
		}
		foreach($element_dynamic as $dynamic_id => $d){
			if(! in_array($dynamic_id, $dynamic_ids)){
				$del_ids[] = $d["id"];
			}
		}
		if(isset($del_ids)){
			DB::query("DELETE FROM {site_dynamic_element} WHERE id IN (%s)", implode(",", $del_ids));
		}
	}
	public function save_variable_map_no_show(){
		if ($this->diafan->variable_list('plus')){
			$ids = $this->diafan->get_children($this->diafan->id, $this->table, array (), false);
		}
		$ids[] = $this->diafan->id;
		if ($ids){
			DB::query("UPDATE {".$this->table."} SET map_no_show='".( !empty( $_POST["map_no_show"] ) ? "1" : '0' )."' WHERE id IN (".implode(',', $ids).")");
		}
		if ($this->diafan->config('category')){
			DB::query("UPDATE {".str_replace('_category', '', $this->table)."} SET map_no_show='".( !empty( $_POST["map_no_show"] ) ? "1" : '0' )."' WHERE cat_id IN (".implode(',', $ids).")");
		}
	}
	public function save_variable_admin_id(){
		if(! $this->diafan->values("admin_id")){
			$this->set_query("admin_id=%d");
			$this->set_value($this->diafan->_users->id);
		}
	}
	public function save_variable_user_id(){
		if(! $this->diafan->variable('user_id', 'disabled')){
			$this->set_query("user_id=%d");
			$this->set_value($_POST["user_id"]);
		}
	}
	public function save_variable_param($where = ''){
		$ids = array();
		$rows = DB::query_fetch_all("SELECT id, type, config FROM {".$this->table."_param}"
			." WHERE trash='0'".$where." ORDER BY sort ASC");

		foreach ($rows as $row){
			if($row["type"] == 'attachments'){
				Custom::inc('modules/attachments/admin/attachments.admin.inc.php');
				$attachments = new Attachments_admin_inc($this->diafan);
				$attachments->save_param($row["id"], $row["config"]);
				continue;
			}
			if($row["type"] == "editor"){
				$_POST['param'.$row["id"]] = $this->save_field_editor('param'.$row["id"]);
			}

			if ($row["type"] == "multiple"){
				DB::query("DELETE FROM {".$this->table."_param_element} WHERE param_id=%d AND element_id=%d", $row["id"], $this->diafan->id);
				if (! empty($_POST['param'.$row["id"]]) && is_array($_POST['param'.$row["id"]])){
					foreach ($_POST['param'.$row["id"]] as $v){
						DB::query(
							"INSERT INTO {".$this->table."_param_element} (value, param_id, element_id) VALUES ('%s', %d, %d)",
							$v,
							$row["id"],
							$this->diafan->id
						);
					}
				}
				$ids[] = $row["id"];
			}elseif (! empty($_POST['param'.$row["id"]])){
				$id = 0;
				if (! $this->diafan->_route->is_new){
					$id = DB::query_result("SELECT id FROM {".$this->table."_param_element} WHERE param_id=%d AND element_id=%d LIMIT 1", $row["id"], $this->diafan->id);
				}
				switch($row["type"])
				{
					case "date":
						$_POST['param'.$row["id"]] = $this->diafan->formate_in_date($_POST['param'.$row["id"]]);
						break;

					case "datetime":
						$_POST['param'.$row["id"]] = $this->diafan->formate_in_datetime($_POST['param'.$row["id"]]);
						break;

					case "numtext":
						$_POST['param'.$row["id"]] = str_replace(',', '.', $_POST['param'.$row["id"]]);
						break;
				}
				$multilang = in_array($row["type"], array("text", "editor", "textarea")) && ($this->diafan->variable_multilang("name") || $this->diafan->variable_multilang("text"));
				if ($id)
				{
					DB::query(
						"UPDATE {".$this->table."_param_element} 
					    SET ".($multilang ? '[value]' : 'value')
						."='%s' WHERE id=%d",
						$_POST['param'.$row["id"]],
						$id
					);
					DB::query("DELETE FROM {".$this->table."_param_element} 
					    WHERE param_id=%d AND element_id=%d AND id<>%d", $row["id"], $this->diafan->id, $id);
				}
				else
				{
					DB::query(
						"INSERT INTO {".$this->table."_param_element} (".($multilang ? '[value]' : 'value')
						.", param_id, element_id) VALUES ('%s', %d, %d)",
						$_POST['param'.$row["id"]],
						$row["id"],
						$this->diafan->id
					);
				}
				$ids[] = $row["id"];
			}
		}
		DB::query("DELETE FROM {".$this->table."_param_element} 
		    WHERE".($ids ? " param_id NOT IN (".implode(", ", $ids).") AND" : "")." element_id=%d", $this->diafan->id);
	}
	public function save_variable_param_select()
	{
		$name = $this->diafan->variable_multilang("name") ? '[name]' : 'name';
		switch ($_POST["type"])
		{
			case "select":
			case "multiple":
				if(! empty($_POST["param_textarea_check"]))
				{
					$values = DB::query_fetch_value("SELECT id FROM {".$this->table."_select} WHERE param_id=%d", $this->diafan->id, "id");
					$strings = explode("\n", $_POST["param_textarea"]);
					$sort = 1;
					foreach ($strings as $i => $data)
					{
						$data = trim($data);
						if(empty($data) && $data !== "0")
						{
							continue;
						}
						$id = (! empty($values[$i]) ? $values[$i] : '');
						if($id)
						{
							DB::query("UPDATE {".$this->table."_select} SET ".$name."='%h', sort=%d WHERE id=%d", $data, $sort, $id);
						}
						else
						{
							$id = DB::query("INSERT INTO {".$this->table."_select} (param_id, ".$name.", sort) VALUES (%d, '%h', %d)", $this->diafan->id, $data, $sort);
						}
						$sort++;
						$ids[] = $id;
					}
				}
				else
				{
					$ids = array();
					if(! empty($_POST["paramv"]))
					{
						$sort = 1;
						foreach ($_POST["paramv"] as $key => $value)
						{
							$value = trim($value);
							if (! $value && $value !== "0")
								continue;
	
							$id = 0;
							if ( ! empty($_POST["param_id"][$key]))
							{
								$id = $_POST["param_id"][$key];
							}
							if ($id)
							{
								DB::query("UPDATE {".$this->table."_select} SET ".$name."='%h', sort=%d WHERE id=%d", $value, $sort, $id);
							}
							else
							{
								$id = DB::query("INSERT INTO {".$this->table."_select} (param_id, ".$name.", sort) VALUES (%d, '%h', %d)", $this->diafan->id, $value, $sort);
							}
							$sort++;
							$ids[] = $id;
						}
					}
				}
	
				if ( ! empty($ids))
				{
					$del_ids = DB::query_fetch_value("SELECT id FROM {".$this->table."_select} WHERE param_id=%d AND id NOT IN (%s)", $this->diafan->id, implode(",", $ids), "id");
					if($del_ids)
					{
						DB::query("DELETE FROM {".$this->table."_select} WHERE id IN (%s)", implode(",", $del_ids));
						DB::query("DELETE FROM {".$this->table."_param_element} WHERE param_id=%d AND value IN (%s)", $this->diafan->id, implode(",", $del_ids));
					}
				}

				break;
			case "checkbox":
				if ($this->diafan->values("type") == "checkbox" && ($_POST["paramk_check1"] || $_POST["paramk_check0"]))
				{
					$rows = DB::query_fetch_all("SELECT id, value FROM {".$this->table."_select} WHERE param_id=%d", $this->diafan->id);
					foreach ($rows as $row)
					{
						if ($row["value"] == 1)
						{
							DB::query("UPDATE {".$this->table."_select} SET ".$name."='%h' WHERE id=%d", $_POST["paramk_check1"], $row["id"]);
							$check1 = true;
						}
						elseif ($row["value"] == 0)
						{
							DB::query("UPDATE {".$this->table."_select} SET ".$name."='%h' WHERE id=%d", $_POST["paramk_check0"], $row["id"]);
							$check0 = true;
						}
					}
					DB::query("DELETE FROM {".$this->table."_select} WHERE param_id=%d AND value NOT IN (0,1)", $this->diafan->id);
	
				}
				else
				{
					DB::query("DELETE FROM {".$this->table."_select} WHERE param_id=%d", $this->diafan->id);
				}
				if (empty($check0) && $_POST["paramk_check0"])
				{
					DB::query("INSERT INTO {".$this->table."_select} (param_id, value, ".$name.") VALUES (%d, 0, '%h')", $this->diafan->id, $_POST["paramk_check0"]);
				}
				if (empty($check1) && $_POST["paramk_check1"])
				{
					DB::query("INSERT INTO {".$this->table."_select} (param_id, value, ".$name.") VALUES (%d, 1, '%h')", $this->diafan->id, $_POST["paramk_check1"]);
				}

				break;

			default:
				DB::query("DELETE FROM {".$this->table."_select} WHERE param_id=%d", $this->diafan->id);
		}
		$types = $this->diafan->variable("type", "select");
		if(! empty($types["attachments"]))
		{
			Custom::inc('modules/attachments/admin/attachments.admin.inc.php');
			$attachment = new Attachments_admin_inc($this->diafan);
			$attachment->save_config_param();
		}
		if(! empty($types["images"]))
		{
			Custom::inc('modules/images/admin/images.admin.inc.php');
			$images = new Images_admin_inc($this->diafan);
			$images->save_config_param();
		}
	}
	public function save_variable_anons()
	{
		$this->set_query("anons_plus"._LANG."='%d'");
		$this->set_value(empty($_POST["anons_plus"]) ? 0 : 1);

		$this->set_query("anons"._LANG."='%s'");
		$this->set_value($this->save_field_editor('anons'));
	}
	public function set_value($value){$this->value[] = $value;}
	public function set_query($query){$this->query[] = $query;}
}