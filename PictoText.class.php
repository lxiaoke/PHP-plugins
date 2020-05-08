<?php

namespace E;

class PictoText
{
    /**
     * @var String $access_token
     */
    protected $access_token = '24.0344fd2474c10776951fac6e47cc7acf.2592000.1591513941.282335-19775652';

    /**
     * @var Array $allowed_type
     */
    protected $allowed_type = [ IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG ];

    /**
     * 执行
     * @param  String $pic
     * @return String
     */
    public function run($pic)
    {
        // 判断文件是否存在
        if (file_exists($pic)) {

            // 判断是否是允许的图片类型
            if ($this->isAllowedType($pic)) {
                $img = file_get_contents($pic);
                return $this->translate($img);
            }

            return json_encode(['code' => 0, 'msg' => '不支持的图片类型']);
        }

        return json_encode(['code' => 0, 'msg' => '文件不存在']);
    }

    /**
     * 获取 Access Token
     * @param  String $app_id
     * @param  String $secret_key
     * @return String
     */
    public function getAccessToken($app_id = '', $secret_key = '')
    {
        $url = 'https://aip.baidubce.com/oauth/2.0/token';

        $post_data['grant_type']       = 'client_credentials';
        $post_data['client_id']        = $app_id;       // jipoAfal3wdbwRc8W3oTQBGq
        $post_data['client_secret']    = $secret_key;   // oNvh3CpcqbxhnvtlHdD8sSz9KsCXHxRM

        $o = '';
        foreach ($post_data as $k => $v) {
            $o .= "$k=" . urlencode($v) . "&";
        }
        $post_data = substr($o, 0, -1);                 // 去掉最后的 &

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

        $data = curl_exec($curl);
        curl_close($curl);
        
        return $data;
    }

    /**
     * 转换操作
     * @param  String $img
     * @return String
     */
    protected function translate($img)
    {
        // api 接口
        $url = 'https://aip.baidubce.com/rest/2.0/ocr/v1/general_basic?access_token=' . $this->access_token;

        $data = [ 'image' => base64_encode($img) ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }

    /**
     * 判断是否是允许的图片类型
     * @param  String $img
     * @return Boolean
     */
    protected function isAllowedType($img)
    {
        return in_array(exif_imagetype($img), $this->allowed_type);
    }
}
