<?php

class JoditRestTestApplication extends \jodit\JoditApplication {
    function checkPermissions() {
        if (!in_array(@$_SERVER['REMOTE_ADDR'], ['127.0.0.1', '::1']) or isset($_GET['auth'])) {
            throw new ErrorException('Need authorization', 403);
        }
    }
}