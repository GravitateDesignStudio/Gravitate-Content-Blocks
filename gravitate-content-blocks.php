<?php
/*
Plugin Name: Gravitate Content Blocks
Description: Create Content Blocks.
Version: 1.0.0
Plugin URI: http://www.gravitatedesign.com
Author: Gravitate
*/

register_activation_hook( __FILE__, array( 'GRAV_BLOCKS', 'activate' ));
register_deactivation_hook( __FILE__, array( 'GRAV_BLOCKS', 'deactivate' ));

add_action('admin_menu', array( 'GRAV_BLOCKS', 'admin_menu' ));
add_action('admin_init', array( 'GRAV_BLOCKS', 'admin_init' ));
add_action('init', array( 'GRAV_BLOCKS', 'init' ));



class GRAV_BLOCKS {

	private static $version = '1.0.0';
	private static $page = 'options-general.php?page=gravitate_blocks';
	private static $settings = array();
	private static $option_key = 'gravitate_blocks_settings';

	public static function dump($var){
		echo '<pre>';
		var_dump($var);
		echo '</pre>';
	}

	public static function init()
	{
		// Nothing for now
		// self::get_settings();
		// grav_dump(self::$settings);

		if($config_path = self::get_path('config.php'))
		{
			include $config_path;
		}
		else
		{
			// Error
		}
	}

	public static function activate()
	{
		// Nothing for now
	}

	public static function deactivate()
	{
		// Nothing for now
	}

	public static function admin_init()
	{
		// Nothing for now
	}

	public static function admin_menu()
	{
		add_submenu_page( 'options-general.php', 'Gravitate Blocks', 'Gravitate Blocks', 'manage_options', 'gravitate_blocks', array( __CLASS__, 'admin' ));
	}

	public static function get_handler_template()
	{
		if($handler && file_exists(get_template_directory().'/grav-blocks/handler.php'))
		{
			return get_template_directory().'/grav-blocks/handler.php';
		}
		else if(file_exists(plugin_dir_path( __FILE__ ).'grav-blocks/handler.php'))
		{
			return plugin_dir_path( __FILE__ ).'grav-blocks/handler.php';
		}
	}

	public static function get_handler_path($handler='')
	{
		$template = self::get_handler_template();

		if(get_field($handler))
		{
			while(the_flexible_field($handler))
			{
				$block_class_prefix = 'block';
				$block_name = strtolower(str_replace('_', '-', get_row_layout()));
				$block_background = get_sub_field('block_background');
				$block_background_image = get_sub_field('block_background_image');
				$block_background_style = (get_sub_field('block_background') == 'image' && $block_background_image ? ' style="background-image: url(\''.$block_background_image['large'].'\');" ' : '');
				?>

				<section class="<?php echo $block_class_prefix;?>-container <?php echo $block_class_prefix;?>-<?php echo $block_name;?> <?php echo $block_background;?>" <?php echo $block_background_style;?>>

					<?php
						$layout = strtolower(str_replace('_', '-', get_row_layout()));
						GRAV_BLOCKS::get_block($layout);
					?>

				</section>

				<?php
			}
		}
	}

	public static function get_locations()
	{
		self::get_settings(true);
		$locations = array();
		$locations_formatted = array();

		if(!empty(self::$settings['post_types']))
		{
			foreach (self::$settings['post_types'] as $location)
			{
				$locations[] = array('key' => 'post_type', 'value' => $location);
			}
		}

		if(!empty(self::$settings['templates']))
		{
			foreach (self::$settings['templates'] as $location)
			{
				$locations[] = array('key' => 'page_template', 'value' => $location);
			}
		}

		$group = 0;

		foreach ($locations as $location)
		{
			$locations_formatted[] = array (
					array (
						'param' => $location['key'],
						'operator' => '==',
						'value' => $location['value'],
						'order_no' => 0,
						'group_no' => $group++,
					),
				);
		}

		$locations_formatted = apply_filters( 'grav_block_locations', $locations_formatted );

		return $locations_formatted;
	}

	public static function get_block($block='')
	{
		if($path = self::get_path($block))
		{
			if(file_exists($path.'/block.php'))
			{
				include($path.'/block.php');
			}
			else
			{
				// Error
			}
		}
		else
		{
			// Error
		}
	}

	public static function get_blocks()
	{
		self::get_settings(true);
		$blocks = array();

		if(empty(self::$settings['blocks_enabled']))
		{
			return array();
		}

		if($available_blocks = self::get_available_blocks())
		{
			$blocks = array_intersect_key($available_blocks, array_flip(self::$settings['blocks_enabled']));
		}

		return $blocks;
	}

	public static function get_available_blocks()
	{
		$blocks = array();
		$plugin_blocks = array();
		$theme_blocks = array();

		// Get blocks from the Plugin
		if($directory = self::get_path())
		{
			$plugin_blocks = array_filter(glob($directory.'*'), 'is_dir');
		}

		// Get blocks from the Theme
		if($directory = get_template_directory().'/grav-blocks/')
		{
			if(is_dir($directory))
			{
				$theme_blocks = array_filter(glob($directory.'*'), 'is_dir');
			}
		}

		// Overwrite Plugin Blocks with Theme Blocks
		$dirs = array_merge($plugin_blocks, $theme_blocks);

		if($dirs)
		{
			foreach($dirs as $dir)
			{
				$block = basename($dir);

			    if(file_exists($dir.'/block.php'))
			    {
					$blocks[$block] = $dir;
				}
			}
		}

		// Apply Filters to allow others to filter the blocks used.
		$blocks = apply_filters( 'grav_blocks', $blocks );

		return $blocks;
	}

	public static function get_path($path='')
	{
		if(!$path)
		{
			if(is_dir(plugin_dir_path( __FILE__ ).'grav-blocks/'))
			{
				return plugin_dir_path( __FILE__ ).'grav-blocks/';
			}
			else
			{
				// Error
			}
		}
		else
		{
			if(is_dir(get_template_directory().'/grav-blocks/'.$path.'/'))
			{
				return get_template_directory().'/grav-blocks/'.$path;
			}
			else if(file_exists(get_template_directory().'/grav-blocks/'.$path))
			{
				return get_template_directory().'/grav-blocks/'.$path;
			}
			else if(is_dir(plugin_dir_path( __FILE__ ).'grav-blocks/'.$path.'/'))
			{
				return plugin_dir_path( __FILE__ ).'grav-blocks/'.$path;
			}
			else if(file_exists(plugin_dir_path( __FILE__ ).'grav-blocks/'.$path))
			{
				return plugin_dir_path( __FILE__ ).'grav-blocks/'.$path;
			}

			return false;
		}
	}

	public static function get_real_ip()
    {
        foreach (array('HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR') as $server_ip)
        {
            if(!empty($_SERVER[$server_ip]) && is_string($_SERVER[$server_ip]))
            {
                if($ip = trim(reset(explode(',', $_SERVER[$server_ip]))))
	            {
	            	return $ip;
	            }
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

	private static function get_settings_fields($location = 'general')
	{
		switch ($location)
		{
			default:
			case 'general':
				$posts_to_exclude = array('attachment', 'revision', 'nav_menu_item', 'acf-field-group', 'acf-field');
				// TODO add filter here for $posts_to_exclude

				$posts = get_post_types();
				$templates = get_page_templates();
				$post_types = array();
				$template_options = array();

				foreach($posts as $post_type)
				{
					if(!in_array($post_type, $posts_to_exclude))
					{
						$post_types[$post_type] = self::unsanitize_title($post_type);
					}
				}

				if(!in_array('default', array_map('strtolower', $templates)) && !in_array('page.php', array_map('strtolower', $templates)) && file_exists(get_template_directory().'/page.php'))
				{
					$templates = array_merge(array('Default' => 'default'), $templates);
				}

				foreach($templates as $key => $template)
				{
					$template_options[$template] = self::unsanitize_title($key);
				}

				$background_colors_repeater = array(
					'name' => array('type' => 'text', 'label' => 'Name', 'description' => ''),
					'value' => array('type' => 'checkbox', 'label' => 'Value', 'description' => 'Use Hex values (ex. #ff0000)', 'options' => array('blue' => 'Blue', 'red' => 'Red'))
				);


				$fields = array();
				$fields['blocks_enabled'] = array('type' => 'checkbox', 'label' => 'Blocks Enabled', 'options' => implode(',', array_keys(self::get_available_blocks())), 'description' => 'Choose what post types you want to have the Gravitate Blocks.');
				$fields['background_colors'] = array('type' => 'repeater', 'label' => 'Background Color Options', 'fields' => $background_colors_repeater, 'description' => 'Choose what post types you want to have the Gravitate Blocks.');
				$fields['post_types'] = array('type' => 'checkbox', 'label' => 'Post Types', 'options' => $post_types, 'description' => 'Choose what post types you want to have the Gravitate Blocks.');
				$fields['templates'] = array('type' => 'checkbox', 'label' => 'Templates', 'options' => $template_options, 'description' => 'Choose what templates you want to have the Gravitate Blocks.');

				break;

			case 'advanced':
				$advanced_options = array(
					'foundation' => 'Use Foundation 5 CSS.',
					'content' => 'Add content blocks to the end of your content.'
				);

				$fields = array();
				$fields['advanced_options'] = array('type' => 'checkbox', 'label' => 'Advanced Options', 'options' => $advanced_options, 'description' => 'Change Advanced Settings.');

				break;

		}

		self::get_settings();

		// Update Values in Form
		if(!empty(self::$settings))
		{
			foreach (self::$settings as $key => $value)
			{
				if(isset($fields[$key]))
				{
					if($fields[$key]['type'] == 'repeater' && is_array($value))
					{
						$rep_original_fields = $fields[$key]['fields'];

						foreach ($value as $rep_i => $rep_values)
						{
							$fields[$key]['fields'][$rep_i] = $rep_original_fields;

							foreach ($rep_original_fields as $rep_key => $rep_value)
							{
								$fields[$key]['fields'][$rep_i][$rep_key]['value'] = $rep_values[$rep_key];
							}
						}
					}
					else
					{
						$fields[$key]['value'] = $value;
					}
				}
			}
		}

		return $fields;
	}

	private static function get_settings($force=false)
	{
		if(empty(self::$settings) || $force)
		{
			self::$settings = get_option(self::$option_key);
		}
	}

	private static function save_settings()
	{
		if(!empty($_POST['save_settings']) && !empty($_POST['settings']))
		{
			$_POST['settings']['updated_at'] = time();

			$settings = $_POST['settings'];

			if(!empty(self::$settings))
			{
				$settings = array_merge(self::$settings, $settings);
			}

			if(update_option( self::$option_key, $settings ))
			{
				self::get_settings(true);
				return 'Settings Saved Successfully';
			}
		}

		return false;
	}

	public static function admin()
	{
		// Get Settings
		self::get_settings(true);

		// Save Settings if POST
		self::save_settings();

		?>

		<div class="wrap">
		<h2>Gravitate Blocks</h2>
		<h4 style="margin: 6px 0;">Version <?php echo self::$version;?></h4>
		<?php if(!empty($error)){?><div class="error"><p><?php echo $error; ?></p></div><?php } ?>
		</div>

		<br>
		<div class="gravitate-redirects-page-links">
			<a href="<?php echo self::$page;?>&section=General">General</a>
			<a href="<?php echo self::$page;?>&section=advanced">Advanced</a>
		</div>

		<br>
		<br>

		<?php

		$section = (!empty($_GET['section']) ? $_GET['section'] : 'settings');

		switch($section)
		{
			default:
			case 'settings':
				self::settings();
			break;

			case 'advanced':
				self::settings('advanced');

			case 'add':
				self::add();
			break;

			case 'top':
				self::top();
			break;
		}
	}

	private static function add()
	{
		?>


		<?php
	}

	private static function settings($type = 'general')
	{
		// Get Form Fields
		switch ($type){
			default;
			case 'general':
				$fields = self::get_settings_fields();
				break;

			case 'advanced':
				$fields = self::get_settings_fields('advanced');
				break;
		}

		//echo '<pre>';print_r(self::$settings);echo '</pre>';

		//echo '<pre>';print_r($fields);echo '</pre>';

		?>
			<form method="post">
				<input type="hidden" name="save_settings" value="1">
				<table class="form-table">
				<?php
				foreach($fields as $meta_key => $field)
				{
					?>
					<tr>
						<th><label for="<?php echo $meta_key;?>"><?php echo $field['label'];?></label></th>
						<td>
						<?php
						if($field['type'] != 'repeater')
						{
							self::settings_field($meta_key, $field);
						}
						else // If Repeater
						{
							if(!empty($field['fields']))
							{
								?>
								<table class="form-table repeater-table">
									<?php
									foreach ($field['fields'] as $rep_i => $rep_fields)
									{
										if(is_numeric($rep_i))
										{
											?>
											<tr class="repeater-item" style="border: 1px solid #999;">
												<?php

												foreach ($rep_fields as $rep_key => $rep_field)
												{
													?>
													<td>
														<?php self::settings_field($rep_key, $rep_field, $meta_key, $rep_i); ?>
													</td>
													<?php

												}
												$rep_i++;
												?>
												<td>
													<button class="repeater-remove" type="input">X</button>
												</td>
											</tr>
										<?php
										}
									}
									?>
									<tfoot>
										<tr>
											<td colspan="10"><button class="repeater-add" style="float:right;" type="input">Add</button></td>
										</tr>
									</tfoot>
								</table>
								<?php
							}
						}
						?>
						</td>
					</tr>
					<?php
				}
				?>
				</table>
				<p><input type="submit" value="Save Settings" class="button button-primary" id="submit" name="submit"></p>
			</form>

			<script>

			jQuery(function($)
			{

				$('.repeater-add').on('click', function(e)
				{
					e.preventDefault();
					var clone = $('.repeater-table .repeater-item:first-child').clone();
					clone.html(clone.html().replace(new RegExp(/\[0\]/, 'g'), '['+$(this).closest('.repeater-table').find('.repeater-item').length+']'));
					clone.find('input[type="text"], textarea').val('');
					clone.find('input[type="checkbox"]').removeAttr('checked');
					clone.appendTo('.repeater-table');
					addRemoveListeners();
					return false;
				});

				function addRemoveListeners()
				{
					$('.repeater-remove').off('click');
					$('.repeater-remove').on('click', function(e)
					{
						e.preventDefault();
						if($(this).closest('.repeater-table').find('.repeater-item').length > 1)
						{
							$(this).closest('.repeater-item').remove();
						}
						else
						{
							alert('You need to keep at least one Item');
						}
						return false;
					});
				}

				addRemoveListeners();
			});

			</script>
		<?php

	}

	private static function settings_field($meta_key, $field, $repeater_key='', $rep_i=0)
	{
		$settings_attribute = 'settings['.$meta_key.']';

		if($repeater_key && $field['label'])
		{
			$settings_attribute = 'settings['.$repeater_key.']['.$rep_i.']['.$meta_key.']';

			?><label for="<?php echo $meta_key;?>"><strong><?php echo $field['label'];?></strong></label><br><?php
		}

		if(!empty($field['description']))
		{ ?><span class="description"><?php echo $field['description'];?></span><br><?php }

		if($field['type'] == 'text')
		{
			?><input type="text" name="<?php echo $settings_attribute;?>" id="<?php echo $meta_key;?>"<?php echo (isset($field['maxlength']) ? ' maxlength="'.$field['maxlength'].'"' : '');?> value="<?php echo esc_attr( (isset($field['value']) ? $field['value'] : '') );?>" class="regular-text" /><br /><?php
		}
		else if($field['type'] == 'textarea')
		{
			?><textarea rows="6" cols="38" name="<?php echo $settings_attribute;?>" id="<?php echo $meta_key;?>"><?php echo esc_attr( (isset($field['value']) ? $field['value'] : '') );?></textarea><br /><?php
		}
		else if($field['type'] == 'select')
		{
			?>
			<select name="<?php echo $settings_attribute;?>" id="<?php echo $meta_key;?>">
			<?php
			foreach($field['options'] as $option_value => $option_label){
				$real_value = ($option_value !== $option_label && !is_numeric($option_value) ? $option_value : $option_label);
				?>
				<option<?php echo ($real_value !== $option_label ? ' value="'.$real_value.'"' : '');?> <?php selected( ($real_value !== $option_label ? $real_value : $option_label), esc_attr( (isset($field['value']) ? $field['value'] : '') ));?>><?php echo $option_label;?></option>
				<?php
			} ?>
			</select>
			<?php
		}
		else if($field['type'] == 'checkbox')
		{
			if(is_string($field['options']))
			{
				$field['options'] = explode(',', $field['options']);
				$field['options'] = array_combine($field['options'], $field['options']);
			}

			?>
			<input type="hidden" name="<?php echo $settings_attribute;?>" value="">
			<?php

			foreach($field['options'] as $option_value => $option_label)
			{
				$real_value = ($option_value !== $option_label && !is_numeric($option_value) ? $option_value : $option_label);

				if(is_array($field['value']))
				{
					$checked = (in_array($real_value, $field['value'])) ? 'checked' : '';
				}
				else
				{
					$checked = '';
				}
				?>
				<label><input type="checkbox" name="<?php echo $settings_attribute;?>[]" value="<?php echo $option_value; ?>" <?php echo $checked; ?>><?php echo $option_label; ?></label><br>
				<?php
			}
		}
	}

	public static function unsanitize_title($title)
	{
		return ucwords(str_replace(array('_', '-'), ' ', $title));
	}
}