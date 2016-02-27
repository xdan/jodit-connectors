# Jodit FileBrowser Connector
Official [Jodit WYSIWYG](http://xdsoft.net/jodit) connector
## Install
```
composer require jodit/connector
```
## Options
Open `index.php`
Rewrite the function check permissions. By default it has view
```php
function checkPermissions () {
    if (function_exists('JoditCheckPermissions')) {
        return JoditCheckPermissions($this);
    }
    /********************************************************************************/
    // rewrite this code for your system
    if (empty($_SESSION['filebrowser'])) {
        $this->display(1, 'You do not have permission to view this directory');
    }
    /********************************************************************************/
}
```
and adjust options 
* `$config['root']` - the root directory for user files
* `$config['baseurl']` - Root URL for user files (exp. `http://xdsoft.net`)
* `$config['extensions']` - an array of valid file extensions that are permitted to be loaded (`['jpg', 'png', 'gif', 'jpeg']`)
* `$config['debug']` - Show reports of internal script errors (`false`)

## How use
Filebrowser settings  [Detailt options](http://xdsoft.net/jodit/doc/Jodit.defaultOptions.html#toc13__anchor)
```javascript
jQuery('#editor').jodit({
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
and uploader options [Detailt options](http://xdsoft.net/jodit/doc/Jodit.defaultOptions.html#toc27__anchor)
```javascript
jQuery('#editor').jodit({
    uploader: {
        url: 'connector/index.php?action=upload',
    }
});
```

### Example Intagrate with Joomla

#### Create `config.php`
```php
<?php
define('_JEXEC', 1);
define('JPATH_BASE', realpath(realpath(__DIR__).'/../../../../../')); // replace to valid path
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

$config = array(
    'root' => JPATH_BASE.'/images/',
    'baseurl' => '/images/',
    'extensions' => array('jpg', 'png', 'gif', 'jpeg'),
    'debug' => true,
);

$app = JFactory::getApplication('site');

function JoditCheckPermissions() {
    $user = JFactory::getUser();
    if (!$user->id) {
        trigger_error('You are not authorized!', E_USER_WARNING);
    }
}
```