<?php
namespace zhanshop;

class Aes
{
    /**
     * var string $method 加解密方法，可通过openssl_get_cipher_methods()获得
     */
    protected $method = 'AES-128-ECB';

    protected $commonKey = 'bxV8lFZs8oqmREMz9^kacHn%y0zE*KnNGwp*dt!JV7olxwKz6d1NjLH9TQSBqEZhvF4C$vzC*6F^oonTi9$Xt4Tt35QAffigBiN';
    /**
     * var string $secret_key 加解密的密钥
     */
    //目前提供3个版本的加密值
    protected  $secret_all_key = [
        '0' => [
            'a3b8ff236e128c5d860742fd74c3aa390e0547153d2a456983c469560b4891aa5923b18832cc1a91bf90e663171bc94f9e5817478b62c2c8c39a420c18158502',
            'fb82541c6a3f9c51f2f5f3934566870faf0ab82835ae45656b3e86a4abb9074ddada6b35e33134c82b78144c2c717ef9775ecf6a7048556680fd168ad1d1932a',
            'd7dcafe5285ae9fb44f119e4b893f791895c5c4dffe2d90cc1a716778363049498aeff18147587bf6d53350b3509379e5d5c1209d151983bfcc076ef8e5815ef',
            'df0f01faf740561136ce8ea357b3a1658890e34a0d3516a43dc9d730397269345c25c4cc6ec22aa6ba41f4bfe36027a0034493d1a95d2df25fab0a74b949f8fa',
            'b783f10e27b5d39f66a43b6c2e0a837d0ec3234fac8b0e86e3a7b38b482482b60a3bdaa7e477f01507427fd6401e5cd51a5511f7d902d71ad05213137e9ff6fb',
            '83937e703ee076937348570e9bc55f5cef6c3c3d20dc82318062b7725d0a0f76ed67dc9e51a50980a0ba743a3e4da8144491d5035daa0f45fe90c42b9f7a77a9',
            '0f8dcd9d1226c57e3af33d68316e1cb1bbc681a9c333a15a2e8372a076db9574ef88b49e9641cf49a0d5900cb557b5d2c404927227b992566428dd4685bcac5f',
            '1a65ceb865f306bfb71eea561c799875f21d704f8ee789790e9b27c6b9a9aa1c9842f01bc686704e16f160f5d4683d3e49fc9aa2ed83e784e6eed4e794684ff7',
            '4d3ab26c87f567c67229e4bd9f193e25803a63e31d13c264377eccd18c1a7de6917f0cfc81562c9ec40e13f988dcf2535a7e68742a26913f32bc9367b2848fa0',
            '9687cbaf9c46be552a35b57770b162a7997d890a5a5a499bd785193a3fa49d9215ff08349114e788964bc5187fc48b8a9152b5104dc77c3368fa94501ecd1501'
        ],
    ];

    protected $secret_version = '0';//加密版本号 版本号可以定期修改增强安全

    /**
     * var string $iv 加解密的向量，有些方法需要设置比如CBC
     */
    protected $iv = '';

    /**
     * var string $options （不知道怎么解释，目前设置为0没什么问题）
     */
    protected $options = 0;

    /**
     * 构造器
     * Aes constructor.
     */
    public function __construct(){
        $appKey = App::config()->get('app.key');
        if($appKey) $this->commonKey = $appKey;
    }

    /**
     * @公共加密
     * @param string $data
     * @return string
     */
    protected function commonEncode($data){
        return openssl_encrypt($data, $this->method, $this->commonKey, $this->options, $this->iv);
    }
    /**
     * @公共解密
     * @param string $data
     * @return string
     */
    protected function commonDecode($data){
        return openssl_decrypt($data, $this->method, $this->commonKey, $this->options, $this->iv);
    }
    /**
     * @加密方法，对数据进行加密，返回加密后的数据
     *
     * @param string $data 要加密的数据
     *
     * @return string
     *
     */
    public function encrypt($data)
    {
        //把这里的值再进行加密
        $key = rand(0,9);
        $secretKey = $this->secret_all_key[$this->secret_version][$key];
        $version = rand(100000, 999999)."@".$this->secret_version.'/'.$key;
        $version = $this->commonEncode($version);
        $version = rtrim($version, "==");
        return $version.openssl_encrypt($data, $this->method, $secretKey, $this->options, $this->iv);
    }

    /**
     * @解密方法，对数据进行解密，返回解密后的数据
     *
     * @param string $data 要解密的数据
     *
     * @return string
     *
     */
    public function decrypt($data)
    {
        //取14位
        $version = substr($data, 0, 22).'==';
        $version = $this->commonDecode($version);
        $version = explode('@', $version);
        if(isset($version[1]) == false) return null;
        $version = $version[1];
        $version = explode('/', $version);
        $secretKey = $this->secret_all_key[$version[0]][$version[1]];
        $data = substr($data, 22);
        return openssl_decrypt($data, $this->method, $secretKey, $this->options, $this->iv);
    }
}

