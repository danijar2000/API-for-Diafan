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
class Rating_api extends Diafan
{
	public function edit()
	{
		$element_type = $this->diafan->element_type();
		if ($this->diafan->is_new
			|| $element_type == 'element' && ! $this->diafan->configmodules("rating")
			|| $element_type != 'element' && ! $this->diafan->configmodules("rating_".$element_type)
		)
			return;

		$row = DB::query_fetch_array("SELECT id, rating, count_votes FROM {rating} WHERE element_id='%d' AND module_name='%s' AND element_type='%s' AND trash='0' LIMIT 1", $this->diafan->id, $this->diafan->_admin->module, $element_type);
		echo '<div class="unit" id="rating">'
			.($row
			 ? '
				<a href="'.BASE_PATH_HREF.'rating/edit'.$row["id"].'/">'.$this->diafan->_('Рейтинг').': '.$row["rating"]
				.' '.$this->diafan->_('голосов').': '.$row["count_votes"].'</a>'
			 : $this->diafan->_('Рейтинг').': '.$this->diafan->_('нет голосов'))
			.$this->diafan->help().'
		</div>';
	}
}