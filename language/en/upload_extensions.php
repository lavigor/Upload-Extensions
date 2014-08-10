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
	'ACP_UPLOAD_EXT_TITLE'			=> 'Upload Extensions',
	'ACP_UPLOAD_EXT_CONFIG_TITLE'	=> 'Upload Extensions',

	'UPLOAD'							=> 'Upload',
	'MOD_UPLOAD'				=> 'Upload Extension',
	'MOD_UPLOAD_EXPLAIN'		=> 'Here you can upload a zipped Extension package containing the necessary files to perform installation. Upload Extensions will then attempt to unzip the file and have it ready for installation.',
	'MOD_UPLOAD_INIT_FAIL'		=> 'There was an error initialising the Extension upload process.',
	'MOD_UPLOAD_SUCCESS'		=> 'Extension uploaded and prepared for installation.',
	'MOD_UPLOAD_UNRECOGNIZED'	=> 'The directory structure of the uploaded Extension archive is unrecognized. Check if the zip archive you uploaded is corrupt or missing any files/directories, or contact the Extension Author.',
	'NO_UPLOAD_FILE'			=> 'No_upload file',

	'ACP_UPLOAD_EXT_TITLES_EXPLAIN'	=> 'Upload Extensions enables you to upload an extension zip file, unpack and copy all the files to its desired folder in your extension folder.',
	'ACP_UPLOAD_EXT_CONT'			=> 'Content of package: ',

	'EXTENSIONS_DISABLED'			=> 'Extension available',
	'EXTENSION_INVALID_LIST'		=> 'Extensionlist',
	
	'ACP_UPLOAD_EXT_DELL'			=> 'Delete extension',
	'ACP_UPLOAD_EXT_UNPACK'			=> 'Unpack extension',
	'ACP_UPLOAD_PACK_UPLOAD'		=> 'Package %s uploaded.',

	'ACP_UPLOAD_EXT_ERROR_DEST'		=> 'No vendor or destination folder',
	'ACP_UPLOAD_EXT_ERROR_COMP'		=> 'composer.json not found',
	
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
	),

	'ZIP_UPLOADED'			=> 'Extension zips already uploaded.',
	'EXT_DELETE_SUCCESS'	=> 'Extension deleted succesfully'
));