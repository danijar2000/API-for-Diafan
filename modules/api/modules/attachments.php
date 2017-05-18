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
class Attachments_api extends Diafan
{
	public function edit()
	{
		$site_id = $this->diafan->values("site_id");

		$name = $this->diafan->variable_name();
		$help = 'attachments';
		$config = array(
			"use_animation" => $this->diafan->configmodules("use_animation", $this->diafan->_admin->module, $site_id),
			"max_count_attachments" => $this->diafan->configmodules("max_count_attachments", $this->diafan->_admin->module, $site_id),
			"attachment_extensions" => $this->diafan->configmodules("attachment_extensions", $this->diafan->_admin->module, $site_id),
		);
		echo '<div class="unit" id="attachments">
			<div class="infofield">'.$name.$this->diafan->help($help).'</div>';
			if(! $this->diafan->is_new)
			{
				$anim_link = '';
				$anim = '';
				if(! empty($config["use_animation"]))
				{
					$anim_link = ' rel="prettyPhoto[attachments_link]"';
					$anim = ' rel="prettyPhoto[attachments]"';
				}
				$attachments = $this->diafan->_attachments->get($this->diafan->id, $this->diafan->table, 0);
				foreach ($attachments as $row)
				{
					echo '<div class="attachment">
					<input type="hidden" name="hide_attachment_delete[]" value="'.$row["id"].'">';
					if ($row["is_image"])
					{
						echo '<a href="'.$row["link"].'"'.$anim.'><img src="'.$row["link_preview"].'"></a> ';
						echo '<a href="'.$row["link"].'"'.$anim_link.'>'.$row["name"].'</a>';
					}
					else
					{
						echo '<a href="'.$row["link"].'">'.$row["name"].'</a>
						';
					}
					echo '<a href="javascript:void(0)" class="attachment_delete" confirm="'.$this->diafan->_('Вы действительно хотите удалить файл?').'"><img src="'.BASE_PATH.'adm/img/delete.png" width="13" height="13" alt="'.$this->diafan->_('Удалить').'"></a></div>';
				}
			}
			echo '
			<div class="attachment_files">
					<input type="file" name="attachments[]" max="'.$config["max_count_attachments"].'" class="inpfiles">
			</div>';
			if ($config["attachment_extensions"])
			{
				echo '<div class="attachment_extensions">('.$this->diafan->_('Доступные типы файлов').': '.$config["attachment_extensions"].')</div>';
			}
			echo '
		</div>';
	}
}