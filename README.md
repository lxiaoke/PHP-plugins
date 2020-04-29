# PHP plugins

#### 介绍
自定义的一些 PHP 插件

#### 使用说明

##### FileUpload.class.php

1. 引入文件

```php
include 'FileUpload.class.php';
```

2. 实例化类

```php
$uploader = new E\FileUpload();
```

3. 配置

```php
$uploader->config([
    'name'      => 'file',
    'exts'      => ['jpg'],
    'type'      => 'image',
    'max_size'  => 2,
]);
```

配置项的说明：

| 配置项   | 说明                                                         |
| -------- | ------------------------------------------------------------ |
| name     | 是 HTML 中 input 的 name，例如： \<input type="file" name="file"\>，默认是 file |
| exts     | 所允许上传的文件扩展名，类型为数组，默认是任何类型的文件     |
| type     | 上传文件类型，可以通过设置 type 属性，设置允许上传类型，默认为 file，<br>目前可选类型有：file 全部文件类型，image 图片类型， zip 压缩包，<br>注意：exts优先级高于 type，<br>即如果设置了 type 为 file，exts 为 \['jpg'\] 时，也还是只能上传 jpg 类型的文件的 |
| max_size | 所允许上传的大小，单位为 M，默认为 2M                        |

4. 上传

```php
$uploader->upload();
```

5. 保存

```php
$uploader->save('./uploads/', 'test')
```

save 方法的返回值是一个布尔值，上传成功返回 true，失败返回 false，可以根据返回的状态进行相应的操作，如果失败，可以使用 `errorCode()` 方法获取错误代码，使用 `errorMsg()` 方法获取错误信息。

> save 方法的第一个参数为必填参数，是要保存的路径，第二个参数是文件名，可选，如果不填，则会自动生成，生成规则： e_ 连接上当前时间的时间戳再连接5位随机数

也可以使用链式操作：

```php
$uploader->upload()->save('./uploads/', 'test');
```

**错误码说明**

| 错误码 | 说明                                                       |
| ------ | ---------------------------------------------------------- |
| 0      | 无错误                                                     |
| 1      | 上传文件超出限制大小                                       |
| 2      | 上传文件超出 form 表单所设置的 MAX_FILE_SIZE，也是文件过大 |
| 3      | 文件只有部分被上传                                         |
| 4      | 没有文件被上传                                             |
| 5      | 存在同名文件                                               |
| 6      | 文件丢失                                                   |
| 7      | 文件写入失败                                               |
| 8      | 上传的文件类型不被允许                                     |
|\-1     | 未知错误|

