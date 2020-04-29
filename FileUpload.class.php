<?php

namespace E;

class FileUpload
{
    /**
     * @var $name 上传文件标记名
     */
    protected $name = 'file';

    /**
     * @var $ext 所允许的扩展名
     */
    protected $exts  = [];

    /**
     * @var $file 上传的文件资源
     */
    protected $file = null;

    /**
     * @var $upload_success 是否上传成功标志
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
     * @var $type 文件类型，默认为图片类型
     */
    protected $type = 'image';

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
            if (property_exists($this, $key)) {
                $this->$key = $val;
            }
        }
        return $this;
    }

    /**
     * 进行文件上传操作
     *
     * @return E\FileUpload
     */
    public function upload()
    {
        $this->file     = $this->getFile();
        $this->file_ext = strrchr($this->file['name'], '.');

        $this->upload_success = !($this->uploadError() || $this->overMaxSize() || $this->notAllowType());

        return $this;
    }

    /**
     * 判断文件是否上传成功
     * 
     * @return boolean
     */
    public function uploadSuccess()
    {
        return $this->upload_success;
    }

    /**
     * 保存已上传的文件
     *
     * @param string $path
     * @param string $file_name
     *
     * @return boolean
     */
    public function save($path, $file_name = '')
    {
        if (!$this->uploadSuccess()) {
            return false;
        }

        // 判断文件夹是否存在，如果不存在，新建一个文件夹
        if (!is_dir($path)) {
            mkdir($path);
        }

        // 获取文件名，不包含后缀
        $file_name = $file_name ?: 'e_' . time() . mt_rand(10000, 99999);

        $file = rtrim($path, '/') . '/' . $file_name . $this->file_ext;

        // 判断文件是否存在
        if (file_exists($file)) {
            $this->error_code = self::ERR_FILE_EXIST;
            return false;
        }

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
     * 判断文件是否超出限制大小
     *
     * @return boolean
     */
    protected function overMaxSize()
    {
        if ($this->file['size'] > $this->getMaxSize() * 1024 * 1024 * 8) {
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
        $exts_arr = [
            'image' => ['jpg', 'jpeg', 'png', 'gif'],
            'file'  => [],
            'zip'   => ['zip', 'rar', '7z']
        ];

        return $exts_arr[$this->type] ?? [];
    }

    /**
     * 判断是否是允许的类型
     * 
     * @return boolean
     */
    protected function notAllowType()
    {
        $this->exts = $this->exts ?: $this->getExtsByType();

        if (!$this->exts) return false;

        if (preg_match('/^\.' . implode('|', $this->exts) . '$/i', $this->file_ext)) {
            return false;
        }

        $this->error_code = self::ERR_FILE_TYPE;
        return true;
    }
}

