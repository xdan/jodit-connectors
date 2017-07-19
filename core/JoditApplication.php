<?php
namespace jodit;

use \abeautifulsite\SimpleImage;
use \ErrorException;

/**
 * Class Request
 * @package jodit
 * @property string $action
 * @property string $source
 * @property string $name
 * @property string $newname
 * @property string $path
 * @property string $url
 * @property array $box
 */
class Request {
    function get($key, $default_value = null) {
        return !empty($_REQUEST[$key]) ? $_REQUEST[$key] : $default_value;
    }

    function __get($key) {
        return $this->get($key);
    }

    function post($keys, $default_value = null) {
        $keys_chain = explode('/', $keys);
        $result = $_POST;

        foreach ($keys_chain as $key) {
            if ($key and isset($result[$key])) {
                $result = $result[$key];
            } else {
                $result = $default_value;
                break;
            }
        }

        return $result;
    }
}

/**
 * Class Response
 * @package jodit
 */
class Response {
    public $success = true;
    public $time;

    public $data = [
        'messages' => [],
        'code' => 220,
    ];

    function __construct() {
        $this->time = date('Y-m-d H:i:s');
        $this->data = (object)$this->data;
    }
}

/**
 * Class Source
 * @package jodit
 * @property string $baseurl
 * @property number $maxFileSize
 * @property number $quality
 * @property string $thumbFolderName
 * @property string $defaultPermission
 */
class Source {
    private $data = [];
    private $defaultOptuions = [];
    function __get($key) {
        if (!empty($this->data->{$key})) {
            return $this->data->{$key};
        }
        if ($this->defaultOptuions->{$key}) {
            return $this->defaultOptuions->{$key};
        }

        throw new ErrorException('Option ' . $key . ' not set', 501);
    }
    function __construct($data, $defaultOptuions) {
        $this->data = (object)$data;
        $this->defaultOptuions = (object)$defaultOptuions;
    }
}


abstract class JoditApplication {

    /**
     * Check whether the user has the ability to view files
     * You can define JoditCheckPermissions function in config.php and use it
     */
    abstract public function checkPermissions ();

    function corsHeaders() {
        if (isset($_SERVER['HTTP_ORIGIN'])) {
            header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        } else {
            header("Access-Control-Allow-Origin: *");
        }

        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Allow-Headers: Origin,X-Requested-With,Content-Type,Accept');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day

        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
            }

            exit(0);
        }

    }

    function display () {
        if (!JODIT_DEBUG) {
            ob_end_clean();
            header('Content-Type: application/json');
        }

        // replace full path from message
        foreach ($this->config->sources as $source) {
            if (isset($this->response->data->messages)) {
                foreach ($this->response->data->messages as &$message) {
                    $message = str_replace($source['root'], '/', $message);
                }
            }
        }

        exit(json_encode($this->response, JODIT_DEBUG ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES: 0));
    }
    function execute () {
        if (method_exists($this, 'action' . $this->action)) {
            $this->response->data =  (object)call_user_func_array([$this, 'action' . $this->action], []);
        } else {
            throw new ErrorException('This action is not found', 404);
        }

        $this->response->success = true;
        $this->response->data->code = 220;
        $this->display();
    }

    /**
     * Constructor FileBrowser
     *
     * @param {array} $config
     * @throws ErrorException
     */
    function __construct ($config) {
        ob_start();
        set_error_handler([$this, 'errorHandler'], E_ALL);
        set_exception_handler([$this, 'exceptionHandler']);

        $this->config  = (object)$config;

        if ($this->config->allowCrossOrigin) {
            $this->corsHeaders();
        }

        $this->response  = new Response();
        $this->request  = new Request();

        $this->action  = $this->request->action;

        if (JODIT_DEBUG) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', 'on');
        } else {
            error_reporting(0);
            ini_set('display_errors', 'off');
        }

        if ($this->request->source && $this->request->source !== 'default' && empty($this->config->sources[$this->request->source])) {
            throw new ErrorException('Need valid parameter source key', 400);
        }
    }

    /**
     * Get default(first) source or by $_REQUEST['source']
     *
     * @return Source
     */
    public function getSource() {
        if (!$this->request->source || empty($this->config->sources[$this->request->source])) {
            return new Source(array_values($this->config->sources)[0], $this->config);
        }
        return new Source($this->config->sources[$this->request->source], $this->config);
    }

    /**
     * @property Response $response
     */
    public $response;

    /**
     * @property Request $request
     */
    public $request;


    /**
     * @property string $action
     */
    private $action;

    /**
     * Check file extension
     *
     * @param {string} $file
     * @param {Source} $source
     * @return bool
     */
    private function isGoodFile($file, $source) {
        $info = pathinfo($file);
        if (!isset($info['extension']) or (!in_array(strtolower($info['extension']), $source->extensions))) {
            return false;
        }
        return true;
    }
    /**
     * Convert number bytes to human format
     *
     * @param $bytes
     * @param int $decimals
     * @return string
     */
    protected function humanFileSize($bytes, $decimals = 2) {
        $size = ['B','kB','MB','GB','TB','PB','EB','ZB','YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . $size[(int)$factor];
    }

    /**
     * Converts from human readable file size (kb,mb,gb,tb) to bytes
     *
     * @param {string|int} human readable file size. Example 1gb or 11.2mb
     * @return int
     */
    protected function convertToBytes($from) {
        if (is_numeric($from)) {
            return (int)$from;
        }

        $number = substr($from, 0, -2);
        $formats = ["KB", "MB", "GB", "TB"];
        $format = strtoupper(substr($from, -2));

        return in_array($format, $formats) ? (int)($number * pow(1024, array_search($format, $formats) + 1)) : (int)$from;
    }

    protected function getImageEditorInfo() {
        $source = $this->getSource();
        $path = $this->getPath($source);

        $file = $this->request->name;

        $box = (object)[
            'w' => 0,
            'h' => 0,
            'x' => 0,
            'y' => 0,
        ];

        if ($this->request->box && is_array($this->request->box)) {
            foreach ($box as $key=>&$value) {
                $value = isset($this->request->box[$key]) ? $this->request->box[$key] : 0;
            }
        }

        $newName = $this->request->newname ?  $this->makeSafe($this->request->newname) : '';
        
        if (!$path || !$file || !file_exists($path . $file) || !is_file($path . $file)) {
            throw new ErrorException('Source file not set or not exists', 404);
        }

//        if (!$newName) {
//            throw new ErrorException('Set new name for file', 400);
//        }

        $img = new SimpleImage();


        $img->load($path . $file);


        if ($newName) {
            $info = pathinfo($path . $file);
            $newName = $newName . '.' . $info['extension'];
            if (file_exists($path . $newName)) {
                throw new ErrorException('File ' . $newName . ' already exists', 400);
            }
        } else {
            $newName = $file;
        }
        
        if (file_exists($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $newName)) {
            unlink($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $newName);
        }
        
        $info = $img->get_original_info();
        
        return (object)[
            'path' => $path,
            'file' => $file,
            'box' => $box,
            'newname' => $newName,
            'img' => $img,
            'width' => $info['width'],
            'height' => $info['height'],
        ];
    }

    protected function translit ($str) {
        $str = (string)$str;

        $replace = [
            'а'=>'a','б'=>'b','в'=>'v','г'=>'g','д'=>'d','е'=>'e','ё'=>'yo','ж'=>'zh','з'=>'z','и'=>'i','й'=>'y',
            'к'=>'k','л'=>'l','м'=>'m','н'=>'n','о'=>'o','п'=>'p','р'=>'r','с'=>'s','т'=>'t','у'=>'u','ф'=>'f',
            'х'=>'h','ц'=>'ts','ч'=>'ch','ш'=>'sh','щ'=>'shch','ъ'=>'','ы'=>'i','ь'=>'','э'=>'e','ю'=>'yu','я'=>'ya',
            ' '=>'-',
            'А'=>'A','Б'=>'B','В'=>'V','Г'=>'G','Д'=>'D','Е'=>'E','Ё'=>'Yo','Ж'=>'Zh','З'=>'Z','И'=>'I','Й'=>'Y',
            'К'=>'K','Л'=>'L','М'=>'M','Н'=>'N','О'=>'O','П'=>'P','Р'=>'R','С'=>'S','Т'=>'T','У'=>'U','Ф'=>'F',
            'Х'=>'H','Ц'=>'Ts','Ч'=>'CH','Ш'=>'Sh','Щ'=>'Shch','Ъ'=>'','Ы'=>'I','Ь'=>'','Э'=>'E','Ю'=>'Yu','Я'=>'Ya',
        ];

        $str = strtr($str, $replace);

        return $str;
    }

    protected function makeSafe($file) {
        $file = rtrim($this->translit($file), '.');
        $regex = ['#(\.){2,}#', '#[^A-Za-z0-9\.\_\- ]#', '#^\.#'];
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
    public function errorHandler ($errorNumber, $errorMessage, $file, $line) {
        $this->response->success = false;
        $this->response->data->code = $errorNumber;
        $this->response->data->messages[] = $errorMessage . (JODIT_DEBUG ? ' - file:' . $file . ' line:' . $line : '');

        $this->display();
    }

    /**
     * @param ErrorException $exception
     */
    public function exceptionHandler ($exception) {
        $this->errorHandler($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }

    /**
     * Get full path for $source->root with trailing slash
     *
     * @param $source
     * @return string
     * @throws ErrorException
     */
    protected function getRoot($source) {
        if ($source->root) {
            if (!is_dir($source->root)) {
                throw new ErrorException('Root directory not exists ' . $source->root, 501);
            }
            return realpath($source->root) . DIRECTORY_SEPARATOR;
        }
        throw new ErrorException('Set root directory for source', 501);
    }

    /**
     * Get full path for $_REQUEST[$name] relative path with trailing slash(if directory)
     *
     * @param $source
     * @param string $name
     * @return bool|string
     * @throws ErrorException
     */
    protected function getPath ($source, $name = 'path') {
        $root = $this->getRoot($source);

        if (!$this->request->source) {
            return $root;
        }

        $relativePath = $this->request->{$name} ?: '';

        //always check whether we are below the root category is not reached
        if (realpath($root . $relativePath) && strpos(realpath($root . $relativePath) . DIRECTORY_SEPARATOR, $root) !== false) {
            $root = realpath($root . $relativePath);
            if (is_dir($root)) {
                $root .= DIRECTORY_SEPARATOR;
            }
        } else {
            throw new ErrorException('Path does not exist', 404);
        }
 
        return $root;
    }


    /**
     * Check by mimetype what file is image
     *
     * @param string $path
     *
     * @return bool
     */
    protected function isImage($path) {
        if (!function_exists('exif_imagetype')) {
            function exif_imagetype($filename) {
                if (( list(, , $type) = getimagesize($filename)) !== false) {
                    return $type;
                }
                return false;
            }
        }
        return in_array(exif_imagetype($path) , [IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP]);
    }

    /**
     * Download remote file on server
     *
     * @param $url
     * @param $destinationFilename
     * @throws ErrorException
     */
    protected function downloadRemoteFile($url, $destinationFilename) {
        if (!ini_get('allow_url_fopen')) {
            throw new ErrorException('allow_url_fopen is disable', 501);
        }
        
        if (!function_exists('curl_init')) {
            $raw = file_get_contents($url);
        } else {

            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4

            $response = parse_url($url);
            curl_setopt($ch, CURLOPT_REFERER, $response['scheme'] . '://' . $response['host']);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0');

            $raw = curl_exec($ch);

            curl_close($ch);
        }

        file_put_contents($destinationFilename, $raw);

        if (!$this->isImage($destinationFilename)) {
            unlink($destinationFilename);
            throw new ErrorException('Bad image ' . $destinationFilename, 406);
        }
    }

    /**
     * Load all files from folder ore source or sources
     */
    protected function actionFiles() {
        $sources = [];
        foreach ($this->config->sources as $key => $source) {
            if ($this->request->source && $this->request->source !== 'default' && $key !== $this->request->source && $this->request->path !== './') {
                continue;
            }

            $source = new Source($source, $this->config);

            $path = $this->getPath($source);

            $sourceData = (object)[
                'baseurl' => $source->baseurl,
                'path' =>  str_replace(realpath($this->getRoot($source)) . DIRECTORY_SEPARATOR, '', $path),
                'files' => [],
            ];

            $dir = opendir($path);

            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..' && is_file($path . $file)) {
                    if ($this->isGoodFile($path . $file, $source)) {
                        $item = [
                            'file' => $file,
                        ];

                        if ($this->config->createThumb) {
                            if (!is_dir($path . $this->config->thumbFolderName)) {
                                mkdir($path . $this->config->thumbFolderName, 0777);
                            }
                            if (!file_exists($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file)) {
                                $img = new SimpleImage($path . $file);
                                $img
                                    ->best_fit(150, 150)
                                    ->save($path.$this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file, $this->config->quality);
                            }
                            $item['thumb'] = $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file;
                        }

                        $item['changed'] = date($this->config->datetimeFormat, filemtime($path.$file));
                        $item['size'] = $this->humanFileSize(filesize($path.$file));
                        $sourceData->files[] = $item;
                    }
                }
            }

            $sources[$key] = $sourceData;
        }

        return [
            'sources' => $sources
        ];
    }

    /**
     * Load all folders from folder ore source or sources
     */
    protected function actionFolders() {
        $sources = [];
        foreach ($this->config->sources as $key => $source) {
            if ($this->request->source && $this->request->source !== 'default' && $key !== $this->request->source && $this->request->path !== './') {
                continue;
            }

            $source = new Source($source, $this->config);

            $path = $this->getPath($source);

            $sourceData = (object)[
                'baseurl' => $source->baseurl,
                'path' =>  str_replace(realpath($this->getRoot($source)) . DIRECTORY_SEPARATOR, '', $path),
                'folders' => [],
            ];

            $sourceData->folders[] = $path == $this->getRoot($source) ? '.' : '..';

            $dir = opendir($path);
            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..' && is_dir($path . $file) and (!$this->config->createThumb || $file !== $this->config->thumbFolderName) and !in_array($file, $this->config->excludeDirectoryNames)) {
                    $sourceData->folders[] = $file;
                }
            }

            $sources[$key] = $sourceData;
        }

        return [
            'sources' => $sources
        ];
    }

    /**
     * Load remote image by URL to self host
     * @throws ErrorException
     */
    protected function actionUploadRemote() {
        $url = $this->request->url;

        if (!$url) {
            throw new ErrorException('Need url parameter', 400);
        }

        $result = parse_url($url);

        if (!isset($result['host']) || !isset($result['path'])) {
            throw new ErrorException('Not valid URL', 400);
        }
        
        $filename = $this->makeSafe(basename($result['path']));
        
        if (!$filename) {
            throw new ErrorException('Not valid URL', 400);
        }

        $this->downloadRemoteFile($url, $this->getRoot($this->getSource()) . $filename);

        return [
            'newfilename' => $filename,
            'baseurl' => $this->getSource()->baseurl,
        ];
    }


    static private $upload_errors = [
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    ];

    /**
     * Upload images
     *
     * @return array
     * @throws ErrorException
     */
    protected function actionUpload() {

        $source = $this->getSource();

        $root = $this->getRoot($source);
        $path = $this->getPath($source);

        $messages = [];
        $files = [];

        if (isset($_FILES['files']) and is_array($_FILES['files']) and isset($_FILES['files']['name']) and is_array($_FILES['files']['name']) and count($_FILES['files']['name'])) {
            foreach ($_FILES['files']['name'] as $i => $file) {
                if ($_FILES['files']['error'][$i]) {
                    throw new ErrorException(isset(self::$upload_errors[$_FILES['files']['error'][$i]]) ? self::$upload_errors[$_FILES['files']['error'][$i]] : 'Error', $_FILES['files']['error'][$i]);
                }

                $tmp_name = $_FILES['files']['tmp_name'][$i];

                if ($source->maxFileSize and filesize($tmp_name) > $this->convertToBytes($source->maxFileSize)) {
                    unlink($tmp_name);
                    throw new ErrorException('File size exceeds the allowable', 403);
                }

                if (move_uploaded_file($tmp_name, $file = $path . $this->makeSafe($_FILES['files']['name'][$i]))) {
                    if (!$this->isGoodFile($file, $source)) {
                        unlink($file);
                        throw new ErrorException('File type is not in white list', 403);
                    }

                    $messages[] = 'File ' . $_FILES['files']['name'][$i] . ' was upload';
                    $files[] = str_replace($root, '', $file);
                } else {
                    if (!is_writable($path)) {
                        throw new ErrorException('Destination directory is not writeble', 424);
                    }

                    throw new ErrorException('No files have been uploaded', 422);
                }
            }
        }
 
        if (!count($files)) {
            throw new ErrorException('No files have been uploaded', 422);
        }

        return [
            'baseurl' => $source->baseurl,
            'messages' => $messages,
            'files' => $files
        ];
    }

    /**
     * Remove file or directory
     *
     * @throws ErrorException
     */
    protected function actionRemove() {
        $source = $this->getSource();

        $file_path = false;

        $path = $this->getPath($source);

        $target = $this->request->name;

        if (realpath($path . $target) && strpos(realpath($path . $target), $this->getRoot($source)) !== false) {
            $file_path = realpath($path . $target);
        }

        if ($file_path && file_exists($file_path)) {
            if (is_file($file_path)) {
                $result = unlink($file_path);
                if ($result) {
                    $file = basename($file_path);
                    $thumb = dirname($file_path) . DIRECTORY_SEPARATOR . $source->thumbFolderName . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($thumb)) {
                        unlink($thumb);
                        if (!count(glob(dirname($thumb) . DIRECTORY_SEPARATOR . "*"))) {
                            rmdir(dirname($thumb));
                        }
                    }
                }
            } else {
                $thumb = $file_path . DIRECTORY_SEPARATOR . $source->thumbFolderName . DIRECTORY_SEPARATOR;
                if (is_dir($thumb)) {
                    if (!count(glob($thumb . "*"))) {
                        rmdir($thumb);
                    }
                }
                $result = rmdir($file_path);
            }

            if (!$result) {
                $error = (object)error_get_last();
                throw new ErrorException('Delete failed! ' . $error->message, 424);
            }
        } else {
            throw new ErrorException('File or directory not exists' . $path . $target, 400);
        }
    }

    /**
     * Create directory
     * @throws ErrorException
     */
    protected function actionCreate() {
        $source = $this->getSource();
        $destinationPath = $this->getPath($source);
        $folderName = $this->makeSafe($this->request->name);

        if ($destinationPath) {
            if ($folderName) {
                if (!realpath($destinationPath . $folderName)) {
                    mkdir($destinationPath . $folderName, $source->defaultPermission);
                    if (is_dir($destinationPath . $folderName)) {
                        return ['messages' => ['Directory successfully created']];
                    }
                    throw new ErrorException('Directory was not created', 404);
                }
                throw new ErrorException('Directory already exists', 406);
            }
            throw new ErrorException('The name for new directory has not been set', 406);
        }
        throw new ErrorException('The destination directory has not been set', 406);
    }

    /**
     * Move file or directory to another folder
     *
     * @throws ErrorException
     */
    protected function actionMove() {
        $source = $this->getSource();
        $destination_path = $this->getPath($source);
        $source_path = $this->getPath($source, 'from');

        if ($source_path) {
            if ($destination_path) {
                if (is_file($source_path) or is_dir($source_path)) {
                    rename($source_path, $destination_path . basename($source_path));
                } else {
                    throw new ErrorException('Not file', 404);
                }
            } else {
                throw new ErrorException('Need destination path', 400);
            }
        } else {
            throw new ErrorException('Need source path', 400);
        }
    }

    /**
     * Resize image
     *
     * @throws ErrorException
     */
    protected function actionResize() {
        $source = $this->getSource();
        $info = $this->getImageEditorInfo();

        if (!$info->box || (int)$info->box->w <= 0) {
            throw new ErrorException('Width not specified', 400);
        }

        if (!$info->box || (int)$info->box->h <= 0) {
            throw new ErrorException('Height not specified', 400);
        }
        

        $info->img
            ->resize((int)$info->box->w, (int)$info->box->h)
            ->save($info->path . $info->newname, $source->quality);
    }

    protected function actionCrop() {
        $source = $this->getSource();
        $info = $this->getImageEditorInfo();

        if ((int)$info->box->x < 0 || (int)$info->box->x > (int)$info->width) {
            throw new ErrorException('Start X not specified', 400);
        }

        if ((int)$info->box->y < 0 || (int)$info->box->y > (int)$info->height) {
            throw new ErrorException('Start Y not specified', 400);
        }

        if ((int)$info->box->w <= 0) {
            throw new ErrorException('Width not specified', 400);
        }

        if ((int)$info->box->h <= 0) {
            throw new ErrorException('Height not specified', 400);
        }

        $info->img
            ->crop((int)$info->box->x, (int)$info->box->y, (int)$info->box->x + (int)$info->box->w, (int)$info->box->y + (int)$info->box->h)
            ->save($info->path . $info->newname, $source->quality);

    }

    /**
     * Get filepath by URL for local files
     *
     * @metod actionGetFileByURL
     */
    function actionGetLocalFileByURL() {
        $url = $this->request->url;
        if (!$url) {
            throw new ErrorException('Need full url', 400);
        }

        $parts = parse_url($url);

        if (empty($parts['path'])) {
            throw new ErrorException('Empty url', 400);
        }

        $found = false;
        $path = '';
        $root = '';

        foreach ($this->config->sources as $key => $source) {
            if ($this->request->source && $this->request->source !== 'default' && $key !== $this->request->source && $this->request->path !== './') {
                continue;
            }

            $source = new Source($source, $this->config);
            $base = parse_url($source->baseurl);

            $path = preg_replace('#^(/)?' . $base['path'] . '#', '', $parts['path']);


            $root = $this->getPath($source);

            if (file_exists($root . $path) && is_file($root . $path) && $this->isGoodFile($root . $path, $source)) {
                $found = true;
                break;
            }
        }

        if (!$found) {
            throw new ErrorException('File does not exist or is above the root of the connector', 424);
        }

        return [
            'path' => str_replace($root, '', dirname($root . $path) . DIRECTORY_SEPARATOR),
            'name' => basename($path),
            'source' => $key
        ];
    }
}
