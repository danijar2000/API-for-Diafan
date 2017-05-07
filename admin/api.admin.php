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
class Api_admin extends Frame_admin
{
    public $url;
    public $variables = array (
        'base' => array (
            'url' => array(
                'type' => 'function',
                'name' => 'URL',
                'help' => 'URL используется для авторизации приложении.',
            ),
            'qr' => array(
                'type' => 'function',
                'name' => 'QR',
                'help' => 'QR для быстрого авторизации в приложении.',
            ),
        ),
        'menu' => array (
            'menus' => array(
                'type' => 'function',
                'name' => 'Меню',
                'help' => 'Отображать меню в приложении',
            ),
        ),
        'additional' => array (
            'hr2' => array(
                'type' => 'title',
                'name' => 'Список',
            ),
            'show_counter' => array(
                'type' => 'checkbox',
                'name' => 'Показывать количество просмотров',
            ),
            'show_action' => array(
                'type' => 'checkbox',
                'name' => 'Показывать колонку "Действий" (сделать активным, удалить, ...)',
            ),
            'show_sort' => array(
                'type' => 'checkbox',
                'name' => 'Показывать сортировку',
            ),
            'hr3' => array(
                'type' => 'title',
                'name' => 'Форма',
            ),
            'edit_images' => array(
                'type' => 'checkbox',
                'name' => 'Изображения',
            ),
            'edit_menu' => array(
                'type' => 'checkbox',
                'name' => 'Меню',
            ),
            'edit_geomap' => array(
                'type' => 'checkbox',
                'name' => 'Геокарта',
            ),
            'edit_rating' => array(
                'type' => 'checkbox',
                'name' => 'Рейтинг',
            ),
            'edit_tags' => array(
                'type' => 'checkbox',
                'name' => 'Теги',
            ),
            'edit_dynamic' => array(
                'type' => 'checkbox',
                'name' => 'Динамические блоки',
            ),
            'hr4' => array(
                'type' => 'title',
                'name' => 'Дополнительные параметры',
            ),
            'edit_additional' => array(
                'type' => 'checkbox',
                'name' => 'Дополнительные параметры',
            ),
            'edit_additional_access' => array(
                'type' => 'checkbox',
                'name' => 'Доступ',
                'depend' => 'edit_additional',
            ),
            'edit_additional_user_id' => array(
                'type' => 'checkbox',
                'name' => 'Автор',
                'depend' => 'edit_additional',
            ),
            'edit_additional_admin_id' => array(
                'type' => 'checkbox',
                'name' => 'Редактор',
                'depend' => 'edit_additional',
            ),
            'edit_additional_timeedit' => array(
                'type' => 'checkbox',
                'name' => 'Время последнего изменения',
                'depend' => 'edit_additional',
            ),
            'edit_additional_counter_view' => array(
                'type' => 'checkbox',
                'name' => 'Счетчик просмотров',
                'depend' => 'edit_additional',
            ),
            'edit_additional_search' => array(
                'type' => 'checkbox',
                'name' => 'Индексирование для поиска',
                'depend' => 'edit_additional',
            ),
            'edit_additional_map' => array(
                'type' => 'checkbox',
                'name' => 'Индексирование для карты сайта',
                'depend' => 'edit_additional',
            ),
            'edit_additional_date_period' => array(
                'type' => 'checkbox',
                'name' => 'Период показа',
                'depend' => 'edit_additional',
            ),
            'edit_additional_number' => array(
                'type' => 'checkbox',
                'name' => 'Номер',
                'depend' => 'edit_additional',
            ),
            'edit_additional_seo' => array(
                'type' => 'checkbox',
                'name' => 'Параметры SEO',
                'depend' => 'edit_additional',
            ),
            'edit_additional_view' => array(
                'type' => 'checkbox',
                'name' => 'Оформление',
                'depend' => 'edit_additional',
            ),
        ),
    );
    public $tabs_name = array(
        'base' => 'Авторизация',
        'menu' => 'Меню',
        'additional' => 'Дополнительные настройки',
    );
	public $config = array (
	    'tab_card', // использование вкладок
		'config', // файл настроек модуля
	);
	public function edit_config_variable_qr()
	{
	    $d = dir(ABSOLUTE_PATH.'cache');
        while ($entry = $d->read()){
            if (substr($entry,0,6) == "qrcode"){
                File::delete_file('cache/'.$entry);
            }
        }
	    Custom::inc("modules/api/functions/qrlib.php");
	    $filename = 'qrcode'.$this->diafan->_session->id.'.png';
	    $data = 'u:'.$this->url.'|s:'.$this->diafan->_session->id;
	    QRcode::png($data, ABSOLUTE_PATH.'cache/'.$filename, 'H', 5, 2);
	    echo '<div id="qr" class="unit"><div class="infofield">'.
	    $this->diafan->variable_name().$this->diafan->help().'</div>';
	    echo '<img src="'.BASE_PATH.'cache/'.$filename.'">';
	    echo '</div>';
	}
	public function edit_config_variable_url()
	{
	    $rewrite = DB::query_result("SELECT r.rewrite 
	        FROM {site} as s 
	        LEFT JOIN {rewrite} as r ON r.module_name = 'site' 
	        AND r.element_type = 'element' AND r.element_id = s.id AND r.trash = '0'
	        WHERE s.module_name = 'api' AND s.trash = '0'");
	    $this->url = BASE_PATH.$rewrite.'/';
	    echo '<div id="url" class="unit"><div class="infofield">'.
	    $this->diafan->variable_name().$this->diafan->help().'</div>';
	    echo $this->url;
	    echo '</div>';
	}
	public function edit_config_variable_menus()
	{
	    echo '<div id="menus" class="unit"><div class="infofield">'.
	    $this->diafan->variable_name().$this->diafan->help().'</div>';
	    if(! isset($this->diafan->cache["menus"])){
	        $this->diafan->cache["menus"] = DB::query_fetch_all("SELECT id, group_id, name, rewrite
	            FROM {admin} 
	            WHERE act='1' AND parent_id = '0' 
	            ORDER BY group_id ASC, sort ASC");
	    }
	    $rows = $this->diafan->cache["menus"];
	    $values = array();
	    if($this->diafan->value === '1'){
	        foreach($rows as $row){
	            $values[] = $row["rewrite"];
	        }
	    }elseif($this->diafan->value){
	        $values = unserialize($this->diafan->value);
	    }
	    $group_id = 0;
	    $groups = array ( 
	        1 => $this->diafan->_('Контент'),
	        4 => $this->diafan->_('Интернет магазин'),
	        2 => $this->diafan->_('Интерактив'),
	        3 => $this->diafan->_('Сервис'),
	        5 => $this->diafan->_('Настройки')
	    );
	    foreach ($rows as $row){
	        if($group_id != $row['group_id']){
	            $group_id = $row['group_id'];
	            echo '<h2 id="hr1">'.$groups[$group_id].'</h2>';
	        }
	        echo '<input type="checkbox" name="menus[]" id="menus_'.$row['rewrite'].'" value="'.
	   	        $row['rewrite'].'"'.
	        (in_array($row['rewrite'], $values) ? ' checked' : '' )
	        .'> <label for="menus_'.$row['rewrite'].'">'.$row['name'].'</label><br>';
	    }
	    echo '</div>';
	}
	public function save_config_variable_qr(){}
	public function save_config_variable_url(){}
	public function save_config_variable_menus(){
	    $this->diafan->set_query("menus='%s'");
	    $this->diafan->set_value(! empty($_POST["menus"]) ? serialize($_POST["menus"]) : '');
	}
}