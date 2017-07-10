# Jodit FileBrowser Connector for Jodit 3.0
Official [Jodit WYSIWYG](http://xdsoft.net/jodit) PHP connector
## Install
```
composer require jodit/connector
```
## Options
Update `config.jodit.php` in connector's root directory.
Rewrite the function check permissions in Application.php
```php
function checkPermissions () {
    /********************************************************************************/
    if (empty($_SESSION['filebrowser'])) {
        throw new \ErrorException('You do not have permission to view this directory', 403);
    }
    /********************************************************************************/
}
```

and adjust options 
* `$config['quality']` - image quality
* `$config['root']` - the root directory for user files
* `$config['baseurl']` - Root URL for user files (exp. `http://xdsoft.net`)
* `$config['createThumb']` - boolean, true - create thumbnails for previews (`true`)
* `$config['thumbFolderName']` - thumbnails folder
* `$config['excludeDirectoryNames']` - exclude these folders
* `$config['extensions']` - an array of valid file extensions that are permitted to be loaded (`['jpg', 'png', 'gif', 'jpeg']`)
* `$config['maxFileSize']` - Maximum file size (0 - is unlimited) default 8Mb

and you can defined several sources 
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