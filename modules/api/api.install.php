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

class API_install extends Install
{
	public $title = "API";
	public $modules = array(
		array(
			"name" => "api",
			"admin" => true,
			"site" => true,
		    "site_page" => true,
		),
	);
	public $admin = array(
		array(
			"name" => "API",
			"rewrite" => "api",
			"group_id" => "5",
			"sort" => 1,
			"act" => true,
		),
	);
	public $site = array(
	    array(
	        "name" => array('API', 'API'),
	        "module_name" => "api",
	        "rewrite" => "api",
	        "menu" => 0,
	        "parent_id" => 0,
	    ),
	);
	public $config = array(
		array(
			"name" => "menus",
			"value" => 'a:23:{i:0;s:4:"site";i:1;s:4:"menu";i:2;s:4:"news";i:3;s:7:"clauses";i:4;s:5:"photo";i:5;s:2:"bs";i:6;s:5:"files";i:7;s:2:"ab";i:8;s:4:"tags";i:9;s:5:"users";i:10;s:5:"forum";i:11;s:3:"faq";i:12;s:8:"feedback";i:13;s:5:"votes";i:14;s:12:"subscribtion";i:15;s:6:"rating";i:16;s:8:"comments";i:17;s:8:"mistakes";i:18;s:4:"shop";i:19;s:10:"shop/order";i:20;s:13:"shop/delivery";i:21;s:7:"payment";i:22;s:15:"shop/ordercount";}',
		),	
	    array(
	        "name" => "show_counter",
	        "value" => "1",
	    ),
	    array(
	        "name" => "show_action",
	        "value" => "1",
	    ),
	    array(
	        "name" => "show_sort",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_images",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_menu",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_geomap",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_rating",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_tags",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_dynamic",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_access",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_user_id",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_admin_id",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_timeedit",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_counter_view",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_search",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_map",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_date_period",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_number",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_seo",
	        "value" => "1",
	    ),
	    array(
	        "name" => "edit_additional_view",
	        "value" => "1",
	    ),
	);
}