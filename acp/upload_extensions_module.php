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
	function main($id, $mode)
	{
		global $db, $config, $user, $cache, $template, $request, $phpbb_root_path, $phpbb_extension_manager, $phpbb_container;

		$this->page_title = $user->lang['ACP_UPLOAD_EXT_TITLE'];
		$this->tpl_name = 'acp_upload_extensions';
		
		if (request_var('action', '') == 'delete')
		{
			if (request_var('ext_name', '') != '')
			{
				$dir = substr(request_var('ext_name', ''), 0, strpos(request_var('ext_name', ''), '/'));
				$this->rrmdir($phpbb_root_path . 'ext/' . $dir); 
			}
		}
		
		$file = request_var('file', '');
		if ($file != '')
		{
			$string = file_get_contents($file);
			$template->assign_vars(array('FILENAME' => substr($file, strrpos($file, '/') + 1),'CONTENT' => highlight_string($string, true)));
		}

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
					
					$template->assign_vars(array('UPLOAD' => 'Package ' . $display_name . ' uploaded.'));
					
					foreach ($json_a['authors'] as $author)
					{
						$template->assign_block_vars('authors', array(
							'AUTHOR'	=> $author['name'],
						));
					}
					
					$template->assign_vars(array('FILETREE' => $this->php_file_tree($phpbb_root_path . 'ext/' . $destination, $display_name)));
					$template->assign_vars(array('U_ACTION' => '/adm/index.php?i=acp_extensions&amp;sid=' .$user->session_id . '&amp;mode=main&amp;action=enable_pre&amp;ext_name=' . urlencode($destination)));
					$template->assign_vars(array('U_ACTION_DELL' => $this->u_action . '&amp;action=delete&amp;ext_name=' . urlencode($destination)));
				} else
				{
					$template->assign_vars(array('UPLOAD_EXT_ERROR' => 'No vendor or destianation folder'));	
				}
			} else
			{
				$template->assign_vars(array('UPLOAD_EXT_ERROR' => 'composer.json not found'));	
			}
		} else 
		{
			$template->assign_vars(array('UPLOAD_EXT_ERROR' => $user->lang['ziperror'][$res]));
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
		$code = 'Content of package: ' . $display_name . '<br /><br />';
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
						//$link = str_replace('[link]', $directory . '/' . urlencode($this_file), $return_link);
						$php_file_tree .= '<li class="pft-file ' . strtolower($ext) . '"><a href="' . $this->u_action . '&amp;file=' . $directory . '/' . urlencode($this_file) . '" title="' . $this_file . '">' . htmlspecialchars($this_file) . '</a></li>';
					}
				}
			}
			$php_file_tree .= '</ul>';
		}
		return $php_file_tree;
	}

}
