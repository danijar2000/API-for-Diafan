<?php
if ( ! defined('DIAFAN')){
	$path = __FILE__; $i = 0;
	while(! file_exists($path.'/includes/404.php'))
	{
		if($i == 10) exit; $i++;
		$path = dirname($path);
	}
	include $path.'/includes/404.php';
}
class Api_model extends Model{
    var $version_api = '1.0';
    var $token = '';
    
    var $rewrite = '';
    var $module = '';
    var $site_id = 0;
    var $cat_id = 0;
    var $parent = 0;
    var $rows_id = array();
    
    //Edit
    var $id = 0;
    var $is_new = false;
    
    //Paginator
    var $page;
    var $limit = 10;
    var $offset = 0;
    var $count = 0;
    var $data = array();
	//Error
	var $error_code = "";
	var $error = "Нет ошибок";
	
	var $_frame;
	var $_functions;
	var $icons = array(
        'site'=>'files_o','menu'=>'list','news'=>'newspaper_o',
        'clauses'=>'file_text_o','photo'=>'file_image_o','files'=>'folder_open_o',
        'bs'=>'ticket','ab'=>'sticky_note_o','tags'=>'tags',
        'shop'=>'tags','shop_order'=>'shopping_cart','shop_delivery'=>'check_square_o',
        'shop-ordercount'=>'line_chart','payment'=>'credit_card','line_chart'=>'shop_ordercount',
        'group'=>'users','forum'=>'comments_o','faq'=>'question_circle_o',
        'feedback'=>'reply','votes'=>'industry','subscribtion'=>'envelope_o',
        'rating'=>'star_o','geomap'=>'map_marker','comments'=>'comment_o',
        'mistakes'=>'bug','consultant'=>'mortar_board','keywords'=>'chain',
        'filemanager'=>'file_code_o','custom'=>'file_photo_o','update'=>'cloud_download',
        'search'=>'search','config'=>'gears','service'=>'service','images'=>'object_ungroup',
        'captcha'=>'underline','languages'=>'globe','trash'=>'trash','gear'=>'cog',
	    
	    'shop-delivery' => 'shopping_basket','shop-order' => 'shopping_cart',
	);
	private $action_object;
	public $cache;
	
	public function __get($name){
	    if (! isset($this->cache["var"][$name])){
			switch($name){
				case 'installed_modules':
				case 'title_modules':
				case 'all_modules':
					$this->cache["var"]["installed_modules"] = array();
					$this->cache["var"]["title_modules"] = array();
					$this->cache["var"]["all_modules"] = DB::query_fetch_all("SELECT * FROM {modules} ORDER BY id ASC");
					foreach($this->cache["var"]["all_modules"] as $m){
						$this->cache["var"]["title_modules"][$m["name"]] = $m["title"];
						$this->cache["var"]["installed_modules"][] = $m["name"];
					}
					break;
				case 'admin_pages':
					$this->cache["var"]["admin_pages"] = DB::query_fetch_key_array("SELECT id, name, rewrite, group_id, parent_id, `add`, add_name FROM {admin} WHERE act='1' ORDER BY sort ASC", "parent_id");
					break;
				case '_cache':
					Custom::inc('includes/cache.php');
					$this->cache["var"][$name] = new Cache;
					break;
				case '_route':
					Custom::inc('includes/route.php');
					$this->cache["var"][$name] = new Route($this);
					break;
				case '_tpl':
				    Custom::inc('includes/template.php');
				    $this->cache["var"][$name] = new Template($this);
				    break;
				
				case '_parser_theme':
				    Custom::inc('includes/parser_theme.php');
				    $this->cache["var"][$name] = new Parser_theme($this);
				    break;
				case '_users':
				    $this->cache["var"][$name] = $this->diafan->_users;
				    break;
				default:
					if (substr($name, 0, 1) == '_'){
						$module = substr($name, 1);
						if (Custom::exists('modules/'.$module.'/'.$module.'.inc.php')){
							Custom::inc('includes/model.php');
							Custom::inc('modules/'.$module.'/'.$module.'.inc.php');
							$class = ucfirst($module).'_inc';
							$this->cache["var"][$name] = new $class($this);
						}
					}else{
					    if(property_exists($this->_frame, $name)){
					        return $this->_frame->$name;
					    }elseif(property_exists($this->action_object, $name)){
					        return $this->action_object->$name;
					    }else{
					        return false;
					    }
					}
					break;
			}
		}
		return $this->cache["var"][$name];
	}
	public function __call($name, $arguments){
	    if(method_exists($this->action_object, $name)){
	        return call_user_func_array(array(&$this->action_object, $name), $arguments);
	    }
	    if (method_exists($this->_frame, $name)){
	        return call_user_func_array(array(&$this->_frame, $name), $arguments);
	    }
	    if (method_exists($this->diafan, $name)){
	        return call_user_func_array(array(&$this->diafan, $name), $arguments);
	    }
	    if (method_exists($this->_functions, $name)){
	        return call_user_func_array(array(&$this->_functions, $name), $arguments);
	    }
	    return 'fail_function';
	}
	public function getInfo(){
	    if(!$this->diafan->_users->id && isset($_REQUEST['s'])){
	        $this->authQR();
	    }
	    if(file_exists(ABSOLUTE_PATH.USERFILES.'/avatar/'.$this->diafan->_users->name.'.png')){
	        $this->data['avatar'] = BASE_PATH.USERFILES.'/avatar/'.$this->diafan->_users->name.'.png';
	    }else{
	        $this->data['avatar'] = BASE_PATH.USERFILES.'/avatar_none.png';
	    }
	    $this->data['site_name'] = constant('TIT'._LANG);
	    $this->data['user_id'] = $this->diafan->_users->id;
	    $this->data['user_email'] = $this->diafan->_users->mail;
	    $this->data['user_name'] = $this->diafan->_users->__get('fio');
	    $this->data['name_cms'] = "DIAFAN";
	    $this->data['version_cms'] = VERSION_CMS;
	    $this->data['version_api'] = $this->version_api;
	}
	public function setRestore(){
	    $this->getFrame();
	    Custom::inc("adm/includes/del.php");
	    $this->action_object = new Del_admin($this->diafan);
	    $this->_users->checked = true;
	    $this->diafan->restore();
	}
	public function setAct(){
	    $this->getFrame();
	    Custom::inc("adm/includes/act.php");
	    $this->action_object = new Act_admin($this);
	    $this->_users->checked = true;
	    $this->act();
	}
	public function setDelete(){
	    $this->getFrame();
	    Custom::inc("adm/includes/del.php");
	    $this->action_object = new Del_admin($this);
	    $this->_users->checked = true;
	    $this->del();
	}
	public function setSave(){
	    $this->getFrame();
	    Custom::inc("adm/includes/save.php");
	    $this->action_object = new Save_admin($this);
	    $this->set_get_nav();
	    Custom::inc("adm/includes/save_functions.php");
	    $this->_functions = new Save_functions_admin($this);
	    $this->_users->checked = true;
	    $this->save();
	}
	public function getEdit(){
	    $this->getFrame();
	    if(isset($_REQUEST['id'])){
	        $this->id = $_REQUEST['id'];
	    }else{
	        $this->is_new = true;
	        $this->id = 0;
	    }
	    Custom::inc("adm/includes/theme.php");
	    Custom::inc("adm/includes/edit.php");
	    Custom::inc("modules/api/functions/edit.php");
	    $this->action_object = new Edit_admin_api($this);
	    if($this->is_variable("admin_id") && $this->values("admin_id")
	        && $this->values("admin_id") != $this->diafan->_users->id
	        && DB::query_result("SELECT only_self FROM {users_role} WHERE id=%d LIMIT 1", $this->diafan->_users->role_id)){
	        $this->error_code = "not_access";
	        $this->error = $this->diafan->_('У вас нету доступа.', false);
	        $this->out();
	    }
	    foreach ($this->_frame->variables as $table_type => $variable_table){
	        $this->show_table($variable_table);
	    }
	    $data = array();
	    foreach ($this->data as $name => $value){
	        $data[] = array_merge(array("n"=>$name),$value);
	    }
	    $this->data = $data;
	}
	public function getList(){
	    $this->getFrame();
	    Custom::inc("adm/includes/show.php");
	    Custom::inc("modules/api/functions/show.php");
	    $this->action_object = new Show_admin_api($this);
	    $this->prepare_config();
	    $this->prepare_variables();
		$this->set_get_nav();
		$this->list_row(0);
	}
	private function getFrame(){
	    $this->rewrite = $_REQUEST['r'];
	    if (! $this->diafan->_users->roles('init', $this->rewrite) && $this->diafan->_users->id){
	        $this->error_code = "not_access";
	        $this->error = $this->diafan->_('У вас нету доступа.', false);
	        $this->out();
	    }
	    define('_SHORTNAME',  '' );
	    define('URL', $this->get_admin_url());
	    Custom::inc("adm/includes/frame.php");
	    if (strpos($this->rewrite, '/') !== false){
	        $rew = explode('/', $this->rewrite);
	        foreach ($rew as $k => $v){
	            $v = preg_replace('/[^A-Za-z0-9\-\_]+/', '', $v);
	            if ($k == 0){
	                $rewrite = $v;
	                $module = $v.'.admin';
	            }else{
	                $module .= '.'.$v;
	            }
	        }
	    }else{
	        $rewrite = preg_replace('/[^A-Za-z0-9\-\_]+/', '', $this->rewrite);
	        $module = $rewrite.'.admin';
	    }
	    if(in_array($rewrite, $this->installed_modules) 
	        && Custom::exists('modules/'.$rewrite.'/admin/'.$module.'.php') 
	        && $this->diafan->_users->id){
	        if (!empty( $_GET["parent"] )){
	            $this->_route->parent = preg_replace('/[^0-9]+/', '', $_GET["parent"]);
	        }
	        Custom::inc('modules/'.$rewrite.'/admin/'.$module.'.php');
	        $this->module = $rewrite;
	        $this->current_module = $rewrite;
	        $this->_admin->module = $rewrite;
	        $this->_admin->rewrite = $this->rewrite;
	        $this->title_module = (! empty($this->title_modules[$rewrite]) ? $this->title_modules[$rewrite] : '');
	    }
	    $name_class_module = str_replace('.', '_', ucfirst($module));
	    $name_func_module = 'inc_file_'.$rewrite;
	    if (function_exists($name_func_module)){
	        $name_class_module = $name_func_module($this);
	    }
	    if (in_array($name_class_module, get_declared_classes())){
	        $this->_frame = new $name_class_module($this);
	    }else{
	        $this->_frame = new Frame_admin($this);
	    }
	    $this->legacy();
	}
	
	public function getMenus(){
	    $cache_meta = array(
	        "name" => "getMenus",
	        "date" => date("Y.m.d"),
	        "role_id" => $this->_users->role_id,
	    );
	    $menus = unserialize($this->diafan->configmodules("menus"));
	    
	    if (is_array($menus) && !$this->data = $this->diafan->_cache->get($cache_meta, $this->diafan->module)){
	        $result = DB::query_fetch_all("SELECT id, group_id, name, rewrite
	           FROM {admin} 
	           WHERE act='1' AND parent_id = '0' AND rewrite IN('".implode("','",$menus)."') 
	            AND rewrite NOT LIKE '%/counter' AND rewrite NOT LIKE '%/config'
	           ORDER BY group_id ASC, sort ASC");
	        $ids = array();
	        foreach ($result as $row){
	            $ids[] = $row['id'];
	        }
	        $subs = DB::query_fetch_all("SELECT parent_id,  name, rewrite
	           FROM {admin}
	           WHERE act='1' AND parent_id IN (".implode(',',$ids).") AND rewrite NOT LIKE '%/counter' AND rewrite NOT LIKE '%/config'
	           ORDER BY sort ASC");
	        $groups = array (
	            1 => array('name' => $this->diafan->_('Контент',false)),
	            4 => array('name' => $this->diafan->_('Интернет магазин',false)),
	            2 => array('name' => $this->diafan->_('Интерактив',false)),
	            3 => array('name' => $this->diafan->_('Сервис',false)),
	            5 => array('name' => $this->diafan->_('Настройки',false)),
	        );
	        foreach ($result as $row){
	            if (! $this->diafan->_users->roles('init', $row["rewrite"])){
	                continue;
	            }
	            $row["site_id"] = 0;
	            if($this->diafan->configmodules("admin_page", $row["rewrite"])){
	                $rows_sites = DB::query_fetch_all("SELECT id, name".$this->diafan->_languages->site." AS name 
	                    FROM {site} 
	                    WHERE trash='0' AND act".$this->diafan->_languages->site."='1' AND module_name='%s'", $row["rewrite"]);
                    foreach ($rows_sites as $row_site){
                        $row["name"] = $row_site["name"];
                        $row["site_id"] = $row_site["id"];
                        $groups[$row["group_id"]]['items'][] = $row;
                    }
	            }else{
	                $row["name"] = $this->diafan->_($row["name"],false);
	                $groups[$row["group_id"]]['items'][] = $row;
	            }
	        }
	        Custom::inc("adm/includes/frame.php");
	        $g_id = 0;
	        foreach ($groups as $group_id => $items){
	            if(!empty($items['items'])){
                    $this->data[$g_id]['name'] = $items['name'];
    	            foreach ($items['items'] as $row){
    	                $data = array();
    	                if(strpos($row["rewrite"], '/') !== false){
    	                    list($module, $file) = explode('/', $row["rewrite"], 2);
    	                }else{
    	                    $module = $row["rewrite"];
    	                    $file = '';
    	                }
    	                $count = 0;
    	                if(Custom::exists('modules/'.$module.'/admin/'.$module.'.admin'.($file ? '.'.$file : '').'.count.php')){
    	                    Custom::inc('modules/'.$module.'/admin/'.$module.'.admin'.($file ? '.'.$file : '').'.count.php');
    	                    $class = ucfirst($module).'_admin'.($file ? '_'.$file : '').'_count';
    	                    if (method_exists($class, 'count')){
    	                        $class_count_menu = new $class($this->diafan);
    	                        $count = $class_count_menu->count($row["site_id"]);
    	                    }
    	                }
    	                //$data['id'] = $row["id"];
    	                $data['r'] = $row["rewrite"];
    	                $data['site_id'] = $row["site_id"];
    	                $data['name'] = $row["name"];
    	                $data['count'] = $count;
    	                $data['view'] = 'list';
    	                if(Custom::exists('modules/'.$module.'/admin/'.$module.'.admin'.($file ? '.'.$file : '').'.php')){
    	                    Custom::inc('modules/'.$module.'/admin/'.$module.'.admin'.($file ? '.'.$file : '').'.php');
    	                    $class = ucfirst($module).'_admin'.($file ? '_'.$file : '');
    	                    if(class_exists($class)){
    	                        $class = new $class($this->diafan);
    	                        $data['view'] = $class->table ? 'list':'edit';
    	                    }
    	                }
    	                $link = str_replace('/', '-', $row["rewrite"]);
    	                $data['icon'] = "faw_".(isset($this->icons[$link]) ? $this->icons[$link] : $link);
    	                foreach ($subs as $sub){
    	                    if($row['id'] == $sub['parent_id']){
    	                        if (! $this->diafan->_users->roles('init', $sub["rewrite"])){
    	                            continue;
    	                        }
    	                        if(strpos($sub["rewrite"], '/') !== false){
    	                            list($smodule, $sfile) = explode('/', $sub["rewrite"], 2);
    	                        }else{
    	                            $smodule = $row["rewrite"];
    	                            $sfile = '';
    	                        }
    	                        $sub['site_id'] = $row['site_id'];
    	                        $sub['name'] = $this->diafan->_($sub['name'],false);
    	                        $sub['view'] = 'edit';
    	                        if(Custom::exists('modules/'.$module.'/admin/'.$module.'.admin'.($sfile ? '.'.$sfile : '').'.php')){
    	                            Custom::inc('modules/'.$module.'/admin/'.$module.'.admin'.($sfile ? '.'.$sfile : '').'.php');
    	                            $class = ucfirst($module).'_admin'.($sfile ? '_'.$sfile : '');
    	                            if(class_exists($class)){
    	                                $class = new $class($this->diafan);
    	                                $sub['view'] = $class->table ? 'list':'edit';
    	                            }
    	                        }
    	                        $sub['r'] = $sub['rewrite'];
    	                        unset($sub['rewrite']);
    	                        unset($sub['parent_id']);
    	                        $data['sub'][] = $sub;
    	                    }
    	                }
    	                $this->data[$g_id]['items'][] = $data;
    	            }
    	            $g_id++;
	            }
	        }
	       $this->diafan->_cache->save($this->data, $cache_meta, $this->diafan->_site->module);
	    }
	}
	public function getTopMenus(){
	    $result = DB::query_fetch_all("SELECT rewrite, add_name as name FROM {admin} WHERE act='1' AND `add` = '1' AND  add_name != '' ORDER BY sort ASC");
	    foreach ($result as $row){
	        $data = array('name'=>$row['name'],'view'=>'edit');
	        $data['rewrite'] = $row['rewrite'];
	        $data['icon'] = "fa-".(isset($this->icons[$row['rewrite']]) ? $this->icons[$row['rewrite']] : $row['rewrite']);
	        $this->data[] = $data;
	    }
	}
	public function getLanguages(){
	    $result = DB::query_fetch_all("SELECT * FROM {languages} ORDER BY base_admin DESC, id ASC");
	    foreach ($result as $row){
	        $this->data[] = $row;
	    }
	}
	public function chek(){
	    if(isset($_REQUEST["r"])){$this->rewrite = $_REQUEST["r"];}
	    if(isset($_REQUEST['site_id'])){$this->site_id = $_REQUEST['site_id'];}
	    if(isset($_REQUEST['category_id'])){$this->cat_id = $_REQUEST['category_id'];}
        if(isset($_REQUEST['limit'])){$this->limit = $_REQUEST['limit'];}
        if(isset($_REQUEST['offset'])){$this->offset = $_REQUEST['offset'];}
        if(isset($_REQUEST['id'])){$this->id = $_REQUEST['id'];}
	}
	public function out(){
	    if($this->diafan->_users->id){
    	    $result = array(
    	        'error_code' => $this->error_code, 
    	        'error' => $this->error, 
    	    );
	    }else {
	        $result = array(
	            'error_code' => "token_wrong",
	            'error' => $this->diafan->_('Вы не авторизованы.', false),
	        );
	    }
	    if($this->offset >= 1)         {$result['offset'] = $this->offset;             }
	    if($this->count >= 1)          {$result['limit'] = $this->limit;               }
	    if($this->count >= 1)          {$result['count'] = $this->count;               }
	    if(isset($this->title_module)) {$result['title_module'] = $this->title_module; }
	    if($this->id >= 1)             {$result['id'] = $this->id;                     }
	    if(count($this->data) >= 1)    {$result['data'] = $this->data;                 }
	    if (defined('MOD_DEVELOPER_PROFILING') && MOD_DEVELOPER_PROFILING){
	        $result['sql'] = DB::query_fetch_all("SHOW PROFILES");
	    }
	    $result = json_encode($result);
	    header('Content-Type: application/json');
	    echo $result;
	    exit();
	}
	public function setAuth(){
	    if (isset($_REQUEST['name']) && isset($_REQUEST['pass'])){
	        $this->authLogin();
	    }elseif(isset($_REQUEST['s'])){
	        $this->authQR();
	    }
	}
	private function authQR(){
	    $user_id = DB::query_result('SELECT user_id FROM {sessions} WHERE session_id = "%h"',$_REQUEST['s']);
	    $admin_role_ids = DB::query_fetch_value("SELECT DISTINCT(role_id) FROM {users_role_perm} WHERE type='admin'", "role_id");
	    $result_sql = DB::query("SELECT * FROM {users} u WHERE trash='0' AND act='1' ".($admin_role_ids ? " AND u.role_id IN(".implode(',', $admin_role_ids).")" :'')." AND id = %d", $user_id);
	    if (DB::num_rows($result_sql)){
	        $user = DB::fetch_object($result_sql);
	        $this->diafan->_users->set($user);
	        $this->diafan->_session->duration();
	        $this->data = array('user_email'=>$user->mail,
	            'user_name' => $user->fio,'user_id'=>$user->id,
	            'token' => session_id(),
	        );
	        $d = dir(ABSOLUTE_PATH.'cache');
	        while ($entry = $d->read()){
	            if (substr($entry,0,6) == "qrcode"){
	                File::delete_file('cache/'.$entry);
	            }
	        }
	    }else{
	        $this->error_code = 'wrong_login_or_pass';
	        $this->error = $this->diafan->_('Неверный QR код.', false);
	    }
	}
	private function authLogin(){
	    if (! $_REQUEST['name'] || ! $_REQUEST['pass']){
	        $this->error_code = 'wrong_login_or_pass';
	        if($this->diafan->configmodules("mail_as_login", "users")){
	            $this->error = $this->diafan->_('Неверный e-mail или пароль.', false);
	        }else{
	            $this->error = $this->diafan->_('Неверный логин или пароль.', false);
	        }
	        $this->out();
	    }
	    $name = ($this->diafan->configmodules("mail_as_login", "users") ? "mail" : "name");
	    if (DB::query_result("SELECT id FROM {users} WHERE trash='0' AND act='0' AND LOWER(".$name.")=LOWER('%s') LIMIT 1", trim($_REQUEST['name']))){
	        $this->error_code = 'blocked';
	        $this->error = $this->diafan->_('Логин не активирован или заблокирован.', false);
	        $this->out();
	    }
	    
	    if ($this->_log()){
	        $this->error_code = 'blocked_30_min';
	        $this->error = $this->diafan->_('Вы превысили количество попыток, поэтому будете заблокированы на 30 минут', false);
	        $this->out();
	    }
	    // роли пользователей, имеющих доступ к администрированию
	    $admin_role_ids = DB::query_fetch_value("SELECT DISTINCT(role_id) FROM {users_role_perm} WHERE type='admin'", "role_id");
	     
	    $result_sql = DB::query("SELECT * FROM {users} u WHERE trash='0' AND act='1' ".($admin_role_ids ? " AND u.role_id IN(".implode(',', $admin_role_ids).")" :'')." AND LOWER(".$name.")=LOWER('%s') AND password='%s'", trim($_REQUEST['name']), encrypt(trim($_REQUEST['pass'])));
	    if (DB::num_rows($result_sql)){
	        $user = DB::fetch_object($result_sql);
	        $this->diafan->_users->set($user);
	        $this->diafan->_session->duration();
	        if(isset($_COOKIE['dev'])){
	            unset($_COOKIE['dev']);
	        }
	        
	        $this->data = array('user_email'=>$user->mail,
	            'user_name' => $user->fio,'user_id'=>$user->id,
	            'token' => session_id(),
	        );
	    }else{
	        $this->update_log();
	        $this->error_code = 'wrong_login_or_pass';
	        $this->error = $this->diafan->_('Неверный логин или пароль.', false);
	    }
	}
	public function setLogout(){
	    $this->diafan->_session->destroy($this->token);
	    $this->out();
	}
	private function _log(){
	    DB::query("DELETE FROM {log} WHERE created<%d", time());
	    $ip = getenv('REMOTE_ADDR');
	    if($row = DB::query_fetch_array("SELECT `count` FROM {log} WHERE ip='%h' LIMIT 1", $ip)) {
	        if ($row['count'] > 4){
	            return true;
	        }
	    }
	    return false;
	}
	private function update_log(){
	    $ip = getenv('REMOTE_ADDR');
	    $date = time() + 1800;
	    $result = DB::query("SELECT `count` FROM {log} WHERE ip='%h'", $ip);
	    if (DB::num_rows($result) > 0){
	        DB::query("UPDATE {log} SET `count`=`count`+1, created=%d WHERE ip='%h'", $date, $ip);
	    }else{
	        DB::query("INSERT INTO {log} (ip, created, info) VALUES ('%s', '%d', '%s')", $ip, $date, getenv('HTTP_USER_AGENT'));
	    }
	    DB::free_result($result);
	}
	/**
	 * Поддержка старого синтаксиса
	 * @return void
	 */
	private function legacy()
	{
	    if(! empty($this->text_for_base_link))
	    {
	        if(isset($this->text_for_base_link["text"]))
	        {
	            $this->variable_list('name', 'text', $this->text_for_base_link["text"]);
	        }
	        if(isset($this->text_for_base_link["variable"]))
	        {
	            $this->variable_list('name', 'variable', $this->text_for_base_link["variable"]);
	            if($this->text_for_base_link["variable"])
	            {
	                $this->variable_list($this->text_for_base_link["variable"], 'sql', true);
	                if($this->text_for_base_link["variable"] != 'name')
	                {
	                    $this->variable_list($this->text_for_base_link["variable"], 'type', 'none');
	                }
	            }
	        }
	    }
	
	    if($this->variable_list('name'))
	    {
	         
	        if(! $this->variable_list('name', 'variable') && ! $this->variable_list('name', 'text'))
	        {
	            $this->variable_list('name', 'sql', true);
	        }
	        if($this->variable_list('name', 'variable'))
	        {
	            $this->variable_list($this->variable_list('name', 'variable'), 'sql', true);
	            if($this->variable_list('name', 'variable') != 'name')
	            {
	                $this->variable_list($this->variable_list('name', 'variable'), 'type', 'none');
	            }
	        }
	    }
	    if(! empty($this->select_arr))
	    {
	        foreach($this->select_arr as $k => $arr)
	        {
	            if($this->variable($k, 'type') == 'select')
	            {
	                $this->variable($k, 'select', $arr);
	            }
	        }
	    }
	    if(! empty($this->select))
	    {
	        foreach($this->select as $k => $arr)
	        {
	            if($this->variable($k, 'type') == 'select')
	            {
	                $newarr = array();
	                if(! empty($arr[0]))
	                {
	                    $newarr["table"] = $arr[0];
	                }
	                if(! empty($arr[1]))
	                {
	                    $newarr["id"] = $arr[1];
	                }
	                if(! empty($arr[2]))
	                {
	                    $newarr["name"] = $arr[2];
	                }
	                if(! empty($arr[4]))
	                {
	                    $newarr["empty"] = $arr[4];
	                }
	                if(! empty($arr[5]))
	                {
	                    $newarr["where"] = $arr[5];
	                }
	                if(! empty($arr[6]))
	                {
	                    $newarr["hierarchy"] = true;
	                }
	                if(! empty($arr[7]))
	                {
	                    $newarr["order"] = $arr[7];
	                }
	                $this->variable($k, 'select_db', $newarr);
	            }
	        }
	    }
	    if(! empty($this->show_tr_click_checkbox))
	    {
	        foreach($this->show_tr_click_checkbox as $k => $arr)
	        {
	            foreach($arr as $v)
	            {
	                $d = $this->diafan->variable($v, 'depend');
	                $this->diafan->variable($v, 'depend', ($d ? $d.',' : '').$k);
	            }
	        }
	    }
	}
	public function _($name, $useradmin = true){return $this->diafan->_($name,false);}
	public function redirect($url = '', $http_response_code = 302){$this->out();}
}
