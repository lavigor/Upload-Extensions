Upload Extensions
===========

Upload Extensions upload the extension zip file and unpack and copy all the files to the extension folder named in composer.json

## Requirements
* phpBB 3.1.0-dev or higher
* PHP 5.3.3 or higher

## Installation
You can install this extension on the latest copy of the develop branch ([phpBB 3.1-dev](https://github.com/phpbb/phpbb3)) by doing the following:

1. Copy the [entire contents of this repo](https://github.com/ForumHulp/upload_extension/archive/master.zip) to `FORUM_DIRECTORY/ext/forumhulp/upload_extension/`.
2. Navigate in the ACP to `Customise -> Extension Management -> Manage extensions`.
3. Click Upload Extensions => `Enable`.

Note: This extension is in development. Installation is only recommended for testing purposes and is not supported on live boards. This extension will be officially released following phpBB 3.1.0. Extension depends on two core changes.

## Usage
### upload_extension page
To check the Upload Extensions navigate in the ACP to `Maintenance -> Upload Extensions -> Check Upload Extensions`.
Choose your extension zip file and click upload, the script wil unpack your file in the folder mentioned in composer.json. After this you are redirected to Manage extensions for enabling your extension.

## Update
1. Download the [latest ZIP-archive of `master` branch of this repository](https://github.com/ForumHulp/upload_extension/archive/master.zip).
2. Navigate in the ACP to `Customise -> Extension Management -> Manage extensions` and click Upload Extensions => `Disable`.
3. Copy the contents of `upload_extensions-master` folder to `FORUM_DIRECTORY/ext/forumhulp/upload_extension/`.
4. Navigate in the ACP to `Customise -> Extension Management -> Manage extensions` and click Upload Extensions => `Enable`.
5. Click `Details` or `Re-Check all versions` link to follow updates.

## Uninstallation
Navigate in the ACP to `Customise -> Extension Management -> Manage extensions` and click Upload Extensions => `Disable`.

To permanently uninstall, click `Delete Data` and then you can safely delete the `/ext/forumhulp/upload_extension/` folder.

## License
[GNU General Public License v2](http://opensource.org/licenses/GPL-2.0)

© 2014 - John Peskens (ForumHulp.com)