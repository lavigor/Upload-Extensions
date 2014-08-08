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
	private $config;
	private $user;
	private $cache;
	private $template;
	private $request;

	function main($id, $mode)
	{
		global $config, $user, $cache, $template, $request, $phpbb_root_path, $phpbb_extension_manager;

		$this->config = $config;
		$this->user = $user;
		$this->cache = $cache;
		$this->template = $template;
		$this->request = $request;

		$this->page_title = $user->lang['ACP_UPLOAD_EXT_TITLE'];
		$this->tpl_name = 'acp_upload_extensions';

		$action = request_var('action', '');

		$file = request_var('file', '');
		if ($file != '')
		{
			$string = file_get_contents($file);
			echo substr($file, strrpos($file, '/') + 1) . '<br><br>'.  highlight_string($string, true);
			exit;
		}

		switch ($action)
		{
			case 'details':
				$user->add_lang(array('install', 'acp/extensions', 'migrator'));
				$ext_name = 'forumhulp/upload_extensions';
				$md_manager = new \phpbb\extension\metadata_manager($ext_name, $config, $phpbb_extension_manager, $this->template, $user, $phpbb_root_path);
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
	
					$this->template->assign_vars(array(
						'S_UP_TO_DATE'		=> empty($updates_available),
						'S_VERSIONCHECK'	=> true,
						'UP_TO_DATE_MSG'	=> $user->lang(empty($updates_available) ? 'UP_TO_DATE' : 'NOT_UP_TO_DATE', $md_manager->get_metadata('display-name')),
					));
	
					foreach ($updates_available as $branch => $version_data)
					{
						$this->template->assign_block_vars('updates_available', $version_data);
					}
				}
				catch (\RuntimeException $e)
				{
					$this->template->assign_vars(array(
						'S_VERSIONCHECK_STATUS'			=> $e->getCode(),
						'VERSIONCHECK_FAIL_REASON'		=> ($e->getMessage() !== $user->lang('VERSIONCHECK_FAIL')) ? $e->getMessage() : '',
					));
				}
	
				$this->template->assign_vars(array(
					'U_BACK'				=> $this->u_action . '&amp;action=list',
				));
	
				$this->tpl_name = 'acp_ext_details';
			break;
			
			case 'upload':
				sleep(5); // For upload test
				
				$ext_name = 'boardrules-1.0.0-b2.zip';
				$zip = new \ZipArchive;
				$res = $zip->open($phpbb_root_path . 'ext/' . $ext_name);
				if ($res === true) 
				{
					$zip->extractTo($phpbb_root_path . 'ext/tmp');
					$zip->close();
				  
					$composery = $this->listFolderFiles($phpbb_root_path . 'ext/tmp'); 
				
					if ($composery)
					{
						$string = file_get_contents($composery);
						$json_a=json_decode($string,true);
						$destination = $json_a['name'];
						if (strpos($destination, '/'))
						{
							$display_name = $json_a['extra']['display-name'];
							$source = substr($composery, 0, -14);
							$this->rcopy($source, $phpbb_root_path . 'ext/' . $destination);
							$this->rrmdir($phpbb_root_path . 'ext/tmp');
														
							foreach ($json_a['authors'] as $author)
							{
								$this->template->assign_block_vars('authors', array(
									'AUTHOR'	=> $author['name'],
								));
							}
							
							$this->template->assign_vars(array(
								'UPLOAD'	=> sprintf($user->lang['ACP_UPLOAD_PACK_UPLOAD'], $display_name),
								'FILETREE'	=> $this->php_file_tree($phpbb_root_path . 'ext/' . $destination, $display_name),
								'S_ACTION'	=> '/adm/index.php?i=acp_extensions&amp;sid=' .$user->session_id . '&amp;mode=main&amp;action=enable_pre&amp;ext_name=' . urlencode($destination),
								'S_ACTION_DELL' => $this->u_action . '&amp;action=delete&amp;ext_name=' . urlencode($destination),
								'U_ACTION'	=> $this->u_action
							));
						} else
						{
							$this->template->assign_vars(array('UPLOAD_EXT_ERROR' => $user->lang['ACP_UPLOAD_EXT_ERROR_DEST']));	
						}
					} else
					{
						$this->template->assign_vars(array('UPLOAD_EXT_ERROR' => $user->lang['ACP_UPLOAD_EXT_ERROR_COMP']));	
					}
				} else 
				{
					$this->template->assign_vars(array('UPLOAD_EXT_ERROR' => $user->lang['ziperror'][$res]));
				}
			break;
			
			case 'delete':
				if (request_var('ext_name', '') != '')
				{
					$dir = substr(request_var('ext_name', ''), 0, strpos(request_var('ext_name', ''), '/'));
					$extensions = sizeof(array_filter(glob($phpbb_root_path . 'ext/' . $dir . '/*'), 'is_dir'));
					$dir = ($extensions == 1) ? $dir : substr(request_var('ext_name', ''), strpos(request_var('ext_name', '') + 1, '/'));
					$this->rrmdir($phpbb_root_path . 'ext/' . $dir); 
				}

			default:
				$this->list_available_exts($phpbb_extension_manager);
			
				$this->template->assign_vars(array('DEFAULT' => true, 'U_ACTION' => $this->u_action));
			break;
		}
	}
	
	
	function listFolderFiles($dir)
	{
		global $composer;
		$ffs = scandir($dir);
		$composer = false;
		foreach($ffs as $ff)
		{
			if ($ff != '.' && $ff != '..')
			{
				if ($ff == 'composer.json') {$composer = $dir . '/' . $ff; break;}
	
				if(is_dir($dir.'/'.$ff)) $this->listFolderFiles($dir . '/' . $ff);	
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

	function php_file_tree($directory, $display_name, $extensions = array()) 
	{
		$code = $this->user->lang['ACP_UPLOAD_EXT_CONT'] . $display_name . '<br /><br />';
		if(substr($directory, -1) == '/' ) $directory = substr($directory, 0, strlen($directory) - 1);
		$code .= $this->php_file_tree_dir($directory, $extensions);
		return $code;
	}

	function php_file_tree_dir($directory, $extensions = array(), $first_call = true) 
	{
		if (function_exists('scandir')) $file = scandir($directory); else $file = php4_scandir($directory);
		natcasesort($file);
	
		// Make directories first
		$files = $dirs = array();
		foreach($file as $this_file)
		{
			if (is_dir($directory . '/' . $this_file)) $dirs[] = $this_file; else $files[] = $this_file;
		}
		$file = array_merge($dirs, $files);
	
		// Filter unwanted extensions
		if (!empty($extensions))
		{
			foreach(array_keys($file) as $key)
			{
				if (!is_dir($directory . '/' . $file[$key]))
				{
					$ext = substr($file[$key], strrpos($file[$key],  '.') + 1); 
					if (!in_array($ext, $extensions)) unset($file[$key]);
				}
			}
		}
	
		if (count($file) > 2)
		{ // Use 2 instead of 0 to account for . and .. directories
			$php_file_tree = '<ul';
			if ($first_call) 
			{
				$php_file_tree .= ' class="php-file-tree"'; $first_call = false;
			}
			$php_file_tree .= '>';
			foreach($file as $this_file)
			{
				if ($this_file != '.' && $this_file != '..' )
				{
					if (is_dir($directory . '/' . $this_file))
					{
						// Directory
						$php_file_tree .= '<li class="pft-directory"><a href="#">' . htmlspecialchars($this_file) . '</a>';
						$php_file_tree .= $this->php_file_tree_dir($directory . '/' . $this_file, $extensions, false);
						$php_file_tree .= '</li>';
					} else 
					{
						// File
						// Get extension (prepend 'ext-' to prevent invalid classes from extensions that begin with numbers)
						$ext = 'ext-' . substr($this_file, strrpos($this_file, '.') + 1); 
						$link = $this->u_action . '&amp;file=' . $directory . '/' . urlencode($this_file); 
						$php_file_tree .= '<li class="pft-file ' . strtolower($ext) . '"><a href="javascript:void(0)" onclick="loadXMLDoc(\''. $link . '\')" title="' . $this_file . '">' . htmlspecialchars($this_file) . '</a></li>';
					}
				}
			}
			$php_file_tree .= '</ul>';
		}
		return $php_file_tree;
	}

	/**
	* Lists all the available extensions and dumps to the template
	*
	* @param  $phpbb_extension_manager     An instance of the extension manager
	* @return null
	*/
	public function list_available_exts(\phpbb\extension\manager $phpbb_extension_manager)
	{
		$uninstalled = array_diff_key($phpbb_extension_manager->all_available(), $phpbb_extension_manager->all_configured());

		$available_extension_meta_data = array();

		foreach ($uninstalled as $name => $location)
		{
			$md_manager = $phpbb_extension_manager->create_extension_metadata_manager($name, $this->template);

			try
			{
				$meta = $md_manager->get_metadata('all');
				$available_extension_meta_data[$name] = array(
					'META_DISPLAY_NAME' => $md_manager->get_metadata('display-name'),
					'META_VERSION' => $meta['version'],
				);

				$force_update = $this->request->variable('versioncheck_force', false);
				$updates = $this->version_check($md_manager, $force_update, !$force_update);

				$available_extension_meta_data[$name]['S_UP_TO_DATE'] = empty($updates);
				$available_extension_meta_data[$name]['S_VERSIONCHECK'] = true;
				$available_extension_meta_data[$name]['U_VERSIONCHECK_FORCE'] = $this->u_action . '&amp;action=details&amp;versioncheck_force=1&amp;ext_name=' . urlencode($md_manager->get_metadata('name'));
			}
			catch(\phpbb\extension\exception $e)
			{
				$this->template->assign_block_vars('disabled', array(
					'META_DISPLAY_NAME'		=> $this->user->lang('EXTENSION_INVALID_LIST', $name, $e),
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

			$this->template->assign_block_vars('disabled', $block_vars);
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
}
