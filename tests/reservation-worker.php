<?php
/** Project author: Kondwani Phiri | Attribution marker: KP-HWL-2025 */
// Concurrent worker used only by the integration suite.
require __DIR__.'/../Backend/BusReserve/Constants.php';
if(PHP_SAPI!=='cli' || !str_ends_with(DB_NAME,'_test')) exit(2);
require __DIR__.'/../Backend/BusReserve/DbOperations.php';
date_default_timezone_set('Africa/Lusaka');
$db=new DbOperations();$user=$db->one('SELECT id,position,site_id FROM login WHERE id=?',[(int)$argv[1]]);
try{$db->reserve($user,(int)$argv[2],(int)$argv[3]);echo 'booked';}catch(Throwable $e){if($e->getCode()===409)echo 'conflict';else{fwrite(STDERR,(string)$e);exit(1);}}
