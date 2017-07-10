# Jodit FileBrowser Connector for Jodit 3.0
Official [Jodit WYSIWYG](http://xdsoft.net/jodit) PHP connector
---
[old version](https://github.com/xdan/jodit-connectors/tree/2.5.62) for Jodit 2.x
## Install
```
composer require jodit/connector
```
## Configuration
Change `checkPermissions` in `Application.php`

Like this:
```php
function checkPermissions () {
    /********************************************************************************/
    if (empty($_SESSION['filebrowser'])) {
        throw new \ErrorException('You do not have permission to view this directory', 403);
    }
    /********************************************************************************/
}
```
Change `config.php`
Available options:
* `$config['quality']` - image quality
* `$config['root']` - the root directory for user files
* `$config['baseurl']` - Root URL for user files (exp. `http://xdsoft.net`)
* `$config['createThumb']` - boolean, true - create thumbnails for previews (`true`)
* `$config['thumbFolderName']` - thumbnails folder
* `$config['excludeDirectoryNames']` - exclude these folders
* `$config['extensions']` - an array of valid file extensions that are permitted to be loaded (`['jpg', 'png', 'gif', 'jpeg']`)
* `$config['maxFileSize']` - Maximum file size (0 - is unlimited) default 8Mb

you can defined several sources:
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


## How use
Filebrowser settings  [Detailt options](http://xdsoft.net/jodit/doc/Jodit.defaultOptions.html#toc13__anchor)
```javascript
new Jodit('#editor', {
    filebrowser: {
        ajax: {
            url: 'connector/index.php',
            process: function (resp) {
               return {
                    resp.files || [], // {array} The names of files or folders
                    path: resp.path, // {string} Real relative path
                    baseurl: resp.baseurl, // {string} Base url for filebrowser
                    error: resp.error, // {int}
                    msg: resp.msg // {string}
                };
            }
        }
    }
});
```
and uploader options [Default options](http://xdsoft.net/jodit/doc/Jodit.defaultOptions.html#toc27__anchor)
```javascript
new Jodit('#editor', {
    uploader: {
        url: 'connector/index.php?action=upload',
    }
});
```

### Example Integrate with Joomla

#### Change `Application.php`
```php
<?php
define('_JEXEC', 1);
define('JPATH_BASE', realpath(realpath(__DIR__).'/../../../../../')); // replace to valid path
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

JFactory::getApplication('site');


class JoditRestApplication extends \jodit\JoditApplication {
    function checkPermissions() {
        
        $user = JFactory::getUser();
        if (!$user->id) {
            trigger_error('You are not authorized!', E_USER_WARNING);
        }
        throw new ErrorException('You need override `checkPermissions` method in file `Application.php`', 501);
    }
}

```

#### Change `config.php`
```php
return [
    'sources' => [
        'joomla Images' => [
            'root' => JPATH_BASE.'/images/',
            'baseurl' => '/images/',
            'createThumb' => true,
            'thumbFolderName' => '_thumbs',
            'thumbFolderName' => array('.tmb', '.quarantine'),
            'extensions' => array('jpg', 'png', 'gif', 'jpeg'),
        ],
        'joomla Media' => [
            'root' => JPATH_BASE.'/media/',
            'baseurl' => '/medias/',
            'createThumb' => false,
            'thumbFolderName' => '_thumbs',
            'thumbFolderName' => array('.tmb', '.quarantine'),
            'extensions' => array('jpg', 'png', 'gif', 'jpeg'),
        ],
    ]
];
```


### Run tests
Start PHP server
```$xslt
npm start
```
Run tests
```$xslt
npm test
```

### API
#### files - Get all files from folder
```
GET index.php?action=files&source=:source&path=:path
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root

See [`tests/api/getAlFilesByAllSourcesCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/getAlFilesByAllSourcesCept.php) and  [`tests/api/getAllFilesByOneSourceCept.php`](https://github.com/xdan/jodit-connectors/blob/master/tests/api/getAllFilesByOneSourceCept.php)

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

See `tests/api/getAllFoldersByAllSourcesCept.php` and  `tests/api/getAllFoldersByOneSourceCept.php`

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

#### uploadremote - Download image from another server
```
GET index.php?action=uploadremote&source=:source&path=:path&url=:url
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :url - full image URL

See `tests/api/uploadImageByUrlToServerCept.php`

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

#### upload - Upload files to server
```
POST index.php
$_POST = [
    action=upload,
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

See `tests/api/uploadImageToServerCept.php`



#### remove - Remove file or folder from server
```
GET index.php?action=remove&source=:source&path=:path&name=:name
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - file name or folder name 

See `tests/api/removeImageFromServerCept.php`


#### create - Create folder on server
```
GET index.php?action=create&source=:source&path=:path&name=:name
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - new folder name 

See `tests/api/createFolderCept.php`

#### move - Move folder or file to another place
```
GET index.php?action=move&source=:source&path=:path&from=:from
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root. This is where the file will be move
* :from - relative path (from source.root) file or folder

See `tests/api/moveFileCept.php`


#### resize - Resize image
```
GET index.php?action=resize&source=:source&path=:path&name=:name&box[w]=:box_width&box[h]=:box_height
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - File source in `:path`
* :newname - new file name in `:path`. Can be equal `:name`
* :box - new width and height

See `tests/api/resizeImageCept.php`


#### crop - Crop image
```
GET index.php?action=crop&source=:source&path=:path&name=:name&box[w]=:box_width&box[h]=:box_height&box[x]=:box_start_x&box[y]=:box_start_y
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :name - File source in `:path`
* :newname - new file name in `:path`. Can be equal `:name`
* :box - bounding box

See `tests/api/cropImageCept.php`

#### getlocalfilebyurl - Get local file by URL
```
GET index.php?action=getlocalfilebyurl&source=:source&path=:path&url=:url
```
* [:source=default] - key from config (ex. from Joomla config - `joomla Media)
* [:path=source.root] - relative path for source.root.
* :url - Full fil url for source

See `tests/api/getlocalFileByURLCept.php`
Example:
```
index.php?action=getlocalfilebyurl&source=test&url=http://localhost:8181/tests/files/artio.jpg
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
- [ ] Create FTP/SFTP sources
- [ ] Create image filters (noise, gray scale etc.)

### Contacts
* [chupurnov@gmail.com](mailto:chupurnov@gmail.com)
* Website [xdsoft.net](http://xdsoft.net)
* [Jodit](http://xdsoft.net/jodit)



 