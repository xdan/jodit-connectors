<?
return [
    'datetimeFormat' => 'm/d/Y g:i A',
    'quality' => 90,
    'defaultPermission' => 0775,

    'sources' => [
        'default' => [
            //'root' => realpath(realpath(dirname(__FILE__) . '/..') . '/files') . DIRECTORY_SEPARATOR,
            //'baseurl' => 'files/',
            //'extensions' => ['jpg', 'png', 'gif', 'jpeg'],
        ],
    ],

    'createThumb' => true,
    'thumbFolderName' => '_thumbs',
    'excludeDirectoryNames' => ['.tmb', '.quarantine'],
    'maxFileSize' => '8mb',

//    'baseurl' => ($_SERVER['HTTPS'] ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/',
//    'root' => __DIR__,
//    'extensions' => ['jpg', 'png', 'gif', 'jpeg'],
];