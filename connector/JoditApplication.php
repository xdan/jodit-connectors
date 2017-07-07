<?php
namespace jodit;
use PHPUnit\Exception;

/**
 * Class Request
 * @package jodit
 * @property string $action
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
class Response {
    public $success = true;
    public $time;

    public $data = [
        'sources' => [],
        'messages' => [],
        'code' => 220,
    ];

    function __construct() {
        $this->time = date('Y-m-d H:i:s');
        $this->data = (object)$this->data;
    }

    function display() {
        if (!JODIT_DEBUG) {
            ob_end_clean();
            header('Content-Type: application/json');
        }

//        if ($this->data->messages) {
//            foreach ($this->data->messages as &$message) {
//                $message = str_replace($path, '/', $message);
//            }
//        }

        exit(json_encode($this, JODIT_DEBUG ? JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES: 0));
    }
}


abstract class JoditApplication {

    /**
     * Check whether the user has the ability to view files
     * You can define JoditCheckPermissions function in config.php and use it
     */
    abstract public function checkPermissions ();

    function execute () {
        if (method_exists($this, 'action' . $this->action)) {
            call_user_func_array([$this, 'action' . $this->action], []);
        } else {
            throw new \ErrorException('This action is not found', 404);
        }

        $this->response->success = true;
        $this->response->display();
    }

    /**
     * Constructor FileBrowser
     */
    function __construct ($config) {
        ob_start();
        set_error_handler([$this, 'errorHandler'], E_ALL);
        set_exception_handler([$this, 'exceptionHandler']);

        $this->config  = (object)$config;

        $this->response  = new Response();
        $this->request  = new Request();

        $this->action  = $this->request->action ?  $this->request->action : 'files';

        if (JODIT_DEBUG) {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
            ini_set('display_errors', 'on');
        } else {
            error_reporting(0);
            ini_set('display_errors', 'off');
        }

        if (!in_array($this->action, ['files', 'folders']) and  (!$this->request->source || empty($this->config->sources[$this->request->source]))) {
            throw new \ErrorException('Need parameter source key', 400);
        }
        if ($this->request->source && empty($this->config->sources[$this->request->source])) {
            throw new \ErrorException('Need valid parameter source key', 400);
        }
    }

    /**
     * Get default(first) source or by $_REQUEST['source']
     *
     * @return array
     */
    public function getSource() {
        if (!$this->request->source) {
            return array_values($this->config->sources)[0];
        }
        return (object)$this->config->sources[$this->request->source];
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
     * Convert number bytes to human format
     *
     * @param $bytes
     * @param int $decimals
     * @return string
     */
    protected function humanFileSize($bytes, $decimals = 2) {
        $size = ['B','kB','MB','GB','TB','PB','EB','ZB','YB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$size[$factor];
    }

    /**
     * Converts from human readable file size (kb,mb,gb,tb) to bytes
     *
     * @param {string|int} human readable file size. Example 1gb or 11.2mb
     * @return {int}
     */
    protected function convertToBytes($from) {
        if (is_numeric($from)) {
            return $from;
        }

        $number = substr($from, 0, -2);
        $formats = ["KB", "MB", "GB", "TB"];
        $format = strtoupper(substr($from, -2));

        return in_array($format, $formats) ? $number * pow(1024, array_search($format, $formats) + 1) : (int)$from;
    }

    protected function getImageEditorInfo() {
        $path = $this->getPath();

        $file = isset($this->request->file) ?  $this->request->file : '';;
        $box = isset($this->request->box) ?  (object)$this->request->box : '';
        $newName = !empty($this->request->newname) ?  $this->makeSafe($this->request->newname) : '';
        
        if (!$path || !file_exists($path . $file)) {
            throw new \ErrorException('Image file is not specified', 400);
        }

        $img = new \abeautifulsite\SimpleImage();


        $img->load($path . $file);


        if ($newName) {
            $info = pathinfo($path . $file);
            $newName = $newName . '.' . $info['extension'];
            if (file_exists($path . $newName)) {
                throw new \ErrorException('File ' . $newName . ' already exists', 400);
            }
        } else {
            $newName = $file;
        }
        
        if (file_exists($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $newName)) {
            unlink($path.$this->config->thumbFolderName . DIRECTORY_SEPARATOR . $newName);
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

        $this->response->display();
    }
    public function exceptionHandler ($exception) {
        $this->errorHandler($exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }

    /**
     * Get full path for $source->root with trailing slash
     *
     * @param $source
     * @return string
     * @throws \ErrorException
     */
    protected function getRoot($source) {
        if ($source->root) {
            if (!is_dir($source->root)) {
                throw new \ErrorException('Root directory not exists ' . $source->root, 501);
            }
            return realpath($source->root) . DIRECTORY_SEPARATOR;
        }
        throw new \ErrorException('Set root directory for source', 501);
    }

    /**
     * Get full path for $_REQUEST[$name] relative path with trailing slash(if directory)
     *
     * @param $source
     * @param string $name
     * @return bool|string
     * @throws \ErrorException
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
            throw new \ErrorException('Path does not exist', 404);
        }
 
        return $root;
    }


    /**
     * Check by mimetype what file is image
     *
     * @param string $path
     * @return {boolean}
     */
    protected function isImage($path) {
        if (!function_exists('exif_imagetype')) {
            function exif_imagetype($filename) {
                if (( list($width, $height, $type, $attr) = getimagesize($filename)) !== false) {
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
     * @throws \ErrorException
     */
    protected function downloadRemoteFile($url, $destinationFilename) {
        if (!ini_get('allow_url_fopen')) {
            throw new \ErrorException('allow_url_fopen is disable', 501);
        }
        
        if (!function_exists('curl_init')) {
            file_put_contents($destinationFilename, file_get_contents($url));
            return;
        }

        $ch = curl_init ($url);
 
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);// таймаут4

        $response = parse_url($url);
        curl_setopt($ch, CURLOPT_REFERER, $response['scheme'] . '://' . $response['host']);
        curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:45.0) Gecko/20100101 Firefox/45.0');
        
        $raw = curl_exec($ch);

        curl_close ($ch);

        file_put_contents($destinationFilename, $raw);
    
        if (!$this->isImage($destinationFilename)) {
            unlink($destinationFilename);
            throw new \ErrorException('Bad image ' . $destinationFilename, 406);
        }
    }

    /**
     * Load all files from folder ore source or sources
     */
    protected function actionFiles() {
        foreach ($this->config->sources as $key => $source) {
            if ($this->request->source && $key !== $this->request->source) {
                continue;
            }

            $source = (object)$source;

            $path = $this->getPath($source);

            $sourceData = (object)[
                'baseurl' => $source->baseurl,
                'path' =>  str_replace(realpath($this->getRoot($source)) . DIRECTORY_SEPARATOR, '', $path),
                'files' => [],
            ];

            $dir = opendir($path);

            while ($file = readdir($dir)) {
                if ($file != '.' && $file != '..' && is_file($path . $file)) {
                    $info = pathinfo($path . $file);
                    if (!isset($info['extension']) or (!isset($source->extensions) or in_array(strtolower($info['extension']), $source->extensions))) {
                        $item = [
                            'file' => $file,
                        ];
                        if ($this->config->createThumb) {
                            if (!is_dir($path . $this->config->thumbFolderName)) {
                                mkdir($path . $this->config->thumbFolderName, 0777);
                            }
                            if (!file_exists($path . $this->config->thumbFolderName . DIRECTORY_SEPARATOR . $file)) {
                                $img = new \abeautifulsite\SimpleImage($path . $file);
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

            $this->response->data->sources[$key] = $sourceData;
        }
    }

    /**
     * Load all folders from folder ore source or sources
     */
    protected function actionFolders() {
        foreach ($this->config->sources as $key => $source) {
            if ($this->request->source && $key !== $this->request->source) {
                continue;
            }

            $source = (object)$source;

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

            $this->response->data->sources[$key] = $sourceData;
        }
    }


    protected function actionUploadRemote() {
        $url = $this->request->url;
        if (!$url) {
            throw new \ErrorException('Need url parameter', 400);
        }

        $result = parse_url($url);

        if (!isset($result['host']) || !isset($result['path'])) {
            throw new \ErrorException('Not valid URL', 400);
        }
        
        $filename = $this->makeSafe(basename($result['path']));
        
        if (!$filename) {
            throw new \ErrorException('Not valid URL', 400);
        }

        $this->downloadRemoteFile($url, $this->getRoot($this->getSource()) . $filename);

        $this->response->data->sources[$this->request->source] = [
            'newfilename' => $filename,
            'baseurl' => $this->getSource()->baseurl,
        ];
    }
    protected function actionUpload() {
        $path = $this->getPath();
        $errors = [
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        ];
        
        if (isset($_FILES['files']) and is_array($_FILES['files']) and isset($_FILES['files']['name']) and is_array($_FILES['files']['name']) and count($_FILES['files']['name'])) {
            foreach ($_FILES['files']['name'] as $i=>$file) {
                if ($_FILES['files']['error'][$i]) {
                    throw new \ErrorException(isset($errors[$_FILES['files']['error'][$i]]) ? $errors[$_FILES['files']['error'][$i]] : 'Error', $_FILES['files']['error'][$i]);
                }

                $tmp_name = $_FILES['files']['tmp_name'][$i];

                if ($this->config->maxFileSize and filesize($tmp_name) > $this->convertToBytes($this->config->maxFileSize)) {
                    unlink($tmp_name);
                    throw new \ErrorException('File size exceeds the allowable', 403);
                }

                if (move_uploaded_file($tmp_name, $file = $path.$this->makeSafe($_FILES['files']['name'][$i]))) {
                    $info = pathinfo($file);

                    if (!isset($info['extension']) or (isset($this->config->extensions) and !in_array(strtolower($info['extension']), $this->config->extensions))) {
                        unlink($file);
                        throw new \ErrorException('File type not in white list', 403);
                    }

                    $this->response->data->message[] = 'File ' . $_FILES['files']['name'][$i] . ' was upload';
                    $this->response->data->files[] = str_replace($this->root, '', $file);
                } else {
                    if (!is_writable($path)) {
                        throw new \ErrorException('Destination directory is not writeble', 424);
                    }

                    throw new \ErrorException('No files have been uploaded', 422);
                }
            }
            $this->response->data->baseurl = $this->config->baseurl;
        }
 
        if (!count($this->response->data->files)) {
            throw new \ErrorException('No files have been uploaded', 422);
        }
    }
    protected function actionRemove() {
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
                throw new \ErrorException('Delete failed! '.$error->message, 424);
            }
        } else {
            throw new \ErrorException('The destination path has not been set', 400);
        }
    }
    protected function actionCreate() {
        $destinationPath = $this->getPath();
        $folderName = $this->makeSafe(isset($this->request->name) ?  $this->request->name : '');
        if ($destinationPath) {
            if ($folderName) {
                if (!realpath($destinationPath . $folderName)) {
                    mkdir($destinationPath.$folderName, 0777);
                    if (is_dir($destinationPath.$folderName)) {
                        $this->response->data->message = 'Directory was created';
                    } else {
                        throw new \ErrorException('Directory was not created', 404);
                    }
                } else {
                    throw new \ErrorException('Folder already exists', 406);
                }
            } else {
                throw new \ErrorException('The name for the new folder has not been set', 406);
            }
        } else {
            throw new \ErrorException('The destination folder has not been set', 406);
        }
    }
    protected function actionMove() {
        $dstpath = $this->getPath();
        $srcpath = $this->getPath('filepath');

        if ($srcpath) {
            if ($dstpath) {
                if (is_file($srcpath) or is_dir($srcpath)) {
                    rename($srcpath, $dstpath.basename($srcpath));
                } else {
                    throw new \ErrorException('Not file', 404);
                }
            } else {
                throw new \ErrorException('Need destination path', 400);
            }
        } else {
            throw new \ErrorException('Need source path', 400);
        }
    }
    protected function actionResize() {
        
        $info = $this->getImageEditorInfo();

        if ((int)$info->box->w <= 0) {
            throw new \ErrorException('Width not specified', 400);
        }

        if ((int)$info->box->h <= 0) {
            throw new \ErrorException('Height not specified', 400);
        }
        

        $info->img
            ->resize((int)$info->box->w, (int)$info->box->h)
            ->save($info->path.$info->newname, $this->config->quality);

    }
    protected function actionCrop() {
        $info = $this->getImageEditorInfo();

        if ((int)$info->box->x < 0 || (int)$info->box->x > (int)$info->width) {
            throw new \ErrorException('Start X not specified', 400);
        }

        if ((int)$info->box->y < 0 || (int)$info->box->y > (int)$info->height) {
            throw new \ErrorException('Start Y not specified', 400);
        }

        if ((int)$info->box->w <= 0) {
            throw new \ErrorException('Width not specified', 400);
        }

        if ((int)$info->box->h <= 0) {
            throw new \ErrorException('Height not specified', 400);
        }

        $info->img
            ->crop((int)$info->box->x, (int)$info->box->y, (int)$info->box->x + (int)$info->box->w, (int)$info->box->y + (int)$info->box->h)
            ->save($info->path.$info->newname, $this->config->quality);

    }

    /**
     * Get filepath by URL for local files
     *
     * @metod actionGetFileByURL
     */
    function actionGetLocalFileByURL() {
        $url = $this->request->url;
        if (!$url) {
            throw new \ErrorException('Need full url', 400);
        }

        $parts = parse_url($url);

        if (empty($parts['path'])) {
            throw new \ErrorException('Empty url', 400);
        }
        
        $source = $this->config->sources->{$this->request->source};
        $base = parse_url($source->baseurl);

        $path = preg_replace('#^(/)?' . $base['path'] . '#', '', $parts['path']);


        $root = $this->getPath($source);

        if (!file_exists($root . $path) || !is_file($root . $path)) {
            throw new \ErrorException('File does not exist or is above the root of the connector', 424);
        }
        
        $this->response->data->path = str_replace($root, '', dirname($root . $path));
        $this->response->data->name = basename($path);
    }
}
