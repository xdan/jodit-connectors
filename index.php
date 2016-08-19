<?php
require_once 'vendor/autoload.php';

class JoditFileBrowser {
    public $result;
    public $root;
    public $action;
    public $request = array();
    
    private function humanFilesize($bytes, $decimals = 2) {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    private function getImageEditorInfo() {
        $path = $this->getPath();
        $file = isset($this->request->file) ?  $this->request->file : '';;
        $box = isset($this->request->box) ?  (object)$this->request->box : '';
        $newname = !empty($this->request->newname) ?  $this->makeSafe($this->request->newname) : '';
        
        if (!$path || !file_exists($path . $file)) {
            trigger_error('Image file is not specified', E_USER_WARNING);
        }

        $img = new abeautifulsite\SimpleImage();

        try {
            $img->load($path . $file);
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }

        if ($newname) {
            $info = pathinfo($path . $file);
            $newname = $newname . '.' . $info['extension'];
            if (file_exists($path . $newname)) {                
                trigger_error('File ' . $newname . ' already exists', E_USER_WARNING);
            }
        } else {
            $newname = $file;
        }
        
        $info = $img->get_original_info();
        
        return (object)array(
            'path' => $path,
            'file' => $file,
            'box' => $box,
            'newname' => $newname,
            'img' => $img,
            'width' => $info['width'],
            'height' => $info['height'],
        );
    }

    private function translit ($str) {
        $str = (string)$str;

        $repl = array(
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
            'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'i','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
            ' '=>'-',
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'Yo','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'Y',
            'К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F',
            'Х'=>'H','Ц'=>'Ts','Ч'=>'CH','Ш'=>'Sh','Щ'=>'Shch','Ъ'=>'','Ы'=>'I','Ь'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya',
        );

        $str = strtr($str, $repl);

        return $str;
    }

    private function makeSafe($file) {
        $file = rtrim($this->translit($file), '.');
        $regex = array('#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#');
        return trim(preg_replace($regex, '', $file));
    }
    
    /**
     * Error handler
     *
     * @param {int} errno contains the level of the error raised, as an integer.
     * @param {string} errstr contains the error message, as a string.
     * @param {string} errfile which contains the filename that the error was raised in, as a string.
     * @param {string} errline which contains the line number the error was raised at, as an integer.
     */
    function errorHandler ($errno, $errstr, $file, $line) {
        $this->result->error = $errno ? $errno : 1;
        $this->result->msg = $errstr. ($this->config->debug ? ' Line:'.$line : '');
        $this->display();
    }
    
    /**
     * Display JSON
     */
    function display () {
        if (!$this->config->debug) {
            ob_end_clean();
        }
        if ($this->result->msg) {
            $this->result->msg = str_replace($this->config->root, '/', $this->result->msg);
        }
        exit(json_encode($this->result));
    }
    
    /**
     * Check whether the user has the ability to view files
     * You can define JoditCheckPermissions function in config.php and use it 
     */
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

    /**
     * Constructor FileBrowser
     * @param {array} $request Request
     */
    function __construct ($request, $config) {
        session_start();
        ob_start();
        header('Content-Type: application/json');

        set_error_handler(array($this, 'errorHandler'), $this->config->debug ? E_ALL : E_USER_WARNING);

        $this->request = (object)$request;
        $this->config  = (object)$config;
        $this->result  = (object)array('error'=> 1, 'msg' => array(), 'files'=> array());

        $this->action  = isset($this->request->action) ?  $this->request->action : 'items';

        $this->root  = isset($this->config->root) ?  realpath($this->config->root) . DIRECTORY_SEPARATOR : dirname(__FILE__) . DIRECTORY_SEPARATOR;

        if (!$this->root) {
            trigger_error('No root path', E_USER_WARNING);
        }

        if ($this->config->debug) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', 0);
        }

    }
    
    /**
     * Get current path
     */
    function getPath ($name = 'path') {
        $relpath = isset($this->request->{$name}) ?  $this->request->{$name} : '';
        $path = realpath($this->root) . DIRECTORY_SEPARATOR;

        //always check whether we are below the root category is not reached
        if (realpath($path.$relpath) && strpos(realpath($path.$relpath), $this->root) !== false) {
            $path = realpath($this->root.$relpath);
            if (is_dir($path)) {
                $path .= DIRECTORY_SEPARATOR;
            }
        }
 
        return $path;
    }

    function execute () {
        if (method_exists($this, 'action'.$this->action)) {
            $this->{'action'.$this->action}();
        } else {
            trigger_error('This action is not found', E_USER_WARNING);
        }
        $this->result->error = 0;
        $this->result->path = str_replace($this->root, '', $this->getPath());
        $this->display();
    }

    function actionItems() {
        $path = $this->getPath();
        $dir = opendir($path);
        $this->result->baseurl = $this->config->baseurl;
        $this->result->path = str_replace(realpath($this->root) . DIRECTORY_SEPARATOR, '', $this->getPath());
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_file($path.$file)) {
                $info = pathinfo($path.$file);
                if (!isset($info['extension']) or (!isset($this->config->extensions) or in_array(strtolower($info['extension']), $this->config->extensions))) {
                    $item = array(
                        'file' => $file,
                    );
                    if ($this->config->createThumb) {
                        if (!is_dir($path.$this->config->thumbFolderName)) {
                            mkdir($path.$this->config->thumbFolderName, 0777);
                        }
                        if (!file_exists($path.$this->config->thumbFolderName.'/'.$file)) {
                            $img = new abeautifulsite\SimpleImage($path.$file);
                            $img
                                ->best_fit(150, 150)
                                ->save($path.$this->config->thumbFolderName.'/'.$file, $this->config->quality);
                        }
                        $item['thumb'] = $this->config->thumbFolderName.'/'.$file;
                        $item['changed'] = date($this->config->datetimeFormat, filemtime($path.$file));
                        $item['size'] = $this->humanFilesize(filesize($path.$file));
                    }
                    $this->result->files[] = $item;
                }
            }
        }
    }
    function actionFolder() {
        $path = $this->getPath();

        $this->result->files[] = $path == $this->root ? '.' : '..';

        $dir = opendir($path);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && is_dir($path.$file) and (!$this->config->createThumb || $file !== $this->config->thumbFolderName) and !in_array($file, $this->config->excludeDirectoryNames)) {
                $this->result->files[] = $file;
            }
        }
    }

    function actionUpload() {
        $path = $this->getPath();
        $errors = array(
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        );
        
        if (isset($_FILES['files']) and is_array($_FILES['files']) and isset($_FILES['files']['name']) and is_array($_FILES['files']['name']) and count($_FILES['files']['name'])) {
            foreach ($_FILES['files']['name'] as $i=>$file) {
                if ($_FILES['files']['error'][$i]) {
                    trigger_error(isset($errors[$_FILES['files']['error'][$i]]) ? $errors[$_FILES['files']['error'][$i]] : 'Error', E_USER_WARNING);
                }
                $tmp_name = $_FILES['files']['tmp_name'][$i];
                if (move_uploaded_file($tmp_name, $file = $path.$this->makeSafe($_FILES['files']['name'][$i]))) {
                    $info = pathinfo($file);

                    if (!isset($info['extension']) or (isset($this->config->extensions) and !in_array(strtolower($info['extension']), $this->config->extensions))) {
                        unlink($file);
                        trigger_error('File type not in white list', E_USER_WARNING);
                    }

                    $this->result->msg[] = 'File '.$_FILES['files']['name'][$i].' was upload';
                    $this->result->files[] = str_replace($this->root, '', $file);
                } else {
                    if (!is_writable($path)) {
                        trigger_error('Destination directory is not writeble', E_USER_WARNING);
                    }

                    trigger_error('No files have been uploaded', E_USER_WARNING);
                }
            }
            $this->result->baseurl = $this->config->baseurl;
        }
 
        if (!count($this->result->files)) {
            trigger_error('No files have been uploaded', E_USER_WARNING);
        }
    }
    function actionRemove() {
        $filepath = false;

        $path = $this->getPath();
        $target = isset($_REQUEST['target']) ?  $_REQUEST['target'] : '';

        if (realpath($path.$target) && strpos(realpath($path.$target), $this->root) !== false) {
            $filepath = realpath($path.$target);
        }

        if ($filepath) {
            $result = false;
            if (is_file($filepath)) {
                $result = unlink($filepath);
                if ($result) {
                    $file = basename($filepath);
                    $thumb = dirname($filepath) . DIRECTORY_SEPARATOR . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($thumb)) {
                        unlink($thumb);
                        if (!count(glob(dirname($thumb) . DIRECTORY_SEPARATOR . "*"))) {
                            rmdir(dirname($thumb));
                        }
                    }
                }
            } else {
                $thumb = $filepath . DIRECTORY_SEPARATOR . $this->config->thumbFolderName . DIRECTORY_SEPARATOR;
                if (is_dir($thumb)) {
                    if (!count(glob($thumb . "*"))) {
                        rmdir($thumb);
                    }
                }
                $result = rmdir($filepath);
            }
            if (!$result) {
                $error = (object)error_get_last();
                trigger_error('Delete failed! '.$error->message, E_USER_WARNING);
            }
        } else {
            trigger_error('The destination path has not been set', E_USER_WARNING);
        }
    }
    function actionCreate() {
        $dstpath = $this->getPath();
        $foldername = $this->makeSafe(isset($this->request->name) ?  $this->request->name : '');
        if ($dstpath) {
            if ($foldername) {
                if (!realpath($dstpath.$foldername)) {
                    mkdir($dstpath.$foldername, 0777);
                    if (is_dir($dstpath.$foldername)) {
                        $this->result->msg = 'Directory was created';
                    } else {
                        trigger_error('Directory was not created', E_USER_WARNING);
                    }
                } else {
                    trigger_error('Folder already exists', E_USER_WARNING);
                }
            } else {
                trigger_error('The name for the new folder has not been set', E_USER_WARNING);
            }
        } else {
            trigger_error('The destination folder has not been set', E_USER_WARNING);
        }
    }
    function actionMove() {
        $dstpath = $this->getPath();
        $srcpath = $this->getPath('filepath');

        if ($srcpath) {
            if ($dstpath) {
                if (is_file($srcpath) or is_dir($srcpath)) {
                    rename($srcpath, $dstpath.basename($srcpath));
                } else {
                    trigger_error('Not file', E_USER_WARNING);
                }
            } else {
                trigger_error('Need destination path', E_USER_WARNING);
            }
        } else {
            trigger_error('Need source path', E_USER_WARNING);
        }
    }
    function actionResize() {
        
        $info = $this->getImageEditorInfo();

        if ((int)$info->box->w <= 0) {
            trigger_error('Width not specified', E_USER_WARNING);
        }

        if ((int)$info->box->h <= 0) {
            trigger_error('Height not specified', E_USER_WARNING);
        }
        
        try {
            $info->img
                ->resize((int)$info->box->w, (int)$info->box->h)
                ->save($info->path.$info->newname, $this->config->quality);
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }
    function actionCrop() {
        $info = $this->getImageEditorInfo();

        if ((int)$info->box->x < 0 || (int)$info->box->x > (int)$info->width) {
            trigger_error('Start X not specified', E_USER_WARNING);
        }

        if ((int)$info->box->y < 0 || (int)$info->box->y > (int)$info->height) {
            trigger_error('Start Y not specified', E_USER_WARNING);
        }

        if ((int)$info->box->w <= 0) {
            trigger_error('Width not specified', E_USER_WARNING);
        }

        if ((int)$info->box->h <= 0) {
            trigger_error('Height not specified', E_USER_WARNING);
        }

        try {
            $info->img
                ->crop((int)$info->box->x, (int)$info->box->y, (int)$info->box->x + (int)$info->box->w, (int)$info->box->y + (int)$info->box->h)
                ->save($info->path.$info->newname, $this->config->quality);
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_WARNING);
        }
    }
}

$config = array(
    'datetimeFormat' => 'm/d/Y H:i A',
    'quality' => 90,
    'root' => realpath(realpath(dirname(__FILE__). DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR,
    'baseurl' => 'files/',
    'createThumb' => true,
    'thumbFolderName' => '_thumbs',
    'excludeDirectoryNames' => array('.tmb', '.quarantine'),
    'extensions' => array('jpg', 'png', 'gif', 'jpeg'),
    'debug' => true,
);

if (file_exists("config.jodit.php")) {
    include "config.jodit.php";
} else if (file_exists("../config.jodit.php")) {
    include "../config.jodit.php";
} else if (file_exists("config.php")) {
    include "config.php";
}

$filebrowser = new JoditFileBrowser($_REQUEST, $config);

$filebrowser->checkPermissions();

$filebrowser->execute();