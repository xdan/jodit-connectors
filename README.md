# Jodit FileBrowser Connector for Jodit v.3.0

Official [Jodit WYSIWYG](http://xdsoft.net/jodit) PHP connector
---
[old version](https://github.com/xdan/jodit-connectors/tree/2.5.62) for Jodit 2.x

## Install

```
composer create-project --no-dev jodit/connector
```

or download [ZIP archive](https://xdsoft.net/jodit/store/connector.zip)

## Configuration

Available options:

* `$config['saveSameFileNameStrategy'] = "addNumber"` - Strategy in case the uploaded file has the same name as the file
  on the server.
    - "addNumber" - The number "olsen.png" => "olsen(1).png" is added number to the file name, if such a file exists, it
      will be "olsen(2).png", etc.
    - "replace" - Just replace the file
    - "error" - Throw the error - "File already exists"
* `$config['quality'] = 90` - image quality
* `$config['datetimeFormat'] = 'd/m/Y'` - Date format
* `$config['root'] = __DIR__` - the root directory for user files
* `$config['baseurl'] = ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/'` - Root URL for user
  files (exp. `http://xdsoft.net`)
* `$config['createThumb'] = true` - boolean, true - create thumbnails for previews (`true`)
* `$config['safeThumbsCountInOneTime'] = 20` - int, If the `createThumb` option is enabled, then with a large number of
  files in the folder, the server will noticeably slow down when generating so many previews.
  Therefore, at a time, only such a number of pictures are processed.
* `$config['thumbFolderName'] = '_thumbs'` - thumbnails folder
* `$config['excludeDirectoryNames'] = ['.tmb', '.quarantine'],` - exclude these folders
* `$config['extensions'] = ['jpg', 'png', 'gif', 'jpeg']` - an array of valid file extensions that are permitted to be
  loaded (`['jpg', 'png', 'gif', 'jpeg']`)
* `$config['maxFileSize'] = 8mb` - Maximum file size (0 - is unlimited) default 8Mb
* `$config['allowCrossOrigin'] = false` - Allow cross origin request
* `$config['allowReplaceSourceFile'] = true` - Allow replace source image on resized or croped version
* `$config['sources']` - Array of options
* `$config['accessControl']` - Array for checking allow/deny permissions [Read more](#access-control)
* `$config['defaultRole']="guest"` - Default role for [Access Control](#access-control)
* `$config['roleSessionVar']="JoditUserRole"` - The session key name that Jodit connector will use for checking the role
  for current user. [Read more](#access-control)

you can defined several sources, and override some options:

```php
$config['sources'] = [
    'images' => [
        'root' => __DIR__ . '/images',
        'baseurl' => 'http://xdsoft.net/images',
        'maxFileSize' => '100kb',
        'createThumb' => false,
        'extensions' => ['jpg'],
    ]
];
```

## How use with [Jodit](https://github.com/xdan/jodit/)

Filebrowser settings  [Detailt options](https://xdsoft.net/jodit/doc/#modules-filebrowser-filebrowser)

```javascript
new Jodit('#editor', {
    filebrowser: {
        ajax: {
            url: 'connector/index.php'
        }
    }
});
```

and uploader options [Default options](https://xdsoft.net/jodit/doc/#modules-uploader)

```javascript
new Jodit('#editor', {
    uploader: {
        url: 'connector/index.php?action=fileUpload',
    }
});
```

### Customize config

Change `config.php`
> Do not modify the default.config.php file, instead, override the settings in the config.php file

```php
return [
    'sources' => [
        'joomla Images' => [
            'root' => JPATH_BASE.'/images/',
            'baseurl' => '/images/',
            'createThumb' => true,
            'thumbFolderName' => '_thumbs',
            'extensions' => array('jpg', 'png', 'gif', 'jpeg'),
        ],
        'joomla Media' => [
            'root' => JPATH_BASE.'/media/',
            'baseurl' => '/medias/',
            'createThumb' => false,
            'thumbFolderName' => '_thumbs',
            'extensions' => array('jpg', 'png', 'gif', 'jpeg'),
        ],
    ]
];
```

## Authentication

Change `connector/checkAuthentication` in `connector/Application.php`

Like this:

```php
function checkAuthentication () {
    /********************************************************************************/
    if (empty($_SESSION['filebrowser'])) {
        throw new \ErrorException('You do not have permission to view this directory', 403);
    }
    /********************************************************************************/
}
```

### Example Integrate with Joomla

Change `Application.php`

```php
<?php
define('_JEXEC', 1);
define('JPATH_BASE', realpath(realpath(__DIR__).'/../../../../../')); // replace to valid path

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

JFactory::getApplication('site');


class JoditRestApplication extends \jodit\JoditApplication {
    function checkAuthentication() {
        $user = JFactory::getUser();
        if (!$user->id) {
            trigger_error('You are not authorized!', E_USER_WARNING);
        }
    }
}

```

You can use `$action` for allow or deny access

```php
function checkPermissions () {
    /********************************************************************************/
    if (!empty($_SESSION['filebrowser'])) {
        switch ($this->action) {
        case "imageResize":
        case "fileMove":
        case "folderCreate":
        case "fileRemove":
        case "fileUploadRemote":
        case "fileUpload":
            throw new \ErrorException('You do not have permission to view this action', 403);
        }
        return true;
    }
    
    throw new \ErrorException('You do not have permission to view this directory', 403);
    /********************************************************************************/
}
```

but better use `AllowControl` option

## Access Control

`roleSessionVar` - The session key name that Jodit connector will use for checking the role for current user.

```php
$config['roleSessionVar'] = 'JoditUserRole';
```

After this you will be able to use `$_SESSION['JoditUserRole']` to set inside your script - user role, after that user
was authenticated:

Somewhere in your script

```php
session_start();
//...
$_SESSION['JoditUserRole'] = 'administrator';
```

In `deafult.config.php` you can find default ACL config

```php
$config['roleSessionVar'] = 'JoditUserRole';

$config['accessControl'][] = array(
	'role'                => '*',
	'extensions'          => '*',
	'path'                => '/',
	'FILES'               => true,
	'FILE_MOVE'           => true,
	'FILE_UPLOAD'         => true,
	'FILE_UPLOAD_REMOTE'  => true,
	'FILE_REMOVE'         => true,
	'FILE_RENAME'         => true,

	'FOLDERS'             => true,
	'FOLDER_MOVE'         => true,
	'FOLDER_REMOVE'       => true,
	'FOLDER_RENAME'       => true,

	'IMAGE_RESIZE'        => true,
	'IMAGE_CROP'          => true,
);

$config['accessControl'][] = array(
	'role'                => '*',
   
	'extensions'          => 'exe,bat,com,sh,swf',

	'FILE_MOVE'           => false,
	'FILE_UPLOAD'         => false,
	'FILE_UPLOAD_REMOTE'  => false,
	'FILE_RENAME'         => false,
);
```

It means that all authenticated user will have all permissions but they are not allowed to download executable files.

In `config.php` you can customize it. For example set read-only permission for all users, but give to users with the
role - `administrator` full access:

```php
$config['accessControl'][] = Array(
 'role'                => '*',

 'FILES'               => false,
 'FILE_MOVE'           => false,
 'FILE_UPLOAD'         => false,
 'FILE_UPLOAD_REMOTE'  => false,
 'FILE_REMOVE'         => false,
 'FILE_RENAME'         => false,
 
 'FOLDERS'             => false,
 'FOLDER_MOVE'         => false,
 'FOLDER_REMOVE'       => false,
 'FOLDER_RENAME'       => false,
 
 'IMAGE_RESIZE'        => false,
 'IMAGE_CROP'          => false,
);

$config['accessControl'][] = Array(
 'role' => 'administrator',
 'FILES'               => true,
 'FILE_MOVE'           => true,
 'FILE_UPLOAD'         => true,
 'FILE_UPLOAD_REMOTE'  => true,
 'FILE_REMOVE'         => true,
 'FILE_RENAME'         => true,
 
 'FOLDERS'             => true,
 'FOLDER_MOVE'         => true,
 'FOLDER_REMOVE'       => true,
 'FOLDER_RENAME'       => true,
 
 'IMAGE_RESIZE'        => true,
 'IMAGE_CROP'          => true,
);
```

### API

> All actions case-sensitive

### Actions

#### permissions - get permissions to current path. This action should call after every changing path.

```
GET index.php?action=permission&source=:source&path=:path
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root

See [`tests/api/PermissionsCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/PermissionsCept.php)

Answer JSON example:

```JSON
{
    "success": true,
    "time": "2018-03-05 10:14:44",
    "data": {
        "permissions": {
            "allowFiles": true,
            "allowFileMove": true,
            "allowFileUpload": true,
            "allowFileUploadRemote": true,
            "allowFileRemove": true,
            "allowFileRename": true,
            "allowFolders": true,
            "allowFolderMove": true,
            "allowFolderCreate": true,
            "allowFolderRemove": true,
            "allowFolderRename": true,
            "allowImageResize": true,
            "allowImageCrop": true
        },
        "code": 220
    }
}
```

#### files - Get all files from folder

```
GET index.php?action=files&source=:source&path=:path
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root

See [`tests/api/getAllFilesByAllSourcesCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/getAllFilesByAllSourcesCept.php)
and  [`tests/api/getAllFilesByOneSourceCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/getAllFilesByOneSourceCept.php)

Answer JSON example:

```JSON
{
    "success": true,
    "time": "2017-07-10 17:10:26",
    "data": {
        "sources": {
            "test": {
                "baseurl": "http://localhost:8181/tests/files/",
                "path": "",
                "files": [
                    {
                        "file": "artio.jpg",
                        "thumb": "_thumbs\\artio.jpg",
                        "changed": "07/07/2017 3:06 PM",
                        "size": "53.50kB"
                    }
                ]
            },
            "folder1": {
                "baseurl": "http://localhost:8181/tests/files/folder1/",
                "path": "",
                "files": [
                    {
                        "file": "artio2.jpg",
                        "thumb": "_thumbs\\artio2.jpg",
                        "changed": "07/07/2017 3:06 PM",
                        "size": "53.50kB"
                    }
                ]
            }
        },
        "code": 220
    }
}
```

#### folders - Get all folders from path

```
GET index.php?action=folders&source=:source&path=:path
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.

See [`tests/api/getAllFoldersByAllSourcesCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/getAllFoldersByAllSourcesCept.php)
and  [`tests/api/getAllFoldersByOneSourceCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/getAllFoldersByOneSourceCept.php)

Answer JSON example:

```JSON
{
    "success": true,
    "time": "2017-07-10 17:11:10",
    "data": {
        "sources": {
            "test": {
                "baseurl": "http://localhost:8181/tests/files/",
                "path": "",
                "folders": [
                    ".",
                    "folder1"
                ]
            },
            "folder1": {
                "baseurl": "http://localhost:8181/tests/files/folder1/",
                "path": "",
                "folders": [
                    ".",
                    "folder2"
                ]
            }
        },
        "code": 220
    }
}
```

#### fileUploadRemote - Download image from another server

```
GET index.php?action=fileUploadRemote&source=:source&path=:path&url=:url
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :url - full image URL

See [`tests/api/uploadImageByUrlToServerCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/uploadImageByUrlToServerCept.php)

Answer JSON example:

```JSON
{
    "success": true,
    "time": "2017-07-10 17:13:49",
    "data": {
        "newfilename": "icon-joomla.png",
        "baseurl": "http://localhost:8181/tests/files/",
        "code": 220
    }
}
```

#### fileUpload - Upload files to server

```
POST index.php
$_POST = [
    action=fileUpload,
    source=:source,
    path=:path,
]
$_FILES = [
    files=[...]
]
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :files - files

See [`tests/api/uploadImageToServerCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/uploadImageToServerCept.php)

#### fileRemove - Remove file

```
GET index.php?action=fileRemove&source=:source&path=:path&name=:name
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - file name or folder name

See [`tests/api/removeImageFromServerCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/removeImageFromServerCept.php)

#### folderRemove - Remove folder from server

```
GET index.php?action=folderRemove&source=:source&path=:path&name=:name
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - file name or folder name

#### folderCreate - Create folder on server

```
GET index.php?action=folderCreate&source=:source&path=:path&name=:name
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - new folder name

See [`tests/api/createFolderCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/createFolderCept.php)

#### folderMove - Move folder to another place

```
GET index.php?action=folderMove&source=:source&path=:path&from=:from
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root. This is where the file will be move
* :from - relative path (from source.root) file or folder

See [`tests/api/moveFileCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/moveFileCept.php)

#### fileMove - Move file to another place

```
GET index.php?action=fileMove&source=:source&path=:path&from=:from
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root. This is where the file will be move
* :from - relative path (from source.root) file or folder

See [`tests/api/moveFileCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/moveFileCept.php)

#### imageResize - Resize image

```
GET index.php?action=imageResize&source=:source&path=:path&name=:name&box[w]=:box_width&box[h]=:box_height
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - File source in `:path`
* :newname - new file name in `:path`. Can be equal `:name`
* :box - new width and height

See [`tests/api/resizeImageCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/resizeImageCept.php)

#### imageCrop - Crop image

```
GET index.php?action=crop&source=:source&path=:path&name=:name&box[w]=:box_width&box[h]=:box_height&box[x]=:box_start_x&box[y]=:box_start_y
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - File source in `:path`
* :newname - new file name in `:path`. Can be equal `:name`
* :box - bounding box

See [`tests/api/cropImageCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/cropImageCept.php)

#### getLocalFileByUrl - Get local file by URL

```
GET index.php?action=getlocalfilebyurl&source=:source&path=:path&url=:url
```

* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :url - Full fil url for source

See [`tests/api/getlocalFileByURLCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/getlocalFileByURLCept.php)
Example:

```
index.php?action=getLocalFileByUrl&source=test&url=http://localhost:8181/tests/files/artio.jpg
```

Answer JSON example:

```JSON
{
    "success": true,
    "time": "2017-07-10 17:34:29",
    "data": {
        "path": "",
        "name": "artio.jpg",
        "code": 220
    }
}
```

### Road map

- [x] Cross Origin requests
- [ ] Add pagination
- [ ] Create FTP/SFTP sources
- [ ] Create image filters (noise, gray scale etc.)

### Contacts

* [chupurnov@gmail.com](mailto:chupurnov@gmail.com)
* Website [xdsoft.net](https://xdsoft.net)
* [Jodit](https://xdsoft.net/jodit/)



 
