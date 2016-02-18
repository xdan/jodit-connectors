# Jodit FileBrowser Connector
Official [Jodit WYSIWYG](http://xdsoft.net/jodit) connector
## Options
Open `index.php`
Rewrite the function check permissions. By default it has view
```php
function checkPermissions () {
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
