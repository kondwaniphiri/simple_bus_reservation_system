<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
require __DIR__.'/../Backend/BusReserve/Environment.php';
$dir=sys_get_temp_dir().'/hwl-env-'.bin2hex(random_bytes(6));mkdir($dir);$path=$dir.'/settings.env';
try{
 $text="\xEF\xBB\xBF# Configuration\nNAME=\"value with # and =\"\nEMPTY=\"\"\nSINGLE='literal # value'\nESCAPED=\"line\\nvalue\"\n";
 file_put_contents($path,$text);$v=Environment::readFile($path);
 if($v['NAME']!=='value with # and =' || $v['EMPTY']!=='' || $v['SINGLE']!=='literal # value' || $v['ESCAPED']!=="line\nvalue")throw new RuntimeException('Quoted environment parsing failed');
 putenv('HWL_ENV_PROBE=system');if(Environment::value('HWL_ENV_PROBE','file')!=='system')throw new RuntimeException('Environment precedence failed');putenv('HWL_ENV_PROBE');
 file_put_contents($path,'INVALID LINE');try{Environment::readFile($path);throw new LogicException('Invalid environment line accepted');}catch(RuntimeException $expected){}
 require __DIR__.'/../Backend/BusReserve/Connection.php';$db=(new Connection())->connect();if(!$db->query('SELECT 1'))throw new RuntimeException('Database configuration failed');
 echo "PASS: environment quoting, BOM handling, empty values, validation, precedence and database connection.\n";
}finally{if(is_file($path))unlink($path);rmdir($dir);}
