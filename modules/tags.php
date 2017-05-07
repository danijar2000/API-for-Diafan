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
class Tags_api extends Diafan
{
	public function edit()
	{
		$element_type = $this->diafan->element_type();
		if (! $this->diafan->configmodules("tags".($element_type != 'element'? '_'.$element_type : '')))
		{
			return;
		}
		Custom::inc('modules/tags/admin/tags.admin.view.php');
		$tags_view = new Tags_admin_view($this->diafan);

		echo '<div class="unit tags" id="tags">
			<h2>'.$this->diafan->_("Теги").$this->diafan->help().'</h2>
			<div class="tags_container">'.$tags_view->show($this->diafan->is_new ? 0 : $this->diafan->id).'</div>
			<div>'.$this->diafan->_('Добавить теги').'</div>

			<textarea rows="5" name="tags"></textarea>
			<div class="unit__sinfo">'.$this->diafan->_('Несколько тегов через Enter').'</div>
			
			<span class="btn btn_blue btn_small btn_tags tags_upload">
				<i class="fa fa-plus-square"></i>
				'.$this->diafan->_('Добавить').'
			</span> '.$this->diafan->_('или').' 
			<a href="#" class="tags_cloud" element_id="'.($this->diafan->is_new ? 0 : $this->diafan->id).'">
				<i class="fa fa-tags"></i>
				'.$this->diafan->_('Выбрать из облака тегов').'
			</a>
			<div class="errors error_tags"></div>
			<h2></h2>
		</div>';
	}
}