<?php
/**
*
* @package Upload Extensions
* @copyright (c) 2014 ForumHulp.com
* @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
*
*/

if (!defined('IN_PHPBB'))
{
        exit;
}

if (empty($lang) || !is_array($lang))
{
        $lang = array();
}

$lang = array_merge($lang, array(
	'ACP_UPLOAD_EXT_TITLE'				=> 'Upload Extensions',
	'ACP_UPLOAD_EXT_CONFIG_TITLE'		=> 'Upload Extensions',


	'ACP_UPLOAD_EXT_TITLES_EXPLAIN'			=> 'Upload Extensions enables you to upload an extension zip file, unpack and copy all the files to its desired folder in your extension folder.',
	
	'ziperror'		=> array(
		'10'		=> 'File already exists.',
		'21'		=> 'Zip archive inconsistent.',
		'18'		=> 'Invalid argument.',
		'14'		=> 'Malloc failure.',
		'9'			=> 'No such file.',
		'19'		=> 'Not a zip archive.',
		'11'		=> 'Can\'t open file.',
		'5'			=> 'Read error.',
		'4'			=> 'Seek error.'
	)

));