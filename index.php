<?php
class FileBrowser {
    public $result;
    public $root;
    public $action;
    public $request = array();
    
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

        set_error_handler(array($this, 'errorHandler'), E_ALL);

        $this->request = (object)$request;
        $this->config  = (object)$config;
        $this->result  = (object)array('error'=> 1, 'msg' => array(), 'files'=> array());
        $this->action  = isset($this->request->action) ?  $this->request->action : 'items';
        $this->root  = isset($this->config->root) ?  $this->config->root : dirname(__FILE__);
        
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
        $path = $this->root;
        //always check whether we are below the root category is not reached
        if (realpath($this->root.$relpath) && strpos(realpath($this->root.$relpath), $this->root) !== false) {
            $path = realpath($this->root.$relpath).DIRECTORY_SEPARATOR;
        }
        return $path;
    }

    function execute () {
        if (method_exists($this, 'action'.$this->action)) {
            $this->{'action'.$this->action}();
        }
        $this->result->error = 0;
        $this->result->path = str_replace($this->root, '', $this->getPath());
        $this->display();
    }
    
    function each($path, $condition) {
        $path = $this->getPath();
        $dir = opendir($path);
        while ($file = readdir($dir)) {
            if ($file != '.' && $file != '..' && call_user_func($condition, $path.$file)) {
                $this->result->files[] = $file;
            }
        }
    }


    function actionItems() {
        $this->each($this->getPath(), function ($filepath) {
            if (is_file($filepath)) {
                $info = pathinfo($filepath);
                if (!isset($this->config->extensions) or in_array(strtolower($info['extension']), $this->config->extensions)) {
                    return true;
                }
            }
            return false;
        });
        $this->result->baseurl = $this->config->baseurl;
    }
    function actionFolder() {
        $path = $this->getPath();

        $this->result->files[] = $path == $this->root ? '.' : '..';

        $this->each($path, function ($filepath) {
            if (is_dir($filepath)) {
                return true;
            }
        });
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
                    
                    if (isset($this->config->extensions) and !in_array(strtolower($info['extension']), $this->config->extensions)) {
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
            if (is_file($filepath)) {
                unlink($filepath);
            } else {
                rmdir($filepath);
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
        $srcpath = $this->getPath('file');

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
}

$config = array(
    'root' => realpath(realpath(dirname(__FILE__).DIRECTORY_SEPARATOR.'..').DIRECTORY_SEPARATOR.'files'.DIRECTORY_SEPARATOR). DIRECTORY_SEPARATOR,
    'baseurl' => 'files/',
    'extensions' => array('jpg', 'png', 'gif', 'jpeg'),
    'debug' => false,
);

if (file_exists("config.php")) {
    include "config.php";
}

$filebrowser = new FileBrowser($_REQUEST, $config);

$filebrowser->checkPermissions();

$filebrowser->execute();