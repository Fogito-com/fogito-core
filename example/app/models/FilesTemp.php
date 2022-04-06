<?php
namespace Models;

use Fogito\App;

class FilesTemp extends \Fogito\Db\ModelManager
{
    public $_id;
    public $user_id;
    public $parent_type;
    public $parent_id;
    public $file;
    public $filename;
    public $size;
    public $type;
    public $extension;
    public $avatars = [];
    
    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return 'files_temp';
    }


}
