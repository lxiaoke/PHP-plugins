<?php

namespace E;

class FileUpload
{
    /**
     * @var String $name 上传文件标记名
     */
    protected $name = 'file';

    /**
     * @var Array $ext 所允许的扩展名
     */
    protected $exts  = [];

    /**
     * @var Resource $file 上传的文件资源
     */
    protected $file = null;

    /**
     * @var Boolean $upload_success 是否上传成功标志
     */
    protected $upload_success = false;

    /**
     * @var $max_size 上传文件所允许的大小，单位为 M
     */
    protected $max_size = 2;

    /**
     * @var $error_code 错误码
     */
    protected $error_code = 0;

    /**
     * @var $error_msg 错误信息
     */
    protected $error_msg = '';

    /**
     * @var $file_ext 文件扩展名
     */
    protected $file_ext = '';

    /**
     * @var $type 文件类型，默认为任意类型
     */
    protected $type = 'file';

    /**
     * @var $path 文件保存路径，默认是当前文件夹
     */
    protected $path = __DIR__;

    /**
     * @var $allow_properties 允许修改的属性
     */
    protected $allow_properties = [ 'name', 'exts', 'max_size', 'path' ];

    /**
     * @var $exts_arr 文件后缀数组，键名为其大类型
     */
    protected $exts_arr = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'ico', 'svg', 'tif', 'tiff', 'webp'],
        'text'  => ['css', 'csv', 'htm', 'html', 'ics', 'js', 'mjs', 'txt'],
        'audio' => ['aac', 'mid', 'midi', 'mp3', 'oga', 'wav', 'weba'],
        'video' => ['avi', 'mpeg', 'ogv', 'webm', 'mp4'],
        'app'   => [],
        'zip'   => ['zip', 'rar', '7z'],
        'font'  => [],
        'file'  => [],
    ];

    /**
     * @var $mime_prefix_arr Mime 大类型数组
     */
    protected $mime_type_arr = [
        'image' => 'image',
        'text'  => 'text',
        'audio' => 'audio',
        'video' => 'video',
        'font'  => 'font',
        'app'   => 'application',
        'zip'   => 'application',
    ];

    protected const ERR_OK         = 0;
    protected const ERR_FILE_SIZE  = 1;
    protected const ERR_FORM_SIZE  = 2;
    protected const ERR_PARTIAL    = 3;
    protected const ERR_NO_FILE    = 4;
    protected const ERR_FILE_EXIST = 5;
    protected const ERR_NO_TMP_DIR = 6;
    protected const ERR_CANT_WRITE = 7;
    protected const ERR_FILE_TYPE  = 8;

    /**
     * 配置上传信息
     *
     * @param array $arr
     * @return E\FileUpload
     */
    public function config($arr)
    {
        foreach ($arr as $key => $val) {
            // 如果 $key 在允许修改的属性数组内并且类中存在 $key 属性，修改属性 $key
            if (in_array($key, $this->allow_properties) && property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        $this->exts = $this->exts ?: $this->getExtsByType();

        return $this;
    }

    /**
     * 进行文件上传操作，只是对上传文件进行初步的判断，没有进行文件的移动
     *
     * @return E\FileUpload
     */
    public function upload()
    {
        // 获取上传文件和文件后缀
        $this->file     = $this->getFile();
        $this->file_ext = strrchr($this->file['name'], '.');

        $this->upload_success = !($this->uploadError() || $this->overMaxSize() || $this->notAllowType());

        return $this;
    }

    /**
     * 保存已上传的文件
     *
     * @param string $path
     * @param string $file_name
     *
     * @return boolean
     */
    public function save($path = '', $file_name = '')
    {
        // 判断在上传阶段是否出现错误，如果没有上传成功，返回 false
        if (!$this->upload_success) {
            return false;
        }

        $path = $path ?: $this->path;
        // 判断文件夹是否存在，如果不存在，新建一个文件夹
        if (!is_dir($path)) {
            mkdir($path);
        }

        // 获取文件名，不包含后缀，如果没有设置，则自动生成 e_ + 当前时间时间戳 + 5位随机数
        $file_name = $file_name ?: 'e_' . time() . mt_rand(10000, 99999);

        // 连接路径、文件名、后缀
        $file = rtrim($path, '/') . '/' . $file_name . $this->file_ext;

        // 判断文件是否存在，如果文件已经存在，返回 false
        if (file_exists($file)) {
            $this->error_code = self::ERR_FILE_EXIST;
            return false;
        }

        // 移动文件
        if (move_uploaded_file($this->file['tmp_name'], $file)) {
            return true;
        }

        // 文件未上传成功，出现未知错误
        $this->error_code = -1;
        return false;
    }

    /**
     * 返回错误码
     * 
     * @return integer
     */
    public function errorCode()
    {
        return $this->error_code;
    }

    /**
     * 返回错误信息
     *
     * @return string
     */
    public function errorMsg()
    {
        !$this->error_msg && $this->setErrorMsgByCode($this->error_code);

        return $this->error_msg;
    }

    /**
     * 获取上传文件
     * 
     * @return mixed
     */
    protected function getFile()
    {
        return $_FILES[$this->name];
    }

    /**
     * 判断是否上传错误
     * 
     * @return boolean
     */
    protected function uploadError()
    {
        $this->error_code = $this->file['error'];

        return (bool)$this->file['error'];
    }

    /**
     * 根据错误代码设置错误信息
     *
     * @param  int $code
     */
    protected function setErrorMsgByCode($code)
    {
        $msg_arr = [
            '',
            '上传文件最大为 ' . $this->getMaxSize() . 'M',
            '上传文件过大',
            '文件只有部分被上传',
            '没有文件被上传',
            '文件已存在',
            '文件丢失',
            '文件写入失败',
            '只能上传' . implode(',', $this->exts) . '类型的文件'
        ];

        $this->error_msg = $msg_arr[$code] ?? '未知错误';
    }

    /**
     * 获取上传文件所限制的最大大小
     * 
     * @return int
     */
    protected function getMaxSize()
    {
        return min($this->max_size, (int)ini_get('upload_max_filesize'));
    }

    /**
     * 判断文件是否超出限制大小,
     * 超出限制大小返回 true，否则返回 false
     *
     * @return boolean
     */
    protected function overMaxSize()
    {
        // 将以 M 为单位的限制大小转换成 bit
        $max_size_bit = $this->getMaxSize() * 1024 * 1024 * 8;

        if ($this->file['size'] > $max_size_bit) {
            $this->error_code = self::ERR_FILE_SIZE;
            return true;
        }
        
        return false;
    }

    /**
     * 通过类型获取后缀名数组
     *
     * @return array
     */
    protected function getExtsByType()
    {
        return $this->exts_arr[$this->type] ?? [];
    }

    /**
     * 验证 Mime 类型，验证成功返回 true，失败返回 false
     * 
     * @param  string $file
     * @return boolean
     */
    protected function validateMime($file)
    {
        $mime_type = $this->getMimeType($file);
        if () {
            //
        }
        return false;
    }

    /**
     * 获取 Mime 类型
     *
     * @param  string $file
     * @return string
     */
    protected function getMimeType($file)
    {
        // 创建一个 fileinfo 资源
        $finfo = new finfo(FILEINFO_MIME);

        // 创建资源成功，并且传入的文件存在
        if ($finfo && file_exists($file)) {
            $mime_info = $finfo->file($file);
            $mime_arr  = explode('; ', $mime_info);

            if ($mime_arr && is_array($mime_arr) && count($mime_arr)) {
                return $mime_arr[0];
            }
        }

        return '';
    }

    /**
     * 判断是否是允许的类型。
     * 如果不允许上传，返回 true，否则返回 false
     * 
     * @return boolean
     */
    protected function notAllowType()
    {
        // 允许的后缀数组为空，说明是任意类型的文件，
        // 因此直接返回 false，不进行后缀名的判断
        if (!$this->exts) return false;

        if (preg_match('/^\.' . implode('|', $this->exts) . '$/i', $this->file_ext)) {
            return false;
        }

        // 后缀名验证未通过，返回 true
        $this->error_code = self::ERR_FILE_TYPE;
        return true;
    }
}

