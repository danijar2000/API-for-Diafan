<?php
if (! defined('DIAFAN')){
	$path = __FILE__; $i = 0;
	while(! file_exists($path.'/includes/404.php')){
		if($i == 10) exit; $i++;
		$path = dirname($path);
	}
	include $path.'/includes/404.php';
}
class Edit_admin_api extends Diafan{	
    /**
     * @var array значения полей
     */
    public $values = array ();
    
    /**
     * @var integer счетчик
     */
    public $k = 0;
    
    /**
     * @var string название текущего поля
     */
    public $key;
    
    /**
     * @var mixed значение текущего поля
     */
    public $value;
    
    /**
     * @var array названия табов
     */
    public $tabs_name;
    
    public function show_table($variable_table){
        $not = array("rel_elements");
        foreach ($variable_table as $this->diafan->key => $row){
            if(is_array($row)){
                $type = $row["type"];
            }else{
                $type = $row;
            }
            $this->k++;
            $key = $this->diafan->key.(! $this->diafan->config("config") && $this->diafan->variable_multilang($this->diafan->key) ? _LANG : '' );
            $this->diafan->value = $this->diafan->values($key);
            if($this->diafan->value === false){
                $this->diafan->value = '';
            }
            $func = 'edit_variable_'.str_replace('-', '_', $this->diafan->key);
            if(method_exists($this, $func)){
                $this->$func();
            }else{
                $this->show_table_tr(
                    $type,
                    $this->diafan->key,
                    $this->diafan->value,
                    $this->diafan->variable_name(),
                    $this->diafan->help(),
                    $this->diafan->variable_disabled(),
                    $this->diafan->variable('', 'maxlength'),
                    $this->diafan->variable('', 'select'),
                    $this->diafan->variable('', 'select_db'),
                    $this->diafan->variable('', 'depend')
                    );
            }
        }
    }
    public function show_table_tr($type, $key, $value, $name, $help, $disabled, $maxlength, $select, $select_db, $depend){
        switch($type){
            case 'module':
                if (in_array($key, $this->diafan->installed_modules)
                && Custom::exists('modules/api/modules/'.$key.'.php')){
                    Custom::inc('modules/api/modules/'.$key.'.php');
                    $func = 'edit';
                    $class = ucfirst($key).'_api';
                    if (method_exists($class, 'edit')){
                        $module_class = new $class($this->diafan);
                        $module_class->edit();
                    }
                }
                break;
            case 'title':$this->show_tr_title($key, $name, $help);break;
            case 'password':$this->show_password($key, $name, $value, $help, $disabled);break;
            case 'text':$this->show_text($key, $name, $value, $help, $disabled, $maxlength);break;
            case 'email':$this->show_email($key, $name, $value, $help, $disabled);break;
            case 'phone':$this->show_phone($key, $name, $value, $help, $disabled);break;
            case 'date':$this->show_date($key, $name, $value, $help, $disabled);break;
            case 'datetime':$this->show_datetime($key, $name, $value, $help, $disabled);break;
            case 'numtext':$this->show_numtext($key, $name, $value, $help, $disabled);break;
            case 'floattext':$this->show_floattext($key, $name, $value, $help, $disabled);break;
            case 'textarea':$this->show_textarea($key, $name, $value, $help, $disabled, $maxlength);break;
            case 'checkbox':$this->show_checkbox($key, $name, $value, $help, $disabled);break;
            case 'select':
                if(! $select && $select_db){
                    $select = $this->diafan->get_select_from_db($select_db);
                }
                $this->show_select($key, $name, $value, $help, $disabled, $select);
                break;
            case 'editor':$this->show_editor($key, $name, $value, $help);break;
        }
    }
    public function get_options($cats, $rows, $values, $marker = ''){
        $arr = array();
        foreach ($rows as $row){
            if(! $row)continue;
            $arr[] = array("value" => $row["id"], "name" => $marker.$this->diafan->short_text($row["name"],40),"status"=> in_array($row["id"], $values));
            if (! empty( $cats[$row["id"]] )){
                $arr = array_merge($arr,$this->get_options($cats, $cats[$row["id"]], $values, $marker.' '));
            }
        }
        return $arr;
    }
	public function edit_variable_cat_id(){
		if (! $this->diafan->config("element")){
			return;
		}
		if (! $this->diafan->value){
			$this->diafan->value = $this->diafan->_route->cat;
			$this->diafan->values('cat_id', $this->diafan->_route->cat, true);
		}
		if ($this->diafan->config("category_flat")){
			$cats[0] = DB::query_fetch_all("SELECT id, ".($this->diafan->config('category_no_multilang') ? "name" : "[name]").($this->diafan->config('element_site') ? ", site_id AS rel" : "")." FROM {%s_category} WHERE trash='0' ORDER BY sort ASC", $this->diafan->table);
		}else{
			$cs = DB::query_fetch_all("SELECT id, ".($this->diafan->config('category_no_multilang') ? "name" : "[name]").($this->diafan->config('element_site') ? ", site_id AS rel" : "").", parent_id FROM {%s_category} WHERE trash='0'".( $this->diafan->config("element_multiple") ? " ORDER BY sort ASC LIMIT 1000" : "" ), $this->diafan->table);
			foreach($cs as $c){
				$cats[$c["parent_id"]][] = $c;
			}
		}
		$this->diafan->data["cat_id"] = array("type" => "select", "name" => $this->diafan->variable_name(), "value" => $this->get_options($cats, $cats[0], array ($this->diafan->value)));
		if ($this->diafan->config("element_multiple")){
			$values = array();
			if (! $this->diafan->is_new){
				$rows = DB::query_fetch_all("SELECT cat_id FROM {%s_category_rel} WHERE element_id=%d AND cat_id>0", $this->diafan->table, $this->diafan->id);
				foreach ($rows as $row){
					if ($row["cat_id"] != $this->diafan->value){
						$values[] = $row["cat_id"];
					}
				}
			}
			$this->diafan->values('cat_ids', $values, true);
			$this->diafan->data["cat_ids"] = array("type" => "multiple","name" => $this->diafan->_('Дополнительные категории'),"value" => $this->get_options($cats, $cats[0], $values));
		}
	}
	public function edit_variable_access(){
		$checked = array ();
		if ($this->diafan->value == '1'){
			$checked = DB::query_fetch_value("SELECT role_id FROM {access} WHERE element_id=%d AND module_name='%s' AND element_type='%s'", $this->diafan->id, $this->diafan->_admin->module, $this->diafan->element_type(), "role_id");
		}
		$this->diafan->data["access"] = array("type"=>"checkbox","name"=>$this->diafan->_('Доступ только'),"value"=>$this->diafan->value=='1');
		$arr[] = array("value"=>"0","name"=>$this->diafan->_('Гость'),"status" => !$this->diafan->value || in_array(0, $checked));
		
		$rows = DB::query_fetch_all("SELECT id, [name] FROM {users_role} WHERE trash='0'");
		foreach ($rows as $row){
		    $arr[] = array("value"=>$row['id'],"name"=>$row['name'],"status" => (!$this->diafan->value || in_array($row['id'], $checked)));
		}
		$this->diafan->data["access_role[]"] = array("type" => "multiple", "name" => $this->diafan->_('Роли'),"value" => $arr);
	}
	public function edit_variable_parent_id(){
		if ($this->diafan->is_new){
			$this->diafan->value = $this->diafan->parent;
		}
		$this->diafan->data["parent_info"]["type"] = "info";
		if( !$this->diafan->value){
			if($this->diafan->module == 'site'){
				$this->diafan->data["parent_info"]["name"] = $this->diafan->_('Главная');
			}else{
				$this->diafan->data["parent_info"]["name"] = $this->diafan->_('нет');
			}
		}else{
			if($this->diafan->variable_list("name", "variable")){
				$list_name = $this->diafan->variable_list("name", "variable");
			}else{
				$list_name = 'name';
			}
			$list_name = ($this->diafan->variable_multilang($list_name) ? '['.$list_name.']' : $list_name);
			$this->diafan->data["parent_info"]["name"] = DB::query_result("SELECT ".$list_name." FROM {".$this->diafan->table."} WHERE id=%d LIMIT 1", $this->diafan->value) ;
		}
		$this->diafan->data["parent_id"] = array("type" => "hidden", "value" => $this->diafan->value);
	}
	public function edit_variable_site_id(){
		if (! $this->diafan->value){
			$this->diafan->value = $this->diafan->site_id;
		}
		$this->diafan->data["site_id"]["type"] = "select";
    	$cats[0] = DB::query_fetch_all("SELECT id, [name] FROM {site} WHERE trash='0' AND module_name='%s' ORDER BY sort ASC, id DESC", $this->diafan->module);
    	$this->diafan->data["site_id"] = array("type" => "select", "name" => $this->diafan->variable_name(),"value" =>$this->get_options($cats, $cats[0], array ( $this->diafan->value )));
	}
	public function edit_variable_site_ids(){
		$show_in_site_id = array();
		if(! $this->diafan->is_new){
			$show_in_site_id = DB::query_fetch_value("SELECT site_id FROM {".$this->diafan->table."_site_rel} WHERE element_id=%d AND site_id>0", $this->diafan->id, "site_id");
		}
		$this->diafan->data["site_ids"]["type"] = "multiple";
		$arr[] = array("value"=>"all","name"=>$this->diafan->_('Все'),"status" => empty($show_in_site_id));
		$cats = DB::query_fetch_key_array("SELECT id, [name], parent_id FROM {site} WHERE trash='0' AND [act]='1' ORDER BY sort ASC, id DESC", "parent_id");
		$this->diafan->data["site_ids"]["value"] = array_merge($arr,$this->get_options($cats, $cats[0], $show_in_site_id));
	}
	public function edit_variable_rewrite(){
		$rewrite = '';
		$redirect = '';
		$redirect_code = 301;
		if (! $this->diafan->is_new){
			$rewrite = DB::query_result("SELECT rewrite FROM {rewrite} WHERE module_name='%s' AND element_id=%d AND element_type='%s' LIMIT 1", $this->diafan->module, $this->diafan->id, $this->diafan->element_type());
			$row_redirect = DB::query_fetch_array("SELECT redirect, code FROM {redirect} WHERE module_name='%s' AND element_id=%d AND element_type='%s' LIMIT 1", $this->diafan->module, $this->diafan->id, $this->diafan->element_type());
			$redirect = $row_redirect["redirect"];
			$redirect_code = $row_redirect["code"];
		}
		if(! $redirect_code){
			$redirect_code = 301;
		}
		$rewrite_site = '';
		if (!$rewrite && $this->diafan->module != "site"){
			if ($this->diafan->config("element") && $this->diafan->values("cat_id")){
				if (! $rewrite_site = DB::query_result("SELECT rewrite FROM {rewrite} WHERE module_name='%s' AND element_id=%d AND element_type='cat' LIMIT 1", $this->diafan->module, $this->diafan->values("cat_id"))){
					$rewrite_site = DB::query_result("SELECT rewrite FROM {rewrite} WHERE module_name='site' AND element_id=%d AND element_type='element' LIMIT 1", $this->diafan->values("site_id"));
				}
			}elseif ($this->diafan->config("category")){
				if ((! $this->diafan->values("parent_id")
					|| ! $rewrite_site = DB::query_result("SELECT rewrite FROM {rewrite} WHERE module_name='%s' AND element_id=%d AND element_type='cat' LIMIT 1", $this->diafan->module, $this->diafan->values("parent_id")))
					&& $this->diafan->values("site_id")){
					$rewrite_site = DB::query_result("SELECT rewrite FROM {rewrite} WHERE module_name='site' AND element_id=%d AND element_type='element' LIMIT 1", $this->diafan->values("site_id"));
				}
			}elseif ($this->diafan->values("site_id")){
				$rewrite_site = DB::query_result("SELECT rewrite FROM {rewrite} WHERE module_name='site' AND element_id=%d AND element_type='element' LIMIT 1", $this->diafan->values("site_id"));
			}
		}
		$this->diafan->data["rewrite"] = array("type" => "text", "name" => $this->diafan->variable_name(), "value" => $rewrite);
		$this->diafan->data["rewrite_redirect"] = array("type" => "text", "name" => $this->diafan->_('Редирект на текущую страницу со страницы'), "value" => $redirect);
		$this->diafan->data["rewrite_code"] = array("type" => "text", "name"=> $this->diafan->_('редирект с кодом ошибки') , "value" => $redirect_code, "maxlength" => 5);
	}
	public function edit_variable_anons(){
		$value = $this->diafan->_route->replace_id_to_link($this->diafan->value);
		$name = $this->diafan->variable_name('anons');
		if($this->diafan->is_new){
			$hide_htmleditor = false;
		}else{
			$hide_htmleditor = in_array('anons', explode(",", $this->diafan->configmodules("hide_".$this->diafan->table."_".$this->diafan->id, "htmleditor")));
		}
		$this->diafan->data["anons_plus"]["type"] = "checkbox";
		$this->diafan->data["anons_plus"]["name"] = $this->diafan->_('Добавлять к описанию');
		$this->diafan->data["anons_plus"]["value"] = ($this->diafan->values("anons_plus"._LANG) ? ' checked' : '');
		
		$this->diafan->data["anons"]["type"] = "textarea";
		$this->diafan->data["anons"]["name"] = $this->diafan->_('Анонс');
		$this->diafan->data["anons"]["value"] = ($value ? str_replace(array ( '<', '>', '"' ), array ( '&lt;', '&gt;', '&quot;' ), str_replace('&', '&amp;', $value)) : '' );
	}
	public function edit_variable_timeedit(){
		if($this->diafan->is_new){
			return;
		}
		$timeedit = $this->diafan->value ? $this->diafan->value : time();
		$this->diafan->data["timeedit"] = array("type"=>"info","name"=>$this->diafan->variable_name(),"value" => $timeedit);
	}
	
	public function edit_variable_number(){
		if ($this->diafan->is_new){
			return;
		}
		$this->diafan->data["number"] = array("type"=>"info","name"=>$this->diafan->variable_name(),"value" => 'id='.$this->diafan->id);
	}
	public function edit_variable_theme(){
		$theme = $this->diafan->values("theme");
		if($this->diafan->is_new && $this->diafan->variable_list('plus') && $this->diafan->_route->parent){
			if(! isset($this->cache["parent_row"])){
				$this->cache["parent_row"] = DB::query_fetch_array("SELECT * FROM {".$this->diafan->table."} WHERE id=%d LIMIT 1", $this->diafan->_route->parent);
			}
			if(! empty($this->cache["parent_row"]["theme"])){
				$theme = $this->cache["parent_row"]["theme"];
			}
		}
		$themes = $this->get_themes();
		$this->diafan->data["theme"] = array("name" => $this->diafan->variable_name(),"type" => "select");
		$arr[] = array("value"=>"","name"=>(! empty($themes['site.php']) ? $themes['site.php'] : 'site.php'), "status"=>0);
		foreach ($themes as $key => $value){
			if ($key == 'site.php')
				continue;
			$arr[] = array("value"=>$key,"name"=>$value, "status"=> $theme == $key);
		}
		$this->diafan->data["theme"]["value"] = $arr;
	}
	public function get_themes(){
		if(isset($this->cache["themes"])){
			return $this->cache["themes"];
		}
		$this->cache["themes"] = array();
		$rows = Custom::read_dir('themes');
		foreach($rows as $file){
			if (preg_match('!\.(php|inc)$!', $file) && is_file(ABSOLUTE_PATH.Custom::path('themes/'.$file))){
				$key = $file;
				$name = $file;
				$handle = fopen(ABSOLUTE_PATH.Custom::path('themes/'.$file), "r");
				$start = false;
				$ln = 1; 
				while (($data = fgets($handle)) !== false){
					if($ln == 1 && (strpos($data, '<?php') === 0 || (strpos($data, '<?') === 0))){
						$start = true;
						continue;
					}
					if($start && preg_match('/\*\s(.+)$/', $data, $m)){
						$name = $this->diafan->_($m[1])." [$file]";
						break;
					}
					if(preg_match('/^\</', $data)){
						break;
					}
					$ln++;
				}
				fclose($handle);
				$this->cache["themes"][$key] = $name;
			}
		}
		arsort($this->cache["themes"]);
		return $this->cache["themes"];
	}
	public function edit_variable_view(){
	    $view = $this->diafan->values("view");
	    if($this->diafan->is_new && $this->diafan->variable_list('plus') && $this->diafan->_route->parent){
	        if(! isset($this->cache["parent_row"])){
	            $this->cache["parent_row"] = DB::query_fetch_array("SELECT * FROM {".$this->diafan->table."} WHERE id=%d LIMIT 1", $this->diafan->_route->parent);
	        }
	        if(! empty($this->cache["parent_row"]["view"])){
	            $view = $this->cache["parent_row"]["view"];
	        }
	    }
	    $views = $this->get_views($this->diafan->module);
	    $default = $this->diafan->element_type();
	    switch($default){
	        case 'cat':
	        case 'brand':
	            $default = 'list';
	            break;
	        case 'element':
	            $default = 'id';
	            break;
	    }
	    $arr[] = array("value" => "", "name" => ! empty($views[$default]) ? $views[$default] : $this->diafan->module.'.view.'.$default.'.php', "status" => 0);
	    foreach ($views as $key => $value){
	        if ($key == $default){
	            continue;
	        }
	        $arr[] = array("value" => $key, "name" => $value, "status" => $view == $key);
	    }
	    $this->diafan->data["view"] = array("type" => "select", "name" => $this->diafan->variable_name(),"value" => $arr);
	}
	public function edit_variable_view_element(){
		$view_element = $this->diafan->values("view_element");
		if($this->diafan->is_new && $this->diafan->variable_list('plus') && $this->diafan->parent){
			if(! isset($this->cache["parent_row"])){
				$this->cache["parent_row"] = DB::query_fetch_array("SELECT * FROM {".$this->diafan->_frame->table."} WHERE id=%d LIMIT 1", $this->diafan->parent);
			}
			if(! empty($this->cache["parent_row"]["view_element"])){
				$view_element = $this->cache["parent_row"]["view_element"];
			}
		}
		$views = $this->get_views($this->diafan->module);
		
		$this->diafan->data["view_element"]["type"] = "select";
		$arr[] = array("value"=>"","name"=>(! empty($views['id']) ? $views['id'] : $this->diafan->module.'.view.id.php'),"status"=>false);
		
		foreach ($views as $key => $value){
			if ($key == 'id'){continue;}
			$arr[] = array("value"=>$key,"name"=>$value,"status"=>$view_element == $key);
		}
		$this->diafan->data["view_element"]["value"] = $arr;
	}
	public function get_views($module){
		if(isset($this->cache["views"][$module])){
			return $this->cache["views"][$module];
		}
		$this->cache["views"][$module] = array();
		$rows = Custom::read_dir("modules/".$module."/views");
		foreach($rows as $file){
			if (preg_match('!\.php$!', $file)
				&& is_file(ABSOLUTE_PATH.Custom::path("modules/".$module."/views/".$file)))	{
				if (! preg_match('/'.$module.'\.view\.([^\.]+)\.php/', $file, $match)){
					continue;
				}
				$key = $match[1];
				$name = $file;
				$handle = fopen(ABSOLUTE_PATH.Custom::path("modules/".$module."/views/".$file), "r");
				$start = false;
				while (($data = fgets($handle)) !== false){
					if(strpos($data, '/**') !== false){
						$start = true;
						continue;
					}
					if($start && preg_match('/\*\s(.+)$/', $data, $m)){
						$name = $this->diafan->_($m[1])." [$file]";
						break;
					}
					if(preg_match('/\*\//', $data)){
						break;
					}
				}
				fclose($handle);
				$this->cache["views"][$module][$key] = $name;
			}
		}
		arsort($this->cache["views"][$module]);
		return $this->cache["views"][$module];
	}
	public function edit_variable_admin_id(){
		if($this->diafan->is_new)
			return false;
		$value = (! $this->diafan->value
			  ? $this->diafan->_('не задан')
			  : DB::query_result("SELECT CONCAT(fio, ' (', name, ')') FROM {users} WHERE id=%d LIMIT 1", $this->diafan->value));
		$this->diafan->data["admin_id"] = array("type" => "info", "name" => $this->diafan->variable_name(), "value" => $value);
	}
	public function edit_variable_user_id(){
	    $this->diafan->data["user_id"]["type"] = "info";
	    $this->diafan->data["user_id"]["value"] = (! $this->diafan->value
			  ? $this->diafan->_('Гость')
			  : DB::query_result("SELECT CONCAT(fio, ' (', name, ')') FROM {users} WHERE id=%d LIMIT 1", $this->diafan->value));
	}
	public function edit_variable_date_period(){
		$time = "";
		$this->diafan->data["date_start"]["name"] = $this->diafan->_("Период показа от");
		$this->diafan->data["date_finish"]["name"] = $this->diafan->_("Период показа до");
		if($this->diafan->variable() == 'datetime'){
			$time = " H:i";
			$this->diafan->data["date_start"]["type"] = "datetime";
			$this->diafan->data["date_finish"]["type"] = "datetime";
		}else{
		    $this->diafan->data["date_start"]["type"] = "date";
		    $this->diafan->data["date_finish"]["type"] = "date";
		}
		$this->diafan->data["date_start"]["value"] = ($this->diafan->values("date_start") ? date("d.m.Y".$time, $this->diafan->values("date_start")) : '');
		$this->diafan->data["date_finish"]["value"] = ($this->diafan->values("date_finish") ? date("d.m.Y".$time, $this->diafan->values("date_finish")) : '');
	}
	public function edit_variable_dynamic(){
	    $element_type = $this->diafan->element_type();
	    $dynamic = DB::query_fetch_all("SELECT b.id, b.[name], b.text, b.type FROM {site_dynamic} AS b"
	        ." INNER JOIN {site_dynamic_module} AS m ON m.dynamic_id=b.id"
	        ." WHERE b.trash='0'"
	        ." AND (m.module_name='%h' OR m.module_name='') AND (m.element_type='%h' OR m.element_type='')"
	        ." GROUP BY b.id ORDER BY b.sort ASC",
	        $this->diafan->module, $element_type);
	
	    if(! $this->diafan->is_new){
	        $values = DB::query_fetch_key("SELECT dynamic_id, [value], parent, category, value".$this->diafan->_languages->site." as rv 
	            FROM {site_dynamic_element} 
	            WHERE element_id=%d AND element_type='%s' AND module_name='%s'", $this->diafan->id, $element_type, $this->diafan->module, "dynamic_id");
	    }
	    foreach($dynamic as $row){
	        $help = $this->diafan->help($row["text"]);
	        $value = (! empty($values[$row["id"]]) ? $values[$row["id"]]["value"] : '');
	        $rvalue = (! empty($values[$row["id"]]) ? $values[$row["id"]]["rv"] : '');
	        $parent = (! empty($values[$row["id"]]) ? $values[$row["id"]]["parent"] : '');
	        $category = (! empty($values[$row["id"]]) ? $values[$row["id"]]["category"] : '');
	        if($this->diafan->variable_list('plus')){
	            $this->diafan->data["dynamic_parent".$row["id"]]["type"] = array("type" => "checkbox", "value" => $parent, "name" => $this->diafan->_('Применить к вложенным элементам'));
	        }
	        if($this->diafan->config('category')){
	            $this->diafan->data["input_dynamic_category".$row["id"]]["type"] = array("type" => "checkbox", "value" => $category, "name" => $this->diafan->_('Применить к элементам категории'));
	        }
	        $row["name"] = $row["name"].' ('.$this->diafan->_('динамический блок').')';
	        switch($row["type"]){
	            case 'text':$this->show_text("dynamic".$row["id"], $row["name"], $value, $help);break;
	            case 'textarea':$this->show_textarea("dynamic".$row["id"], $row["name"], $value, $help);break;
                case 'email':$this->show_email("dynamic".$row["id"], $row["name"], $rvalue, $help);break;
                case 'date':$this->show_date("dynamic".$row["id"], $row["name"], $rvalue, $help);break;
                case 'datetime':$this->show_datetime("dynamic".$row["id"], $row["name"], $rvalue, $help);break;
                case 'numtext':$this->show_numtext("dynamic".$row["id"], $row["name"], $rvalue, $help);break;
                case 'floattext':$this->show_floattext("dynamic".$row["id"], $row["name"], $rvalue, $help);break;
	            case 'editor':$this->show_editor("dynamic".$row["id"], $row["name"], $value, $help);break;
	        }
	    }
	}
	public function edit_variable_param($where = ''){
		$values = array();
		$rvalues = array();
		$multilang = $this->diafan->variable_multilang("param");
		if (! $this->diafan->is_new){
			$rows_el = DB::query_fetch_all("SELECT value".($multilang ? $this->diafan->_languages->site." as rv, [value]" : "")
			.", param_id FROM {".$this->diafan->table."_param_element} WHERE element_id=%d", $this->diafan->id);
			foreach ($rows_el as $row_el){
				$values[$row_el["param_id"]][]  = $row_el["value"];
				if($multilang){
					$rvalues[$row_el["param_id"]][] = $row_el["rv"];
				}
			}
		}
		$options = DB::query_fetch_key_array("SELECT [name], id, param_id FROM {".$this->diafan->table."_param_select} ORDER BY sort ASC", "param_id");
		$rows = DB::query_fetch_all("SELECT p.id, p.[name], p.type, p.[text], p.config FROM {".$this->diafan->table."_param} as p "
		    ." WHERE p.trash='0'".$where." ORDER BY p.sort ASC");
		foreach ($rows as $row){
			$help = $this->diafan->help($row["text"]);
			switch($row["type"]){
				case 'title':
					$this->show_tr_title("param".$row["id"], $row["name"], $help);
					break;
				case 'text':
					$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : '');
					$this->show_text("param".$row["id"], $row["name"], $value, $help);
					break;
				case 'textarea':
					$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : '');
					$this->show_textarea("param".$row["id"], $row["name"], $value, $help);
					break;
				case 'email':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $rvalues[$row["id"]][0] : '');
					}else{
						$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : '');
					}
					$this->show_email("param".$row["id"], $row["name"], $value, $help);
					break;
				case 'phone':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $rvalues[$row["id"]][0] : '');
					}else{
						$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : '');
					}
					$this->show_phone("param".$row["id"], $row["name"], $value, $help);
					break;

				case 'date':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $this->diafan->unixdate($this->diafan->formate_from_date($rvalues[$row["id"]][0])) : '');
					}else{
						$value = (! empty($values[$row["id"]]) ? $this->diafan->unixdate($this->diafan->formate_from_date($values[$row["id"]][0])) : '');
					}
					$this->show_date("param".$row["id"], $row["name"], $value, $help);
					break;

				case 'datetime':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $this->diafan->unixdate($this->diafan->formate_from_datetime($rvalues[$row["id"]][0])) : '');
					}else{
						$value = (! empty($values[$row["id"]]) ? $this->diafan->unixdate($this->diafan->formate_from_datetime($values[$row["id"]][0])) : '');
					}
					$this->show_datetime("param".$row["id"], $row["name"], $value, $help);
					break;

				case 'numtext':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $rvalues[$row["id"]][0] : 0);
					}else{
						$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : 0);
					}
					$this->show_numtext("param".$row["id"], $row["name"], $value, $help);
					break;

				case 'floattext':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $rvalues[$row["id"]][0] : 0);
					}else{
						$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : 0);
					}
					$this->show_floattext("param".$row["id"], $row["name"], $value, $help);
					break;

				case 'checkbox':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $rvalues[$row["id"]][0] : 0);
					}else{
						$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : 0);
					}
					$this->show_checkbox("param".$row["id"], $row["name"], $value, $help);
					break;

				case 'select':
					$opts = array(array('name' => $this->diafan->_('Нет'), 'id' => ''));
					if(! empty($options[$row["id"]])){
						$opts = array_merge($opts, $options[$row["id"]]);
					}
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $rvalues[$row["id"]][0] : 0);
					}else{
						$value = (! empty($values[$row["id"]]) ? $values[$row["id"]][0] : 0);
					}
					$this->show_select("param".$row["id"], $row["name"], $value, $help, false, $opts);
					break;

				case 'multiple':
					if($multilang){
						$value = (! empty($rvalues[$row["id"]]) ? $rvalues[$row["id"]] : array());
					}else{
						$value = (! empty($values[$row["id"]]) ? $values[$row["id"]] : array());
					}
					$this->show_multiple("param".$row["id"], $row["name"], $value, $help, false, (! empty($options[$row["id"]]) ? $options[$row["id"]] : array()));
					break;

				case 'attachments':
					Custom::inc('modules/api/modules/attachments.php');
					$attachments = new Attachments_api($this->diafan);
					$attachments->edit_param($row["id"], $row["name"], $help, $row["config"]);
					break;

				case 'images':
					Custom::inc('modules/api/modules/images.php');
					$images = new Images_api($this->diafan);
					$images->edit_param($row["id"], $row["name"], $help);
					break;
			}
		}
	}
	public function edit_variable_counter_view(){
		if ($this->diafan->is_new || ! $this->diafan->configmodules("counter")){
			return;
		}
		$counter_view = DB::query_result("SELECT count_view FROM {%s_counter} WHERE element_id=%d LIMIT 1", $this->diafan->table, $this->diafan->id);
		if(! $counter_view){
			$counter_view = 0;
		}
		$this->diafan->data["counter_view"] = array("type" => "info","name" => $this->diafan->variable_name(),"value" => $counter_view);
	}
	public function edit_variable_descr(){
	    $this->diafan->data["descr"] = array("type" => "textarea","name" => $this->diafan->variable_name(),"value" => ( $this->diafan->value ? str_replace(array ('<', '>', '"'), array('&lt;', '&gt;', '&quot;'), $this->diafan->value) : '' ),"disabled"=>$this->diafan->variable_disabled());
	}
	public function edit_variable_changefreq(){
	    $arr[] = array("value"=>"monthly","name"=>"monthly","status" => false);
	    $arr[] = array("value"=>"always","name"=>"always","status" => $this->diafan->value == 'always');
	    $arr[] = array("value"=>"hourly","name"=>"hourly","status" => $this->diafan->value == 'hourly');
	    $arr[] = array("value"=>"daily","name"=>"daily","status" => $this->diafan->value == 'daily');
	    $arr[] = array("value"=>"weekly","name"=>"weekly","status" => $this->diafan->value == 'weekly');
	    $arr[] = array("value"=>"yearly","name"=>"yearly","status" => $this->diafan->value == 'yearly');
	    $arr[] = array("value"=>"never","name"=>"never","status" => $this->diafan->value == 'never');
	    $this->diafan->data["changefreq"] = array("type" => "select", "name" => $this->diafan->variable_name(),"value" => $arr);
	}
	public function show_tr_title($key, $name, $help){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"title","help"=>$help);
	}
	public function show_password($key, $name, $value, $help, $disabled = false){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"password","value"=>$value,"help"=>$help,"disabled" => $disabled);
	}
	public function show_text($key, $name, $value, $help, $disabled = false, $maxlength = 0){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"text","value"=>$value,"help"=>$help,"disabled" => $disabled,"maxlength"=>$maxlength);
	}
	public function show_email($key, $name, $value, $help, $disabled = false){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"email","value"=>($value ? str_replace('"', '&quot;', $value) : ''),"help"=>$help,"disabled" => $disabled);
	}
	public function show_phone($key, $name, $value, $help, $disabled = false){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"phone","value"=>($value ? str_replace('"', '&quot;', $value) : ''),"help"=>$help,"disabled" => $disabled);
	}
	public function show_date($key, $name, $value, $help, $disabled = false){
	    if($value){
	        $value = date("d.m.Y", $value);
	    }elseif($this->diafan->is_new){
	        $value = date("d.m.Y");
	    }else{
	        $value = '';
	    }
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"date","value"=>$value,"help"=>$help,"disabled" => $disabled);
	}
	public function show_datetime($key, $name, $value, $help, $disabled = false){
	    if($value){
	        $value = date("d.m.Y H:i", $value);
	    }elseif($this->diafan->is_new){
	        $value = date("d.m.Y H:i");
	    }else{
	        $value = '';
	    }
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"datetime","value"=>$value,"help"=>$help,"disabled" => $disabled);
	}
	public function show_numtext($key, $name, $value, $help, $disabled = false){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"numtext","value"=>$value,"help"=>$help,"disabled" => $disabled);
	}
	public function show_floattext($key, $name, $value, $help, $disabled = false){
	    if(($value * 10) % 10){
	        $num_decimal_places = 2;
	    }else{
	        $num_decimal_places = 0;
	    }
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"floattext","value"=>( $value ? number_format($value, $num_decimal_places, ',', '') : '' ),"help"=>$help,"disabled" => $disabled);
	}
	public function show_textarea($key, $name, $value, $help, $disabled = false, $maxlength = 0){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"textarea","value"=>$value,"help"=>$help,"disabled" => $disabled,"maxlength"=>$maxlength);
	}
	public function show_checkbox($key, $name, $value, $help, $disabled = false){
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"checkbox","value"=>$value,"help"=>$help,"disabled" => $disabled);
	}
	public function show_select($key, $name, $value, $help, $disabled = false, $options = array()){
	    if (! $options){return;}
	    $v = array();
	    foreach ($options as $k => $select){
	        if(is_array($select)){
	            $k = $select["id"];
	            $select = $select["name"];
	        }
	        $v[] = array("value"=>$k, "name"=>$this->diafan->_($select), "status" => $value == $k);
	    }
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"select","value"=>$v,"help"=>$help,"disabled" => $disabled);
	}
	public function show_select_arr($key, $name, $value, $help, $disabled = false, $options = array()){
	    $this->show_select($key, $name, $value, $help, $disabled, $options);
	}
	public function show_multiple($key, $name, $values, $help, $disabled = false, $options = array()){
	    foreach ($values as &$val){
	        if(!$val){
	            unset($val);
	        }
	    }
	    foreach ($options as $k => $select){
	        if(is_array($select)){
	            $k = $select["id"];
	            $select = $select["name"];
	        }
	        $value[] = array("value"=>$k, "name"=>$this->diafan->_($select), "status" => in_array($k, $values));
	    }
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"multiple","value"=>$value,"help"=>$help,"disabled" => $disabled);
	}
	public function show_editor($key, $name, $value, $help){
	    $value = $this->diafan->_route->replace_id_to_link($value);
	    $this->diafan->data[$key] = array("name"=>$name,"type"=>"editor","value"=>( $value ? str_replace(array ( '<', '>', '"' ), array ( '&lt;', '&gt;', '&quot;' ), str_replace('&', '&amp;', $value)) : '' ),"help"=>$help);
	}
	/**
	 * Получает значение поля
	 * @param string $field название поля
	 * @param mixed $default значение по умолчанию
	 * @param boolean $save записать значение по умолчанию
	 * @return mixed
	 */
	public function values($field, $default = false, $save = false)
	{
	    if(! isset($this->cache["oldrow"]))
	    {
	        $values = $this->diafan->get_values();
	
	        if ($this->diafan->config("config"))
	        {
	            foreach ($this->diafan->variables as $title => $variable_table)
	            {
	                foreach ($variable_table as $k => $v)
	                {
	                    if ( empty($values[$k]))
	                    {
	                        $values[$k] = $this->diafan->configmodules($k);
	                    }
	                }
	            }
	        }
	        elseif($this->diafan->is_new)
	        {
	            foreach ($this->diafan->variables as $title => $variable_table)
	            {
	                foreach ($variable_table as $k => $v)
	                {
	                    if (! empty($this->diafan->get_nav_params['filter_'.$k]) && $this->diafan->variable_filter($k) != 'text')
	                    {
	                        $values[$k.(! empty($v["multilang"]) ? _LANG : '')] = $this->diafan->get_nav_params['filter_'.$k];
	                    }
	                    elseif(! empty($v["default"]))
	                    {
	                        $values[$k._LANG] = $v["default"];
	                    }
	                }
	            }
	        }
	        elseif (! $values)
	        {
	            $values = DB::query_fetch_array("SELECT * FROM {".$this->diafan->table."} WHERE id=%d"
	                .($this->diafan->variable_list('actions', 'trash') ? " AND trash='0'" : '' )." LIMIT 1",
	                $this->diafan->id
	                );
	            if (empty($values))
	            {
	                ob_end_clean();
	                Custom::inc('includes/404.php');
	            }
	        }
	        $this->cache["oldrow"] = $values;
	    }
	
	    $field .= ($this->diafan->variable_multilang($field) && ! $this->diafan->config("config") ? _LANG : '');
	
	    if(! isset($this->cache["oldrow"][$field]))
	    {
	        switch($field)
	        {
	            case 'parent_id':
	                if ($this->diafan->is_new)
	                {
	                    $this->cache["oldrow"]["parent_id"] = $this->diafan->_route->parent;
	                }
	                break;
	
	            case 'cat_id':
	                if ($this->diafan->is_new)
	                {
	                    $this->cache["oldrow"]["cat_id"] = $this->diafan->_route->cat;
	                }
	                break;
	
	            case 'site_id':
	                if($this->diafan->table == 'site')
	                {
	                    $this->cache["oldrow"]["site_id"] = $this->diafan->id;
	                }
	                else
	                {
	                    if(empty($this->cache["oldrow"]["site_id"]))
	                    {
	                        $this->cache["oldrow"]["site_id"] = $this->diafan->_route->site;
	                    }
	                    if(empty($this->cache["oldrow"]["site_id"]))
	                    {
	                        $this->cache["oldrow"]["site_id"] = DB::query_result("SELECT id FROM {site} WHERE module_name='%s' AND trash='0'", $this->diafan->_admin->module);
	                    }
	                }
	                break;
	        }
	    }
	    if(! isset($this->cache["oldrow"][$field]))
	    {
	        if(! $default)
	        {
	            $default = $this->diafan->variable($field, 'default');
	        }
	        if($default)
	        {
	            if($save)
	            {
	                $this->cache["oldrow"][$field] = $default;
	            }
	            else
	            {
	                return $default;
	            }
	        }
	        elseif ($this->diafan->config("config"))
	        {
	            $this->cache["oldrow"][$field] = $this->diafan->configmodules($field);
	        }
	    }
	    if(isset($this->cache["oldrow"][$field]))
	    {
	        return $this->cache["oldrow"][$field];
	    }
	    else
	    {
	        return false;
	    }
	    return $this->cache["site_id"];
	}
	/**
	 * Получает значения полей для формы (альтернативный метод)
	 *
	 * @return array
	 */
	public function get_values()
	{
	    return array ();
	}
	/**
	 * Определяет подсказки для полей
	 *
	 * @param string $key название текущего поля или текст подсказки
	 * @return string
	 */
	public function help($key = '')
	{
	    if (! $key)
	    {
	        $key = $this->diafan->key;
	    }
	    if(! $this->diafan->is_variable($key))
	    {
	        $help = $key;
	        $key = rand(0, 3333);
	    }
	    elseif (! $help = $this->diafan->variable($key, 'help'))
	    {
	        return '';
	    }
	
	    return $this->diafan->_($help);
	}
}
