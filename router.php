<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Local development router: php -S 127.0.0.1:8080 router.php
$path=rawurldecode(parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)??'/');
if(str_contains($path,"\0") || str_contains($path,'\\') || preg_match('~(?:^|/)\.|(?:^|/)(?:tests|Backend/bus_ticket\.sql)(?:/|$)|\.(?:sql|log|md|ini|env)$~i',$path)) {
 http_response_code(404);exit;
}
$root=realpath(__DIR__);$file=realpath(__DIR__.$path);
if($file && strncmp($file,$root.DIRECTORY_SEPARATOR,strlen($root)+1)!==0 && $file!==$root){http_response_code(404);exit;}
if($path==='/'){require __DIR__.'/index.html';return true;}
if(!$file || is_dir($file)){http_response_code(404);echo 'Not found';return true;}
return false;
