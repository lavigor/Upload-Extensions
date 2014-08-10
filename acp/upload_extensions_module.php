<?php
/**
*
* @package Upload Extensions
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

namespace forumhulp\upload_extensions\acp;

class upload_extensions_module
{
	public $u_action;
	var $ext_dir = '';
	var $error = '';
	function main($id, $mode)
	{
		global $db, $config, $user, $cache, $template, $request, $phpbb_root_path, $phpbb_extension_manager, $phpbb_container;

		$this->page_title = $user->lang['ACP_UPLOAD_EXT_TITLE'];
		$this->tpl_name = 'acp_upload_extensions';
		$this->ext_dir = $phpbb_root_path . 'ext';

		// get any url vars
		$action = $request->variable('action', '');
		$mode = $request->variable('mode', '');
		$id = $request->variable('i', '');

		switch ($action)
		{
			case 'details':
				$user->add_lang(array('install', 'acp/extensions', 'migrator'));
				$ext_name = 'forumhulp/upload_extensions';
				$md_manager = new \phpbb\extension\metadata_manager($ext_name, $config, $phpbb_extension_manager, $template, $user, $phpbb_root_path);
				try
				{
					$this->metadata = $md_manager->get_metadata('all');
				}
				catch(\phpbb\extension\exception $e)
				{
					trigger_error($e, E_USER_WARNING);
				}

				$md_manager->output_template_data();

				try
				{
					$updates_available = $this->version_check($md_manager, $request->variable('versioncheck_force', false));

					$template->assign_vars(array(
						'S_UP_TO_DATE'		=> empty($updates_available),
						'S_VERSIONCHECK'	=> true,
						'UP_TO_DATE_MSG'	=> $user->lang(empty($updates_available) ? 'UP_TO_DATE' : 'NOT_UP_TO_DATE', $md_manager->get_metadata('display-name')),
					));

					foreach ($updates_available as $branch => $version_data)
					{
						$template->assign_block_vars('updates_available', $version_data);
					}
				}
				catch (\RuntimeException $e)
				{
					$template->assign_vars(array(
						'S_VERSIONCHECK_STATUS'			=> $e->getCode(),
						'VERSIONCHECK_FAIL_REASON'		=> ($e->getMessage() !== $user->lang('VERSIONCHECK_FAIL')) ? $e->getMessage() : '',
					));
				}

				$template->assign_vars(array(
					'U_BACK'				=> $this->u_action . '&amp;action=list',
				));

				$this->tpl_name = 'acp_ext_details';
				break;
			case 'upload':
			case 'upload_remote':
				if (!is_writable($this->ext_dir))
				{
					trigger_error($user->lang('EXT_NOT_WRITABLE'));
				}
				if (!$this->upload_ext($action))
				{
					trigger_error($user->lang('EXT_UPLOAD_ERROR'));
				}
				$this->list_available_exts($phpbb_extension_manager);
				$template->assign_vars(array(
					'U_ACTION'			=> $this->u_action,
					'U_UPLOAD'			=> $phpbb_root_path . 'adm/index.php?i=' . $id . '&amp;sid=' .$user->session_id . '&amp;mode=' . $mode . '&amp;action=upload',
					'U_UPLOAD_REMOTE'	=> $phpbb_root_path . 'adm/index.php?i=' . $id . '&amp;sid=' .$user->session_id . '&amp;mode=' . $mode . '&amp;action=upload_remote',
					'S_FORM_ENCTYPE'	=> ' enctype="multipart/form-data"',
				));
				break;
			case 'delete':
				$ext_name = $request->variable('ext_name', '');
				if ($ext_name != '')
				{
					if (confirm_box(true))
					{
						$dir = substr($ext_name, 0, strpos($ext_name, '/'));
						$extensions = sizeof(array_filter(glob($phpbb_root_path . 'ext/' . $dir . '/*'), 'is_dir'));
						$dir = ($extensions == 1) ? $dir : substr($ext_name, strpos($ext_name, '/') + 1);
						$this->rrmdir($phpbb_root_path . 'ext/' . $dir);
						if($request->is_ajax())
						{
							trigger_error($user->lang('EXT_DELETE_SUCCESS'));
						}
					} else {
						confirm_box(false, $user->lang('EXTENSION_DELETE_CONFIRM', $ext_name), build_hidden_fields(array(
							'i'			=> $id,
							'mode'		=> $mode,
							'action'	=> $action,
						)));
					}
				}
				break;
			default:
				$this->list_available_exts($phpbb_extension_manager);
				$template->assign_vars(array(
					'U_ACTION'			=> $this->u_action,
					'U_UPLOAD'			=> $phpbb_root_path . 'adm/index.php?i=' . $id . '&amp;sid=' .$user->session_id . '&amp;mode=' . $mode . '&amp;action=upload',
					'U_UPLOAD_REMOTE'	=> $phpbb_root_path . 'adm/index.php?i=' . $id . '&amp;sid=' .$user->session_id . '&amp;mode=' . $mode . '&amp;action=upload_remote',
					'S_FORM_ENCTYPE'	=> ' enctype="multipart/form-data"',
				));
				break;
		}
	}
	
	
	function getComposer($dir)
	{
		global $composer;
		$ffs = scandir($dir);
		$composer = false;
		foreach($ffs as $ff)
		{
			if ($ff != '.' && $ff != '..')
			{
				if ($ff == 'composer.json') {$composer = $dir . '/' . $ff; break;}
	
				if(is_dir($dir.'/'.$ff)) $this->getComposer($dir . '/' . $ff);	
			}
		}
		return $composer;
	}

    // Function to remove folders and files 
    function rrmdir($dir) 
	{
        if (is_dir($dir)) 
		{
            $files = scandir($dir);
            foreach ($files as $file) if ($file != '.' && $file != '..') $this->rrmdir($dir . '/' . $file);
         	rmdir($dir);
        }
        else if (file_exists($dir)) unlink($dir);
    }

    // Function to Copy folders and files       
    function rcopy($src, $dst) 
	{
        if (file_exists($dst))
            $this->rrmdir ($dst);
        if (is_dir($src))
		{
			mkdir($dst, 0777, true);
            $files = scandir($src);
            foreach($files as $file)
                if ($file != '.' && $file != '..') $this->rcopy($src . '/' . $file, $dst . '/' . $file);
        } else if (file_exists($src)) copy($src, $dst);
    }

	/**
	* Lists all the available extensions and dumps to the template
	*
	* @param  $phpbb_extension_manager     An instance of the extension manager
	* @return null
	*/
	public function list_available_exts(\phpbb\extension\manager $phpbb_extension_manager)
	{
		global $template, $request, $user;
		$uninstalled = array_diff_key($phpbb_extension_manager->all_available(), $phpbb_extension_manager->all_configured());

		$available_extension_meta_data = array();

		foreach ($uninstalled as $name => $location)
		{
			$md_manager = $phpbb_extension_manager->create_extension_metadata_manager($name, $template);

			try
			{
				$meta = $md_manager->get_metadata('all');
				$available_extension_meta_data[$name] = array(
					'META_DISPLAY_NAME' => $md_manager->get_metadata('display-name'),
					'META_VERSION' => $meta['version'],
				);

				$force_update = $request->variable('versioncheck_force', false);
				$updates = $this->version_check($md_manager, $force_update, !$force_update);

				$available_extension_meta_data[$name]['S_UP_TO_DATE'] = empty($updates);
				$available_extension_meta_data[$name]['S_VERSIONCHECK'] = true;
				$available_extension_meta_data[$name]['U_VERSIONCHECK_FORCE'] = $this->u_action . '&amp;action=details&amp;versioncheck_force=1&amp;ext_name=' . urlencode($md_manager->get_metadata('name'));
			}
			catch(\phpbb\extension\exception $e)
			{
				$template->assign_block_vars('disabled', array(
					'META_DISPLAY_NAME'		=> $user->lang('EXTENSION_INVALID_LIST', $name, $e),
					'S_VERSIONCHECK'		=> false,
				));
			}
			catch (\RuntimeException $e)
			{
				$available_extension_meta_data[$name]['S_VERSIONCHECK'] = false;
			}
		}

		uasort($available_extension_meta_data, array($this, 'sort_extension_meta_data_table'));

		foreach ($available_extension_meta_data as $name => $block_vars)
		{
			$block_vars['U_DETAILS'] = $this->u_action . '&amp;action=delete&amp;ext_name=' . urlencode($name);

			$template->assign_block_vars('disabled', $block_vars);
		}
	}

	/**
	* Sort helper for the table containing the metadata about the extensions.
	*/
	protected function sort_extension_meta_data_table($val1, $val2)
	{
		return strnatcasecmp($val1['META_DISPLAY_NAME'], $val2['META_DISPLAY_NAME']);
	}

	/**
	* Check the version and return the available updates.
	*
	* @param \phpbb\extension\metadata_manager $md_manager The metadata manager for the version to check.
	* @param bool $force_update Ignores cached data. Defaults to false.
	* @param bool $force_cache Force the use of the cache. Override $force_update.
	* @return string
	* @throws RuntimeException
	*/
	protected function version_check(\phpbb\extension\metadata_manager $md_manager, $force_update = false, $force_cache = false)
	{
		global $cache, $config, $user;
		$meta = $md_manager->get_metadata('all');

		if (!isset($meta['extra']['version-check']))
		{
			throw new \RuntimeException($this->user->lang('NO_VERSIONCHECK'), 1);
		}

		$version_check = $meta['extra']['version-check'];

		$version_helper = new \phpbb\version_helper($cache, $config, $user);
		$version_helper->set_current_version($meta['version']);
		$version_helper->set_file_location($version_check['host'], $version_check['directory'], $version_check['filename']);
		$version_helper->force_stability($config['extension_force_unstable'] ? 'unstable' : null);

		return $updates = $version_helper->get_suggested_updates($force_update, $force_cache);
	}
	
	/**
	 *
	 * @package automod
	 * @copyright (c) 2008 phpBB Group
	 * @license http://opensource.org/licenses/gpl-2.0.php GNU Public License
	 *
	 */
	function upload_ext($action)
	{
		global $phpbb_root_path, $phpEx, $template, $user, $request;

		//$can_upload = (@ini_get('file_uploads') == '0' || strtolower(@ini_get('file_uploads')) == 'off' || !@extension_loaded('zlib')) ? false : true;

		$user->add_lang('posting');  // For error messages
		include($phpbb_root_path . 'includes/functions_upload.' . $phpEx);
		$upload = new \fileupload();
		$upload->set_allowed_extensions(array('zip'));	// Only allow ZIP files

		if (!is_writable($this->ext_dir))
		{
			trigger_error($user->lang['EXT_NOT_WRITABLE'] . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$upload_dir = $this->ext_dir;

		// Make sure the ext/ directory exists and if it doesn't, create it
		if (!is_dir($this->ext_dir))
		{
			$this->recursive_mkdir($this->ext_dir);
		}

		// Proceed with the upload
		if ($action == 'upload') $file = $upload->form_upload('extupload');
		else $file = $upload->remote_upload($request->variable('extupload_remote', ''));

		if (empty($file->filename))
		{
			trigger_error((sizeof($file->error) ? implode('<br />', $file->error) : $user->lang['NO_UPLOAD_FILE']) . adm_back_link($this->u_action), E_USER_WARNING);
		}
		else if ($file->init_error || sizeof($file->error))
		{
			$file->remove();
			trigger_error((sizeof($file->error) ? implode('<br />', $file->error) : $user->lang['EXT_UPLOAD_INIT_FAIL']) . adm_back_link($this->u_action), E_USER_WARNING);
		}

		$file->clean_filename('real');
		$file->move_file(str_replace($phpbb_root_path, '', $upload_dir), true, true);

		if (sizeof($file->error))
		{
			$file->remove();
			trigger_error(implode('<br />', $file->error) . adm_back_link($this->u_action), E_USER_WARNING);
		}

		include($phpbb_root_path . 'includes/functions_compress.' . $phpEx);
		$ext_dir = $upload_dir . '/' . str_replace('.zip', '', $file->get('realname'));
		
		$zip = new \ZipArchive;
		$res = $zip->open($file->destination_file);
		if ($res !== true) 
		{
			trigger_error($user->lang['ziperror'][$res] . adm_back_link($this->u_action), E_USER_WARNING);
		}
		$zip->extractTo($phpbb_root_path . 'ext/tmp');
		$zip->close();
			
		$composery = $this->getComposer($phpbb_root_path . 'ext/tmp'); 
			
		if (!$composery)
		{
			trigger_error($user->lang['ACP_UPLOAD_EXT_ERROR_COMP'] . adm_back_link($this->u_action), E_USER_WARNING);
		}
		$string = file_get_contents($composery);
		$json_a = json_decode($string, true);
		$destination = $json_a['name'];
		if (strpos($destination, '/') === false)
		{
			trigger_error($user->lang['ACP_UPLOAD_EXT_ERROR_DEST'] . adm_back_link($this->u_action), E_USER_WARNING);
		}
		$display_name = $json_a['extra']['display-name'];
		if ($json_a['type'] != "phpbb-extension")
		{
			$this->rrmdir($phpbb_root_path . 'ext/tmp');
			$file->remove();
			trigger_error($user->lang['NOT_AN_EXTENSION'] . adm_back_link($this->u_action), E_USER_WARNING);
		}
		$source = substr($composery, 0, -14);
		/* Delete the previous version of extension files - we're able to update them. */
		if (is_dir($phpbb_root_path . 'ext/' . $destination))
		{
			$this->rrmdir($phpbb_root_path . 'ext/' . $destination);
		}
		$this->rcopy($source, $phpbb_root_path . 'ext/' . $destination);
		$this->rrmdir($phpbb_root_path . 'ext/tmp');
					
		$template->assign_vars(array(
			'S_UPLOADED'	=> $user->lang('EXTENSION_UPLOADED', $display_name),
			'S_ACTION'		=> $phpbb_root_path . 'adm/index.php?i=acp_extensions&amp;sid=' .$user->session_id . '&amp;mode=main&amp;action=enable_pre&amp;ext_name=' . urlencode($destination),
			'U_ACTION'		=> $this->u_action
		));

		// Remove the uploaded archive file
		$file->remove();

		return true;
	}
	
	/**
	 * @author Michal Nazarewicz (from the php manual)
	 * Creates all non-existant directories in a path
	 * @param $path - path to create
	 * @param $mode - CHMOD the new dir to these permissions
	 * @return bool
	 */
	function recursive_mkdir($path, $mode = false)
	{
		if (!$mode)
		{
			global $config;
			$mode = octdec($config['am_dir_perms']);
		}

		$dirs = explode('/', $path);
		$count = sizeof($dirs);
		$path = '.';
		for ($i = 0; $i < $count; $i++)
		{
			$path .= '/' . $dirs[$i];

			if (!is_dir($path))
			{
				@mkdir($path, $mode);
				@chmod($path, $mode);

				if (!is_dir($path))
				{
					return false;
				}
			}
		}
		return true;
	}
}
