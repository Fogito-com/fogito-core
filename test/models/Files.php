<?php
namespace Models;

use Lib\Helpers;
use Lib\Lang;

class Files extends \Lib\ModelManager
{
    const PARENT_TYPE_USERS = 'users';
    const PARENT_TYPE_CATEGORIES = 'categories';
    const PARENT_TYPE_SERVICES = 'services';

    const SIZE_TINY   = 'tiny';
    const SIZE_SMALL  = 'small';
    const SIZE_MEDIUM = 'medium';
    const SIZE_LARGE  = 'large';

    const TYPES = [
        "image/jpeg",
        "image/jpe",
        "image/jpg",
        "image/png",
        "image/gif",
    ];

    const DIMENSIONS = [
        'tiny'   => 120,
        'small'  => 320,
        'medium' => 480,
        'large'  => 800,
    ];

    const QUALITY       = 90;
    const MAX_FILE_SIZE = 5 * 1024 * 1024;

    public $_id;
    public $user_id;
    public $parent_type;
    public $parent_id;
    public $file;
    public $filename;
    public $type;
    public $extension;
    public $size;
    public $avatars = [];

    /**
     * getSource
     *
     * @return void
     */
    public static function getSource()
    {
        return "files";
    }

    /**
     * getAvatar
     *
     * @param  mixed $data
     * @param  mixed $size
     * @return void
     */
    public static function getAvatar($data, $size = false)
    {
        if ($data->avatars) {
            $avatars = [];
            foreach ($data->avatars as $key => $value) {
                $avatars[$key] = CLOUD_URL . '/' . $value;
            }
        } else {
            if ($data->parent_type == 'users') {
                $filename = $data->gender == Users::GENDER_FEMALE ? 'no-avatar-female.svg' : 'no-avatar.svg';
            } else {
                $filename = 'no-image.svg';
            }
            $avatars = [];
            foreach (self::DIMENSIONS as $key => $value) {
                $avatars[$key] = CLOUD_URL . '/' . $filename;
            }
        }

        if (is_array($size)) {
            $arr = [];
            foreach ($size as $key) {
                $arr[$key] = $avatars[$key];
            }
            return $arr;
        } else {
            return is_string($size) && \array_key_exists($size, $avatars) ? $avatars[$size] : $avatars;
        }
    }

    /**
     * getAvatarById
     *
     * @param  mixed $id
     * @return void
     */
    public static function getAvatarById($id, $size = false)
    {
        $data = self::findFirst([
            [
                '_id'        => self::objectId($id),
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);

        return self::getAvatar($data, $size);
    }

    /**
     * getFileUrl
     *
     * @param  mixed $data
     * @return void
     */
    public static function getFileUrl($data)
    {
        return CLOUD_URL . '/' . $data->file;
    }

    /**
     * copyTempFile
     *
     * @param  mixed $temp_id
     * @param  mixed $properties
     * @param  mixed $deleteTempFile
     * @return void
     */
    public static function copyTempFile($temp_id, $properties = [], $deleteTempFile = true)
    {
        $temp = FilesTemp::findFirst([
            [
                '_id'        => self::objectId($temp_id),
                'is_deleted' => [
                    '$ne' => true,
                ],
            ],
        ]);
        if (!$temp) {
            throw new \Exception(Lang::get('Temp file not found'));
        }

        $properties = array_merge((array) $temp, (array) $properties);

        if ($properties['_id']) {
            if (!self::isMongoId($properties['_id'])) {
                throw new \Exception(Lang::get('Wrong temp file _id'));
            }

            if (!$properties['_id'] instanceof \MongoDB\BSON\ObjectID) {
                $properties['_id'] = self::objectId($properties['_id']);
            }

            $duplicateFile = self::findById($properties['_id']);
            if ($duplicateFile) {
                $duplicateFile->delete();
            }
        }

        $i = new self;
        foreach ($properties as $key => $value) {
            if (\property_exists(self::class, $key)) {
                $i->{$key} = $value;
            }
        }
        $i->save(!!isset($properties['_id']));

        if ($deleteTempFile) {
            $temp->delete();
        }
        return $i;
    }

    /**
     * pathGen
     *
     * @param  mixed $lengths
     * @param  mixed $splahCount
     * @return void
     */
    public static function pathGen($lengths, $splahCount = 1)
    {
        $path = Helpers::randomizer($lengths);
        $i    = 1;
        while ($i < $splahCount) {
            $path = $path . '/' . Helpers::randomizer($lengths);
            $i++;
        }
        return rtrim($path, '/');
    }

    /**
     * getFileData
     *
     * @param  mixed $file
     * @return void
     */
    public static function getFileData($file)
    {
        if ($file['tmp_name']) {
            $basename = strtolower($file['name']);
            $filename = substr($basename, 0, strrpos($basename, '.'));
            if (!$filename) {
                $filename = $basename;
            }
            $type      = mime_content_type($file['tmp_name']);
            $extension = end(explode('.', $basename));

            $data = [
                'file'      => $file['tmp_name'],
                'basename'  => $basename,
                'filename'  => $filename,
                'type'      => $type,
                'extension' => $extension,
                'size'      => $file['size'],
            ];
        } else {
            $pathinfo = pathinfo($file);
            $basename = strtolower($info['basename']);
            $filename = strtolower($info['filename']);
            if (!$filename) {
                $filename = substr($basename, 0, strrpos($basename, '.'));
                if (!$filename) {
                    $filename = $basename;
                }
            }
            $data = [
                'file'      => $file,
                'basename'  => $basename,
                'filename'  => $filename,
                'type'      => mime_content_type($file),
                'extension' => strtolower($info['extension']),
                'size'      => filesize($file),
            ];
        }
        return $data;
    }

    /**
     * excludeRootPath
     *
     * @param  mixed $path
     * @return void
     */
    public static function excludeRootPath($path)
    {
        return substr($path, strlen(STORAGE_PATH) + 1, strlen($path));
    }

    /**
     * Filter data
     *
     * @param object $data
     * @return array
     */
    public static function filterData($data)
    {
        if ($data) {
            $res = [
                'id'       => $data->getId(),
                'file'     => $data->file,
                'filename' => $data->filename,
                'size'     => $data->size,
                'type'     => $data->type,
            ];
            if (\in_array($data->extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $res['avatars'] = $data->avatars;
            }
            return $res;
        }
        return null;
    }
}
