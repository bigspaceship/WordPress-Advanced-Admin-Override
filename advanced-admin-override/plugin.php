<?php
/*
Plugin Name: Advanced Admin Override
Plugin URI: http://www.bigspaceship.com
Description: This panel allows developers to safely add CSS or JS to post types in the WordPress admin separate of an individual plugin or theme capability. The idea is that if a future WordPress update impacts the overriding the core way the CMS works, the site would be in stasis until a patch was released. Leveraging this plugin allows site administrators to disable custom CMS logic in this case until a patch is created.
Version: 1.0.0
Author: Big Spaceship
Author URI: http://www.bigspaceship.com/
Copyright: Big Spaceship, LLC
*/

$advancedAdmin = new AdvancedAdminOverride();

class AdvancedAdminOverride
{ 
	var $_pluginDirUrl;
	var $_pluginDirServerPath;

	var $_themeUrl;
	var $_siteUrl;
	var $_adminUrl;

	var $_isEnabledByDefault = false;

	function AdvancedAdminOverride()
	{
		$this->_pluginDirServerPath = dirname(__FILE__).''; 	 // server path to this plugin directory
		$this->_pluginDirUrl = plugins_url('',__FILE__); // www path to this plugin directory
		$this->_siteUrl = get_bloginfo('url');
		$this->_adminUrl = admin_url();
		$this->_themeUrl = get_bloginfo('template_directory');

		// jk: actions
		add_action('admin_menu', array($this,'admin_menu'));
		add_action('admin_head', array($this,'admin_head'));

		return true;
	}

	// jk: define modules and required CSS.
	private function _loadConfig() {
		$config = simplexml_load_file($this->_pluginDirServerPath.'/config.xml');

		$this->_modules = array();
		foreach($config->module as $module) {
			$postType = (string) $module->attributes()->type;
			if(post_type_exists($postType)) {
				$postTypeObject = get_post_type_object($postType);

				$this->_modules[$postType] = array();
				$this->_modules[$postType]['js'] = array();
				$this->_modules[$postType]['css'] = array();
				$this->_modules[$postType]['label'] = $postTypeObject->labels->name;

				if(count($module->files->file) > 0) {
					$cssFiles = $module->files->xpath("file[@type='css']");
					foreach($cssFiles as $file) {
						$str = str_replace('{pluginDir}',$this->_pluginDirUrl,(string) $file->attributes()->path);
						$str = str_replace('{themeDir}',$this->_themeUrl,$str);

						$this->_modules[$postType]['css'][] = $str;
					}

					$jsFiles = $module->files->xpath("file[@type='js']");
					foreach($jsFiles as $file) {
						$str = str_replace('{pluginDir}',$this->_pluginDirUrl,(string) $file->attributes()->path);
						$str = str_replace('{themeDir}',$this->_themeUrl,$str);

						$this->_modules[$postType]['js'][] = $str;
					}
				}
			}
		}
	}

	public function admin_menu() {
        add_menu_page('Advanced Admin Override', 'Advanced Admin','manage_options',__FILE__,array($this,'settings_page'),$this->_pluginDirUrl.'/icon.png');
	}

	public function admin_head()
	{
		global $post, $pagenow;
		if(in_array($pagenow, array('post.php', 'post-new.php'))) {
			$this->_loadConfig();

			$postType = get_post_type($post);
			if(in_array($postType, array_keys($this->_modules))) {
				$settings = json_decode(get_option('bss_advanced_admin_settings'),true);
				if(isset($settings[$postType])) {
					$isEnabled = $settings[$postType];
				}
				else {
					$isEnabled = $this->_isEnabledByDefault;
				}

				if($isEnabled) {
					foreach($this->_modules[$postType]['css'] as $file) {
						echo "<link rel='stylesheet' href='{$file}' />";
					}

					foreach($this->_modules[$postType]['js'] as $file) {
						echo "<script type='text/javascript' src='{$file}'></script>";
					}
				}
			}
		}
		else if($pagenow == 'admin.php' && $_GET['page'] == 'advanced_admin_override/plugin.php') {
			if(isset($_POST['submit'])) {
				$this->_updateSettings();
			}
		}
	}

	// jk: update options
	private function _updateSettings() {
		$settings = json_decode(get_option('bss_advanced_admin_settings'),true);
		$newSettings = array();

		if(isset($settings)) {
			foreach($settings as $postType=>$setting) {
				$newSettings[$postType] = false;
			}
		}

		foreach($_POST as $key=>$value) {
			if($key != 'submit') {
				$postType = str_replace('bss_admin_require_','',$key);
				$newSettings[$postType] = true;
			}
		}

		update_option('bss_advanced_admin_settings',json_encode($newSettings));
		add_action('admin_notices',function() {
            echo "<div id='bss_advanced_admin_settings' class='updated fade'><p><strong>Settings saved.</strong></p></div>";
		});		
	}

	// jk: page rendering
	public function settings_page() {
		$this->_loadConfig();
		$data = array();

		$settings = json_decode(get_option('bss_advanced_admin_settings'),true);
		$data['modules'] = $this->_modules;

		foreach($data['modules'] as $postType=>&$module) {
			if(isset($settings[$postType])) {
				$module['is_enabled'] = $settings[$postType];
			}
			else $module['is_enabled'] = $this->_isEnabledByDefault;
		}

		$this->_render($this->_pluginDirServerPath.'/admin.php',$data);
	}

	private function _render($template,$args) {
		ob_start();
		extract($args);
	    include($template);
	    ob_end_flush();
	}
}
?>