<?php
use Jodit\Application;

class JoditRestApplication extends Application {
    /**
     * @example Jodit connector in Joomla
     * ```php
     * function checkPermissions() {
     *      $user = JFactory::getUser();
     *      if (!$user->id) {
     *           trigger_error('You are not authorized!', E_USER_WARNING);
     *      }
     * }
     * ```
     */
    function checkPermissions() {
        // Rewrite this code for your system
        // if (empty($_SESSION['filebrowser'])) {
        //     trigger_error('You do not have permission to view this directory, E_USER_WARNING);
        // }
        throw new ErrorException('You need override `checkPermissions` method in file `Application.php`', 501);
    }
}