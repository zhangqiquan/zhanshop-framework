<?php

namespace zhanshop\apidoc;

use zhanshop\App;
use zhanshop\Error;
use zhanshop\Request;

class SwaggerDoc
{
    /**
     * 获取当前db对象
     * @return Sqlite
     * @throws \Exception
     */
    protected function db(){
        $database = 'swagger';
        $path = App::runtimePath().DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.$database.'.db';
        if(!file_exists($path)){
            $this->createDb($path);
            App::error()->setError($database.'库文件不存在');
        }
        return new Sqlite($path);
    }

    protected function createDb($path){
        if(!file_exists(App::runtimePath().DIRECTORY_SEPARATOR.'doc')){
            mkdir(App::runtimePath().DIRECTORY_SEPARATOR.'doc');
        }

        $sqlLite = new Sqlite($path);
        $sqlLite->execute('CREATE TABLE "apidocs" (
  "id" INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT,
  "type" TEXT DEFAULT http,
  "version" TEXT,
  "uri" TEXT,
  "doc" INTEGER DEFAULT 0,
  "method" TEXT,
  "detail" TEXT,
  "success" TEXT,
  CONSTRAINT "doc" UNIQUE ("type", "version", "uri", "doc", "method")
);');
        $sqlLite->execute('CREATE TABLE "apimenu" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "title" TEXT,
  "parent_id" INTEGER DEFAULT 0,
  "target" TEXT,
  "icon" TEXT,
  "sortrank" integer DEFAULT 50
);');
        $sqlLite->execute('CREATE TABLE "doclist" (
  "id" text NOT NULL,
  "title" TEXT,
  "version" TEXT,
  "url" TEXT,
  "config" TEXT,
  "server_url" TEXT,
  "create_time" DATE,
  PRIMARY KEY ("id"),
  CONSTRAINT "url" UNIQUE ("url")
);');

    }

    /**
     * apidoc入口
     * @param Request $request
     * @return void
     */
    public function apidoc(Request &$request){
        $method = $request->method();
        $data = $this->$method($request);
        return [
            'code' => 0,
            'msg' => 'ok',
            'data' => $data
        ];
    }

    /**
     * 获取所有的文档
     * @param Request $request
     * @return array|false
     * @throws \Exception
     */
    public function doclist(Request &$request){
        $page = $request->param('page', 1);
        $limit = $request->param('limit', 20);
        if($limit > 1000) $limit = 1000;
        $offset = ($page - 1) * $limit;
        return [
            'total' => $this->db()->table('doclist')->count(),
            'list' => $this->db()->table('doclist')->order('create_time desc')->limit($offset, $limit)->select()
        ];
    }
    /**
     * 获取config
     * @param Request $request
     * @return void
     */
    public function config(Request &$request){
        $id = $request->header('doc_id');
        $variable = $request->param('variable');
        $row = $this->getDoc($id);
        if($row == false) App::error()->setError('ID:'.$id.'的文档不存在', Error::NOT_FOUND);
        $config = json_decode($row['config'], true);
        if($variable){
            // 去更新
            foreach($variable as $k => $v){
                if(isset($config[$k])){
                    $config[$k]['default'] = $v;
                }
            }
            $this->db()->table('doclist')->where(['id' => $id])->update([
                'server_url' => $variable['server_url'],
                'config' => json_encode($config, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE),
            ]);
            return [];
        }
        $variables = [
            'server_url' => [
                'field' => 'server_url',
                'title' => '接口访问地址',
                'null' => 'no',
                'input_type' => 'url',
                'default' => $row['server_url'],
            ]
        ];

        if($config){
            foreach($config as $k => $v){
                $variables[$k] = [
                    'field' => $k,
                    'null' => 'no',
                    'title' => $v['description'] ?? '',
                    'input_type' => 'text',
                    'default' => $v['default'] ?? '',
                ];
            }
        }
        return $variables;
    }

    /**
     * getSwaggerDoc
     * @param $url
     * @return false|mixed|string
     */
    protected function getSwaggerDoc($url){
        $localSwagger = App::runtimePath().DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'swagger-'.md5($url).'.json';
        $body = [];
        if(file_exists($localSwagger)){
            $body = file_get_contents($localSwagger);
            if($body) $body = json_decode($body, true);
        }else{
            try {
                $body = App::curl()->request($url, 'GET')['body'];
                if($body == false) App::error()->setError($url.'内容为空');
                $body = json_decode($body, true);
            }catch (\Throwable $e){

            }
        }
        return $body;
    }
    /**
     * 获取菜单
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function index(Request &$request){
        $id = $request->header('doc_id');
        $body = [];
        $title = null;
        $url = null;
        if($id != false && $id != 'null'){
            $doc = $this->getDoc($id);
            $title = $doc['title'] ?? '请选择文档';
            $url = $doc['url'] ?? '';
            if($url){
                $body = $this->getSwaggerDoc($url);
            }
        }
        $data = [
            'menu' => $this->getMenu($body['paths'] ?? []),
            'current' => [
                'title' => $title ?? '请选择文档',
                'url' => $url,
            ]
        ];
        return $data;
    }

    /**
     * 获取菜单
     * @param $paths
     * @return array
     */
    protected function getMenu($paths){
        $menus = [];
        $tags = [];
        foreach ($paths as $k => $v){
            foreach($v as $vv){
                $name = $vv['tags'][0] ?? '默认';
                $tags[$name] = [
                    "id" => md5($name),
                    "name" => $name,
                    "pid" => "0",
                    "icon" => "mdi mdi-file-word",
                    "target" => "_self",
                    "url" => ''
                ];
            }
        }

        $number = 0;
        foreach($tags as $k => $v){
            $number ++;
            $v['id'] = (string)$number;
            $menus[] = $v;
            foreach ($paths as $kk => $vv){
                foreach ($vv as $kkk => $vvv){
                    if(($vvv['tags'][0] ?? '默认') == $v['name']){
                        $menus[] = [
                            "id" => $kk.':'.$kkk,
                            "name" => $vvv['summary'],
                            "pid" => $v['id'],
                            "icon" => "mdi mdi-file-word",
                            "target" => "_self",
                            "url" => 'doc/http.html?version=v1.0.0&uri='.$kk.'&method='.$kkk
                        ];
                    }

                }
            }
        }
        return $menus;
    }


    /**
     * 探查
     * @param Request $request
     * @return void
     */
    public function explore(Request &$request){
        $url = $request->param('url');
        $parse = parse_url($url);
        if(count($parse) < 3) App::error()->setError($url.'不是一个有效的url', Error::FORBIDDEN);

        $resp = App::curl()->request($url, 'GET');
        $localSwagger = App::runtimePath().DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'swagger-'.md5($url).'.json';
        file_put_contents($localSwagger, $resp['body']);
        $data = json_decode($resp['body'], true);
        if(!$data) App::error()->setError($url.'的内容不是一个有效的json格式文件', Error::FORBIDDEN);
        $row = $this->db()->table('doclist')->where(['url' => $url])->find();
        if(!$row){
            // 插入
            $row = [
                'id' => $url,
                'title' => $data['info']['title'],
                'version' => $data['info']['version'],
                'url' => $url,
                'config' => json_encode($data['components']['parameters'] ?? [], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE),
                'server_url' => $data['servers'][0]['url'] ?? '',
                'create_time' => date('Y-m-d H:i:s')
            ];
            $this->db()->table('doclist')->insert($row);
        }else{
            // 更新
            $config = [];
            if($row['config']) $config = json_decode($row['config'], true);

            foreach($data['components']['parameters'] ?? [] as $k => $v){
                $data['components']['parameters'][$k]['default'] = $config[$k]['default'] ?? '';
            }
            $this->db()->table('doclist')->where(['id' => $row['id']])->update([
                'title' => $data['info']['title'],
                'version' => $data['info']['version'],
                'config' => json_encode($data['components']['parameters'] ?? [], JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)
            ]);
        }
        return $row;
    }

    /**
     * 获取接口详情
     * @param Request $request
     * @return void
     */
    public function detail(Request &$request){
        $id = $request->header('doc_id');
        $version = $request->param('version');
        $uri = $request->param('uri');
        $type = $request->param('type');
        $method = $request->param('method');
        $row = $this->getDoc($id);
        //$resp = App::curl()->request($row['url'], 'GET');
        $data = $this->getSwaggerDoc($row['url']);
        if(!$data) App::error()->setError($row['url'].'的内容不是一个有效的json格式文件', Error::FORBIDDEN);
        $doc = $data['paths'][$uri][$method] ?? App::error()->setError('paths下'.$uri.'节点下不存在'.$method);
        $doc['title'] = $doc['summary'] ?? '未知';
        $doc['server_url'] = $row['server_url'].((strpos($uri, '/') !== 0) ? '/'.$uri : $uri);
        $requestBody = $this->getRequestParam($doc['parameters'] ?? [], $data, $doc['server_url'], $row['config']);
        $doc['server_method'] = $method;
        $requestBody = array_merge($requestBody, $this->getRequestBodyParam($doc['requestBody']['content'] ?? [], $data, $doc['server_url'], $row['config']));
        return [
            'detail' => $doc,
            'header' => $this->getRequestHeaderParam($doc['parameters'] ?? [], $data, $row['config']),
            'body' => $requestBody,
            'resp' => $this->getRespParam($doc),
            'example' => $this->getExample($type, $version, $uri, $method, $id),
            'versions' => ['v1.0.0'],
        ];
    }

    protected function getRequestHeaderParam(array $parameters, array $data, array $config){
        $header = [];
        foreach($parameters as $v){
            if(isset($v['$ref'])){
                $ref = str_replace('#/', '', $v['$ref']);
                $param = $data;
                foreach(explode('/', $ref) as $vv){
                    $param = isset($param[$vv]) ? $param[$vv] : [];
                }
                if($param && isset($param['in']) && $param['in'] == 'header'){
                    $conf = $config[$param['name']] ?? [];
                    $default = '';
                    if(isset($conf['in']) && $conf['in'] == 'header') $default = $conf['default'] ?? '';
                    $header[] = [
                        'name' => $param['name'],
                        'type' => $param['schema']['type'] ?? 'string',
                        'required' => $param['required'] ?? false,
                        'default' => $default,
                        'description' => $param['description'] ?? $param['name'],
                    ];
                }
            }else{
                if(isset($v['in']) && $v['in'] == 'header'){
                    $conf = $config[$v['name']] ?? [];
                    $default = '';
                    if(isset($conf['in']) && $conf['in'] == 'header') $default = $conf['default'] ?? '';
                    $header[] = [
                        'name' => $v['name'],
                        'type' => $v['schema']['type'] ?? 'string',
                        'required' => $v['required'] ?? false,
                        'default' => $default,
                        'description' => $v['description'] ?? $v['name'],
                    ];
                }
            }
        }
        return $header;
    }


    /**
     * @param array $parameters
     * @param array $data
     * @param strin $url
     * @return array
     */
    protected function getRequestParam(array $parameters, array $data, string &$url, mixed &$config){
        if($config) $config = json_decode($config, true);
        if($config == false) $config = [];

        $body = [];
        $path = '';
        $paramType = ['query', 'body', 'form'];
        foreach($parameters as $v){
            if(isset($v['$ref'])){
                $ref = str_replace('#/', '', $v['$ref']);
                $param = $data;
                foreach(explode('/', $ref) as $vv){
                    $param = isset($param[$vv]) ? $param[$vv] : [];
                }
                if($param && isset($param['in']) && in_array($param['in'], $paramType)){
                    $conf = $config[$param['name']] ?? [];
                    $default = '';
                    if(isset($conf['in']) && $conf['in'] != 'header') $default = $conf['default'];
                    $body[] = [
                        'name' => $param['name'],
                        'type' => $param['schema']['type'] ?? 'string',
                        'required' => $param['required'] ?? false,
                        'default' => $default,
                        'description' => $param['description'] ?? $param['name'],
                    ];
                }else if($param && isset($param['in']) && $param['in'] == 'path'){
                    $path .= $param['name'].'=-&';
                }
            }else{
                if(isset($v['in']) && in_array($v['in'], $paramType)){
                    $conf = $config[$v['name']] ?? [];
                    $default = '';
                    if(isset($conf['in']) && $conf['in'] != 'header') $default = $conf['default'];
                    $body[] = [
                        'name' => $v['name'],
                        'type' => $v['schema']['type'] ?? 'string',
                        'required' => $v['required'] ?? false,
                        'default' => $default,
                        'description' => $v['description'] ?? $v['name'],
                    ];
                }else if(isset($v['in']) && $v['in'] == 'path'){
                    $path .= $v['name'].'=-&';
                }
            }
        }
        unset($data['openapi'], $data['info'], $data['servers'], $data['paths']);

        if($path){
            if(strpos($url, '?') === false) $url .= '?';
            $url .= rtrim($path, '&');
        }
        return $body;
        /**
         * paramType ：查询参数类型，这里有几种形式：
            类型 作用
            path 以地址的形式提交数据 以地址栏
            query 直接跟参数完成自动映射赋值 a=1&b2
            body 以流的形式提交 仅支持POST
            header 参数在request headers 里边提交
            form 以form表单的形式提交 仅支持POST
         */
    }


    public function getRequestBodyParam(array $parameters, array $data, string &$url, array $config){
        $data = [];
        if($parameters){
            $parameters = current($parameters);
            if(isset($parameters['schema']['properties'])){
                foreach($parameters['schema']['properties'] as $k => $v){
                    $row = [
                        'name' => $k,
                        'type' => $v['type'],
                        'description' => $v['description'] ?? '',
                        'children' => []
                    ];
                    if(isset($v['properties'])){
                        $this->getRespParamChildren($row, $v['properties']);
                    }elseif (isset($v['items']['properties'])){
                        $this->getRespParamChildren($row, $v['items']['properties']);
                    }

                    $data[] = $row;
                }
            }
        }
        return $data;
    }

    /**
     * 获取子级菜单
     * @param array $respParam
     * @return void
     */
    protected function getRespParamChildren(array &$respParam, array $data){
        $number = 0;
        foreach($data as $k => $v){
            $respParam['children'][$number] = [
                'name' => $k,
                'type' => $v['type'],
                'description' => $v['description'] ?? '',
                'children' => []
            ];
            if(isset($v['properties'])){
                $this->getRespParamChildren($respParam['children'][$number], $v['properties']);
            }else if(isset($v['items']['properties'])){
                $this->getRespParamChildren($respParam['children'][$number], $v['items']['properties']);
            }
            $number++;
        }
    }
    protected function getRespParam(array $doc){
        try {
            $content = current($doc['responses'][200]['content'])['schema']['properties'];
        }catch (\Throwable $e){
            return [];
        }
        $data = [];
        foreach($content as $k => $v){
            $row = [
                'name' => $k,
                'type' => $v['type'],
                'description' => $v['description'] ?? '',
                'children' => []
            ];
            if(isset($v['properties'])){
                $this->getRespParamChildren($row, $v['properties']);
            }elseif (isset($v['items']['properties'])){
                $this->getRespParamChildren($row, $v['items']['properties']);
            }

            $data[] = $row;
        }
        return $data;
    }

    /**
     * 获取文档
     * @param string $id
     * @return mixed|null
     * @throws \Exception
     */
    protected function getDoc(string $id){
        $row = $this->db()->table('doclist')->where(['id' => $id])->find();
        if($row == false) App::error()->setError('ID:'.$id.'的文档不存在', Error::NOT_FOUND);
        return $row;
    }
    /**
     * 获取debug数据
     * @param Request $request
     * @return void
     */
    public function debug(Request &$request){
        $detail = $this->detail($request);
        // 给到默认值

        return $detail;
    }

    /**
     * 记录示例的值
     * @param Request $request
     * @return void
     */
    public function example(Request &$request){
        $id = $request->header('doc_id');
        $uri = $request->param('uri');
        $method = $request->param('method');
        $type = $request->param('type');
        $version = $request->param('version');
        $body = $request->param('body');
        $row = $this->db()->table('apidocs')->where([
            'type' => $type,
            'version' => $version,
            'uri' => $uri,
            'method' => strtoupper($method),
            'doc'=> $id
        ])->find();
        // 更新
        if($row){
            $this->db()->table('apidocs')->where(['id' => $row['id']])->update(['success' => json_encode($body, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE)]);
        }else{
            $this->db()->table('apidocs')->insert([
                'type' => $type,
                'version' => $version,
                'uri' => $uri,
                'doc'=> $id,
                'method' => strtoupper($method),
                'success' => json_encode($body, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /**
     * 获取示例值
     * @param $type
     * @param $version
     * @param $uri
     * @param $method
     * @param $id
     * @return mixed|null
     * @throws \Exception
     */
    protected function getExample($type, $version, $uri, $method, $id){
        $success =  $this->db()->table('apidocs')->where([
            'type' => $type,
            'version' => $version,
            'uri' => $uri,
            'method' => strtoupper($method),
            'doc'=> $id
        ])->order('id desc')->value('success');
        if($success){
            return [
                'success' => json_decode($success, true),
                'error' => []
            ];
        }
        return [];
    }

    public function samplecode(Request &$request){
        $language = $request->param('language');
        $detail = $this->detail($request);
        return [
            'sample_code' => SampleCode::$language($detail, $request)
        ];
    }
}


class SampleCode{

    protected static function jqueryRequestParam(string $key, string $type, &$bodyCode, $data, $level = 1){
        $levelHtml = '';
        for($i = 0; $i < $level; $i++){
            $levelHtml .= '        ';
        }
        $startSymbol = '{';
        $endSymbol = '}';
        if($type == 'array'){
            $startSymbol = '['.PHP_EOL.$levelHtml.'  '.'{';
            $endSymbol = $levelHtml.'  }'.PHP_EOL.$levelHtml.']';
        }
        $bodyCode .= $levelHtml.'"'.$key.'": '.$startSymbol.PHP_EOL;
        foreach($data as $v){
            if(isset($v['children']) && $v['children']){
                self::jqueryRequestParam($v['name'], $v['type'], $bodyCode, $v['children'], $level + 1);
                //$bodyCode .= '"",';//$v['children'].' //'.$v['description'].PHP_EOL;
            }else{
                $bodyCode .= '  '.$levelHtml.'"'.$v['name'].'": ' .(is_int($v['default'] ?? '') ? $v['default'] : ("\"".($v['default'] ?? '')."\",")).' //'.$v['description'].PHP_EOL;
            }
        }
        $bodyCode .= $endSymbol.PHP_EOL;
    }

    public static function vue3($detail, &$request){
        $headerCode = '{'.PHP_EOL;

        foreach($detail['header'] as $v){
            $headerCode .= '        "'.$v['name'].'": '. (is_int($v['default'] ?? '') ? $v['default'] : ("\"".($v['default'] ?? '')."\",")).' //'.$v['description'].PHP_EOL;
        }
        $headerCode .= '    }';

        $bodyCode = '{'.PHP_EOL;
        foreach($detail['body'] as $v){
            if(isset($v['children']) && $v['children']){
                self::jqueryRequestParam($v['name'], $v['type'], $bodyCode, $v['children']);
            }else{
                $bodyCode .= '        "'.$v['name'].'": '.(is_int($v['default'] ?? '') ? $v['default'] : ("\"".($v['default'] ?? '')."\",")).' //'.$v['description'].PHP_EOL;
            }

        }
        $bodyCode .= '    }';
        $uri = $request->param('uri');
        $method = $request->param('method');
        $type = $request->param('type');
        $version = $request->param('version');
        $code = "// vuejs示例代码 //
axios.request({
    // 请求的接口地址
    url: \"".$detail['detail']['server_url']."\",
    //请求方法
    method: \"".strtoupper($method)."\",
    //超时时间设置，单位毫秒
    timeout: 30000,
    //请求头参数
    headers: ".$headerCode.",
    // 与请求一起发送的 URL 参数
    params: {},
    // 请求主体被发送的数据
    data: ".$bodyCode.",
}).then(res => {
    // 请求成功处理
    alert(JSON.stringify(res));
}).catch(function (error) {
    // 请求失败处理
    alert(error.message);
});".PHP_EOL;
        return $code;
    }

    public static function jquery($detail, &$request){
        $headerCode = '{'.PHP_EOL;

        foreach($detail['header'] as $v){
            $headerCode .= '        "'.$v['name'].'": '. (is_int($v['default'] ?? '') ? $v['default'] : ("\"".($v['default'] ?? '')."\",")).' //'.$v['description'].PHP_EOL;
        }
        $headerCode .= '    }';

        $bodyCode = '{'.PHP_EOL;
        foreach($detail['body'] as $v){
            if(isset($v['children']) && $v['children']){
                self::jqueryRequestParam($v['name'], $v['type'], $bodyCode, $v['children']);
            }else{
                $bodyCode .= '        "'.$v['name'].'": '.(is_int($v['default'] ?? '') ? $v['default'] : ("\"".($v['default'] ?? '')."\",")).' //'.$v['description'].PHP_EOL;
            }

        }
        $bodyCode .= '    }';
        $uri = $request->param('uri');
        $method = $request->param('method');
        $type = $request->param('type');
        $version = $request->param('version');
        $code = "// jquery示例代码 //
var request = $.ajax({
    //请求的接口地址
    url: '".$detail['detail']['server_url']."',
    //请求方法
    method: '".strtoupper($method)."',
     //超时时间设置，单位毫秒
    timeout: 30000,
    //请求头参数
    headers: ".$headerCode.",
    //请求的数据
    data: ".$bodyCode.",
});
// 请求成功
request.done(function(data) {
    alert(JSON.stringify(data));
});
// 请求异常
request.fail(function(jqXHR) {
    alert(\"接口出错\\n\"+jqXHR.statusText);
});".PHP_EOL;
        return $code;
    }

    public static function php($detail, &$request){
        $method = $request->param('method');
        $headerStr = '    "Content-Type:application/json", '.PHP_EOL;
        foreach($detail['header'] as $v){
            $headerStr .= '    "'.$v['name'].':'.$v['default'].'", //'.$v['description'].PHP_EOL;
        }
        $headerStr = rtrim($headerStr, PHP_EOL);
        $bodyCode = '{'.PHP_EOL;
        foreach($detail['body'] as $v){
            if(isset($v['children']) && $v['children']){
                self::jqueryRequestParam($v['name'], $v['type'], $bodyCode, $v['children']);
            }else{
                $bodyCode .= '        "'.$v['name'].'": '.(is_int($v['default'] ?? '') ? $v['default'] : ("\"".($v['default'] ?? '')."\",")).' //'.$v['description'].PHP_EOL;
            }
        }
        $bodyCode .= '    }';
        $code = "<?php\n\$ch = curl_init();
curl_setopt(\$ch, CURLOPT_URL, '".$detail['detail']['server_url']."');
curl_setopt(\$ch, CURLOPT_CUSTOMREQUEST, '".strtoupper($method)."'); //设置请求方式
curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST,false);

// 请求头 
curl_setopt(\$ch, CURLOPT_HTTPHEADER, [
$headerStr
]);

// 请求参数
curl_setopt(\$ch, CURLOPT_POST, 1);
curl_setopt(\$ch, CURLOPT_POSTFIELDS, '".$bodyCode."');

// 解析方式IPV4
curl_setopt(\$ch, CURLOPT_IPRESOLVE, 1);

\$output = curl_exec(\$ch);
\$code = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);

echo '响应状态:'.\$code.',结果:'.\$output;".PHP_EOL;
        return $code;
    }

    public static function java($detail, &$request){
        return '';
    }

    public static function go($detail, &$request){
        return '';
    }

    public static function python($detail, &$request){
        return '';
    }

    public function curl($detail, &$request){
        return '';
    }

    /**
     * 获取c语言的示例代码
     * @param $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        return self::get($name, $arguments);
    }


}

// 菜单重复bug , 一键复制代码， 在线运行 VUE示例, json文件缓存到本地 每次探查的时候下载一次