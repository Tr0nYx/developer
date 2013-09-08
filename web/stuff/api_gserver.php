<?php
/**
 * File: api_gserver.php.
 * Author: Ulrich Block
 * Date: 05.08.12
 * Time: 18:27
 * Contact: <ulrich.block@easy-wi.com>
 *
 * This file is part of Easy-WI.
 *
 * Easy-WI is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Easy-WI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Easy-WI.  If not, see <http://www.gnu.org/licenses/>.
 *
 * Diese Datei ist Teil von Easy-WI.
 *
 * Easy-WI ist Freie Software: Sie koennen es unter den Bedingungen
 * der GNU General Public License, wie von der Free Software Foundation,
 * Version 3 der Lizenz oder (nach Ihrer Wahl) jeder spaeteren
 * veroeffentlichten Version, weiterverbreiten und/oder modifizieren.
 *
 * Easy-WI wird in der Hoffnung, dass es nuetzlich sein wird, aber
 * OHNE JEDE GEWAEHELEISTUNG, bereitgestellt; sogar ohne die implizite
 * Gewaehrleistung der MARKTFAEHIGKEIT oder EIGNUNG FUER EINEN BESTIMMTEN ZWECK.
 * Siehe die GNU General Public License fuer weitere Details.
 *
 * Sie sollten eine Kopie der GNU General Public License zusammen mit diesem
 * Programm erhalten haben. Wenn nicht, siehe <http://www.gnu.org/licenses/>.
 */

$minimumArray=array('action','identify_server_by','server_local_id','server_external_id');
$editArray=array('active','private','slots','shorten','identify_user_by','user_localid','user_externalid','username');
foreach ($minimumArray as $key) {
    if (!array_key_exists($key,$data)) {
        $success['false'][]='Data key does not exist: '.$key;
    }
}
if (array_key_exists('action',$data) and $data['action']!='gs') {
    foreach ($editArray as $key) {
        if (!array_key_exists($key,$data)) {
            $success['false'][]='Data key does not exist: '.$key;
        }
    }
}
$aesfilecvar=getconfigcvars(EASYWIDIR."/stuff/keyphrasefile.php");
$aeskey=$aesfilecvar['aeskey'];
$active='';
$private='';
$shorten='';
$slots='';
$identifyUserBy='';
$localUserID='';
$externalUserID='';
$username='';
$identifyServerBy='';
$localServerID='';
$externalServerID='';
$taskset='';
$eacallowed='';
$brandname='';
$tvenable='';
$pallowed='';
$port='';
$port2='';
$port3='';
$port4='';
$port5='';
$minram='';
$maxram='';
$hostID='';
$cores='';
$customID=0;
$hostExternalID='';
$initialpassword='';
$installGames='A';
$autoRestart='';
if (!isset($success['false']) and array_value_exists('action','add',$data) and 1>$licenceDetails['lG']) {
    $success['false'][]='licence limit reached';
} else if (!isset($success['false']) and array_value_exists('action','add',$data) and $licenceDetails['lG']>0) {
    if (dataExist('identify_user_by',$data) and isid($data['slots'],11)) {
        if (is_array($data['shorten']) or is_object($data['shorten'])) {
            $shorten=$data['shorten'];
        } else {
            $shorten=array($data['shorten']);
        }
        if (count($shorten)==0) {
            $success['false'][]='No gameshorten(s) has been send';
        } else {
            $active=active_check($data['active']);
            $private=active_check($data['private']);
            $slots=isid($data['slots'],11);
            $identifyUserBy=$data['identify_user_by'];
            $localUserID=isid($data['user_localid'],21);
            $externalUserID=$data['user_externalid'];
            $username=$data['username'];
            $identifyServerBy=$data['identify_server_by'];
            $localServerID=isid($data['server_local_id'],19);
            $externalServerID=$data['server_external_id'];
            $from=array('user_localid'=>'id','username'=>'cname','user_externalid'=>'externalID','email'=>'mail');
            $query=$sql->prepare("SELECT `id`,`cname` FROM `userdata` WHERE `".$from[$data['identify_user_by']]."`=? AND `resellerid`=?");
            $query->execute(array($data[$data['identify_user_by']],$resellerID));
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $localUserLookupID=$row['id'];
            }
            if (!isset($localUserLookupID)) {
                $success['false'][]='user does not exist';
            }
            $query=$sql->prepare("SELECT * FROM `servertypes` WHERE `shorten`=? AND `resellerid`=? LIMIT 1");
            $typeIDs=array();
            $typeIDList=array();
            $shortenToID=array();
            foreach ($shorten as $singleShorten) {
                $query->execute(array($singleShorten,$resellerID));
                foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    if (!isset($portMax) or $row['portMax']>$portMax or (isset($data['primary']) and gamestring($data['primary']) and $row['portMax']<=$portMax and $singleShorten==$data['primary'])) {
                        $portStep=$row['portStep'];
                        $portMax=$row['portMax'];
                        $port=$row['portOne'];
                        $port2=$row['portTwo'];
                        $port3=$row['portThree'];
                        $port4=$row['portFour'];
                        $port5=$row['portFive'];
                    }
                    $typeIDList[]=$row['id'];
                    $shortenToID[$row['id']]=$singleShorten;
                    $typeIDs[$singleShorten]=array('id'=>$row['id'],'map'=>$row['map'],'mapGroup'=>$row['mapGroup'],'tic'=>$row['tic'],'cmd'=>$row['cmd'],'gamemod'=>$row['gamemod'],'gamemod2'=>$row['gamemod2']);
                }
                if (!isset($typeIDs[$singleShorten])) {
                    $success['false'][]='image with the shorten '.$singleShorten.' does not exists';
                }
            }
            if (!isset($success['false']) and !in_array($externalServerID,$bad)) {
                $query=$sql->prepare("SELECT COUNT(`id`) AS `amount` FROM `gsswitch` WHERE `externalID`=? LIMIT 1");
                $query->execute(array($externalServerID));
                if ($query->fetchColumn()>0) {
                    $success['false'][]='server with external ID already exists';
                }
            }
            if (!isset($success['false'])) {
                $masterServerCount=count($typeIDList);
                if ($masterServerCount==1) {
                    $implodedQuery='m.`servertypeid`='.$typeIDList[0];
                } else {
                    $implodedQuery='(m.`servertypeid`='.implode(' OR m.`servertypeid`=',$typeIDList).')';
                }
                if (isset($data['master_server_id']) and isid($data['master_server_id'],19)) {
                    $query=$sql->prepare("SELECT r.`id`,r.`externalID`,r.`ip`,r.`altips`,r.`maxslots`,r.`maxserver`,r.`active` AS `hostactive`,r.`resellerid` AS `resellerid`,(r.`maxserver`-(SELECT COUNT(`id`) FROM gsswitch g WHERE g.`rootID`=r.`id` )) AS `freeserver`,(r.`maxslots`-(SELECT SUM(g.`slots`) FROM gsswitch g WHERE g.`rootID`=r.`id`)) AS `leftslots`,(SELECT COUNT(m.`id`) FROM `rservermasterg`m WHERE m.`serverid`=r.`id` AND $implodedQuery) `mastercount` FROM `rserverdata` r GROUP BY r.`id` HAVING (r.`id`=? AND `hostactive`='Y' AND r.`resellerid`=? AND (`freeserver`>0 OR `freeserver` IS NULL) AND (`leftslots`>? OR `leftslots` IS NULL) AND `mastercount`=?) ORDER BY `freeserver` DESC LIMIT 1");
                    $query->execute(array($data['master_server_id'],$resellerID,$slots,$masterServerCount));
                } else if (isset($data['master_server_external_id']) and wpreg_check($data['master_server_external_id'],255)) {
                    $query=$sql->prepare("SELECT r.`id`,r.`externalID`,r.`ip`,r.`altips`,r.`maxslots`,r.`maxserver`,r.`active` AS `hostactive`,r.`resellerid` AS `resellerid`,(r.`maxserver`-(SELECT COUNT(`id`) FROM gsswitch g WHERE g.`rootID`=r.`id` )) AS `freeserver`,(r.`maxslots`-(SELECT SUM(g.`slots`) FROM gsswitch g WHERE g.`rootID`=r.`id`)) AS `leftslots`,(SELECT COUNT(m.`id`) FROM `rservermasterg`m WHERE m.`serverid`=r.`id` AND $implodedQuery) `mastercount` FROM `rserverdata` r GROUP BY r.`id` HAVING (r.`externalID`=? AND `hostactive`='Y' AND r.`resellerid`=? AND (`freeserver`>0 OR `freeserver` IS NULL) AND (`leftslots`>? OR `leftslots` IS NULL) AND `mastercount`=?) ORDER BY `freeserver` DESC LIMIT 1");
                    $query->execute(array($data['master_server_external_id'],$resellerID,$slots,$masterServerCount));
                } else {
                    $query=$sql->prepare("SELECT r.`id`,r.`externalID`,r.`ip`,r.`altips`,r.`maxslots`,r.`maxserver`,r.`active` AS `hostactive`,r.`resellerid` AS `resellerid`,(r.`maxserver`-(SELECT COUNT(`id`) FROM gsswitch g WHERE g.`rootID`=r.`id` )) AS `freeserver`,(r.`maxslots`-(SELECT SUM(g.`slots`) FROM gsswitch g WHERE g.`rootID`=r.`id`)) AS `leftslots`,(SELECT COUNT(m.`id`) FROM `rservermasterg`m WHERE m.`serverid`=r.`id` AND $implodedQuery) `mastercount` FROM `rserverdata` r GROUP BY r.`id` HAVING (`hostactive`='Y' AND r.`resellerid`=? AND (`freeserver`>0 OR `freeserver` IS NULL) AND (`leftslots`>? OR `leftslots` IS NULL) AND `mastercount`=?) ORDER BY `freeserver` DESC LIMIT 1");
                    $query->execute(array($resellerID,$slots,$masterServerCount));
                }
                foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $hostID=$row['id'];
                    $hostExternalID=$row['externalID'];
                    $ips[]=$row['ip'];
                    foreach (preg_split('/\r\n/',$row['altips'],-1,PREG_SPLIT_NO_EMPTY) as $ip) {
                        $ips[]=$ip;
                    }
                }
                if (isset($ips)) {
                    $used=usedPorts($ips);
                    $ip=$used['ip'];
                    $ports=$used['ports'];
                } else if (isset($data['master_server_id']) and isid($data['master_server_id'],19)) {
                    $missing=array();
                    $query=$sql->prepare("SELECT r.`id` FROM `rserverdata` r LEFT JOIN `rservermasterg` m ON m.`serverid`=r.`id` WHERE r.`id`=? AND r.`active`='Y' AND r.`resellerid`=? AND m.`servertypeid`=? LIMIT 1");
                    foreach ($typeIDList as $ID) {
                        $query->execute(array($data['master_server_id'],$resellerID,$ID));
                        if ($query->rowCount()==0) {
                            $missing[]=$shortenToID[$ID];
                        }
                    }
                } else if (isset($data['master_server_external_id']) and wpreg_check($data['master_server_external_id'],255)) {
                    $missing=array();
                    $query=$sql->prepare("SELECT r.`id` FROM `rserverdata` r LEFT JOIN `rservermasterg` m ON m.`serverid`=r.`id` WHERE r.`externalID`=? AND r.`active`='Y' AND r.`resellerid`=? AND m.`servertypeid`=? LIMIT 1");
                    foreach ($typeIDList as $ID) {
                        $query->execute(array($data['master_server_external_id'],$resellerID,$ID));
                        if ($query->rowCount()==0) {
                            $missing[]=$shortenToID[$ID];
                        }
                    }
                } else {
                    $missing=$shorten;
                }
                if (isset($missing) and count($missing)>0) {
                    $success['false'][]='No free host with shorten(s): '.implode(', ',$missing);
                }
            }
            if (!isset($success['false']) and isip($ip,'ip4')) {
                if ($portMax==1) {
                    if (isset($data['port']) and checkPorts(array($data['port']),$ports)===true) {
                        $port=$data['port'];
                    }
                    while (in_array($port,$ports)) {
                        $port+=$portStep;
                    }
                    $port2='';
                    $port3='';
                    $port4='';
                    $port5='';
                } else if ($portMax==2) {
                    if (isset($data['port'],$data['port2']) and checkPorts(array($data['port'],$data['port2']),$ports)===true) {
                        $port=$data['port'];
                        $port2=$data['port2'];
                    }
                    while (in_array($port,$ports) or in_array($port2,$ports)) {
                        $port+=$portStep;
                        $port2+=$portStep;
                    }
                    $port3='';
                    $port4='';
                    $port5='';
                } else if ($portMax==3) {
                    if (isset($data['port'],$data['port2'],$data['port3']) and checkPorts(array($data['port'],$data['port2'],$data['port3']),$ports)===true) {
                        $port=$data['port'];
                        $port2=$data['port2'];
                        $port3=$data['port3'];
                    }
                    while (in_array($port,$ports) or in_array($port2,$ports) or in_array($port3,$ports)) {
                        $port+=$portStep;
                        $port2+=$portStep;
                        $port3+=$portStep;
                    }
                    $port4='';
                    $port5='';
                } else if ($portMax==4) {
                    if (isset($data['port'],$data['port2'],$data['port3'],$data['port4']) and checkPorts(array($data['port'],$data['port2'],$data['port3'],$data['port4']),$ports)===true) {
                        $port=$data['port'];
                        $port2=$data['port2'];
                        $port3=$data['port3'];
                        $port4=$data['port4'];
                    }
                    while (in_array($port,$ports) or in_array($port2,$ports) or in_array($port3,$ports) or in_array($port4,$ports)) {
                        $port+=$portStep;
                        $port2+=$portStep;
                        $port3+=$portStep;
                        $port4+=$portStep;
                    }
                    $port5='';
                } else {
                    if (isset($data['port'],$data['port2'],$data['port3'],$data['port4'],$data['port5']) and checkPorts(array($data['port'],$data['port2'],$data['port3'],$data['port4'],$data['port5']),$ports)===true) {
                        $port=$data['port'];
                        $port2=$data['port2'];
                        $port3=$data['port3'];
                        $port4=$data['port4'];
                        $port5=$data['port5'];
                    }
                    while (in_array($port,$ports) or in_array($port2,$ports) or in_array($port3,$ports) or in_array($port4,$ports) or in_array($port5,$ports)) {
                        $port+=$portStep;
                        $port2+=$portStep;
                        $port3+=$portStep;
                        $port4+=$portStep;
                        $port5+=$portStep;
                    }
                }
                $initialpassword=passwordgenerate(10);
                $taskset=(isset($data['taskset']) and active_check($data['taskset'])) ? $data['taskset'] : 'N';
                $eacallowed=(isset($data['eacallowed']) and active_check($data['eacallowed'])) ? $data['eacallowed'] : 'N';
                $brandname=(isset($data['brandname']) and active_check($data['brandname'])) ? $data['brandname'] : 'N';
                $tvenable=(isset($data['tvenable']) and active_check($data['tvenable'])) ? $data['tvenable'] : 'N';
                $pallowed=(isset($data['pallowed']) and active_check($data['pallowed'])) ? $data['pallowed'] : 'N';
                $autoRestart=(isset($data['autoRestart']) and active_check($data['autoRestart'])) ? $data['autoRestart'] : 'Y';
                $minram=(isset($data['minram']) and isid($data['minram'],10)) ? $data['minram'] : '';
                $maxram=(isset($data['maxram']) and isid($data['maxram'],10)) ? $data['maxram'] : '';
                $cores=(isset($data['cores']) and cores($data['cores'])) ? $data['cores'] : '';
                if (isset($data['installGames']) and wpreg_check($data['installGames'],1)) {
                    $installGames=$data['installGames'];
                }
                $json=json_encode(array('installGames'=>$installGames));
                $query=$sql->prepare("INSERT INTO `gsswitch` (`active`,`taskset`,`cores`,`userid`,`pallowed`,`eacallowed`,`serverip`,`rootID`,`tvenable`,`port`,`port2`,`port3`,`port4`,`port5`,`minram`,`maxram`,`slots`,`war`,`brandname`,`autoRestart`,`ftppassword`,`resellerid`,`externalID`,`serverid`,`stopped`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,AES_ENCRYPT(?,?),?,?,1,'Y')");
                $query->execute(array($active,$taskset,$cores,$localUserLookupID,$pallowed,$eacallowed,$ip,$hostID,$tvenable,$port,$port2,$port3,$port4,$port5,$minram,$maxram,$slots,$private,$brandname,$autoRestart,$initialpassword,$aeskey,$resellerID,$externalServerID));
                $localServerID=$sql->lastInsertId();
                customColumns('G',$localServerID,'save',$data);
                $customID=$localServerID;
                if (isid($localServerID,19)) {
                    $query=$sql->prepare("INSERT INTO `serverlist` (`servertype`,`switchID`,`map`,`mapGroup`,`cmd`,`tic`,`gamemod`,`gamemod2`,`resellerid`) VALUES (?,?,?,?,?,?,?,?,?)");
                    foreach ($typeIDs as $shorten=>$array) {
                        $query->execute(array($array['id'],$localServerID,$array['map'],$array['mapGroup'],$array['cmd'],$array['tic'],$array['gamemod'],$array['gamemod2'],$resellerID));
                        if (!isset($lastServerID) or (isset($data['primary']) and gamestring($data['primary']) and $shorten==$data['primary'])) {
                            $lastServerID=$sql->lastInsertId();
                        }
                    }
                    if (!isset($lastServerID) or !isid($lastServerID,19) ) {
                        $query=$sql->prepare("SELECT `id` FROM `serverlist` WHERE `switchID`=? AND `resellerid`=? ORDER BY `id` DESC LIMIT 1");
                        $query->execute(array($localServerID,$resellerID));
                        $lastServerID=$query->fetchColumn();
                    }
                    $query=$sql->prepare("UPDATE `gsswitch` SET `serverid`=? WHERE `id`=? AND `resellerid`=? LIMIT 1");
                    $query->execute(array($lastServerID,$localServerID,$resellerID));
                    $query=$sql->prepare("UPDATE `jobs` SET `status`='2' WHERE `type`='gs' AND (`status` IS NULL OR `status`='1') AND `affectedID`=? and `resellerID`=?");
                    $query->execute(array($localServerID,$resellerID));
                    $query=$sql->prepare("INSERT INTO `jobs` (`api`,`type`,`hostID`,`invoicedByID`,`affectedID`,`userID`,`name`,`status`,`date`,`action`,`extraData`,`resellerid`) VALUES ('A','gs',?,?,?,?,?,NULL,NOW(),'ad',?,?)");
                    $query->execute(array($hostID,$resellerID,$localServerID,$localUserLookupID,$ip.':'.$port,$json,$resellerID));
                } else {
                    $success['false'][]='Could not write game server to database';
                }
            }
        }
    } else if (!isset($success['false'])) {
        $active=active_check($data['active']);
        $private=active_check($data['private']);
        $shorten=$data['shorten'];
        $slots=isid($data['slots'],11);
        $identifyUserBy=$data['identify_user_by'];
        $localUserID=isid($data['user_localid'],21);
        $externalUserID=$data['user_externalid'];
        $username=$data['username'];
        $identifyServerBy=$data['identify_server_by'];
        $localServerID=isid($data['server_local_id'],21);
        $externalServerID=$data['server_external_id'];
        if (!dataExist('identify_user_by',$data)) {
            $success['false'][]='Can not identify user or bad email';
        } else {
            $success['false'][]='Slot amount needs to be specified';
        }
    }
} else if (!isset($success['false']) and array_value_exists('action','mod',$data)) {
    $identifyUserBy=$data['identify_user_by'];
    $localUserID=isid($data['user_localid'],21);
    $externalUserID=$data['user_externalid'];
    $username=$data['username'];
    $identifyServerBy=$data['identify_server_by'];
    $localServerID=isid($data['server_local_id'],21);
    $externalServerID=$data['server_external_id'];
    $shorten=$data['shorten'];
    $from=array('server_local_id'=>'id','server_external_id'=>'externalID');
    $initialpassword='';
    if (dataExist('identify_server_by',$data)) {
        $query=$sql->prepare("SELECT r.`externalID`,g.`id`,g.`serverip`,g.`port`,g.`userid`,g.`active`,g.`slots`,g.`rootID`,g.`war` FROM `gsswitch` g LEFT JOIN `rserverdata` r ON g.`rootID`=r.`id` WHERE g.`".$from[$data['identify_server_by']]."`=? AND g.`resellerid`=? LIMIT 1");
        $query->execute(array($data[$data['identify_server_by']],$resellerID));
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $localID=$row['id'];
            $userID=$row['userid'];
            $hostID=$row['rootID'];
            $hostExternalID=$row['externalID'];
            $oldSlots=$row['slots'];
            $name=$row['serverip'].':'.$row['port'];
            $usedPorts=usedPorts(array($row['serverip']));
            $oldActive=$row['active'];
            $oldPort=$row['port'];
            $query=$sql->prepare("SELECT COUNT(`jobID`) AS `amount` FROM `jobs` WHERE `affectedID`=? AND `resellerID`=? AND `action`='dl' AND (`status` IS NULL OR `status`='1') LIMIT 1");
            $query->execute(array($localID,$resellerID));
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if($row['amount']>0) $success['false'][]='Server is marked for deletion';
            }
            $updateArray=array();
            $eventualUpdate='';
            if (isset($data['private']) and active_check($data['private'])) {
                $updateArray[]=$data['private'];
                $eventualUpdate.=',`war`=?';
                $private=$data['private'];
            }
            if (isset($data['slots']) and isid($data['slots'],11)) {
                $updateArray[]=$data['slots'];
                $eventualUpdate.=',`slots`=?';
                $slots=$data['slots'];
            }
            if (isset($data['taskset']) and active_check($data['taskset'])) {
                $updateArray[]=$data['taskset'];
                $eventualUpdate.=',`taskset`=?';
                $taskset=$data['taskset'];
            }
            if (isset($data['eacallowed']) and active_check($data['eacallowed'])) {
                $updateArray[]=$data['eacallowed'];
                $eventualUpdate.=',`eacallowed`=?';
                $eacallowed=$data['eacallowed'];
            }
            if (isset($data['brandname']) and active_check($data['brandname'])) {
                $updateArray[]=$data['brandname'];
                $eventualUpdate.=',`brandname`=?';
                $brandname=$data['brandname'];
            }
            if (isset($data['tvenable']) and active_check($data['tvenable'])) {
                $updateArray[]=$data['tvenable'];
                $eventualUpdate.=',`tvenable`=?';
                $tvenable=$data['tvenable'];
            }
            if (isset($data['pallowed']) and active_check($data['pallowed'])) {
                $updateArray[]=$data['pallowed'];
                $eventualUpdate.=',`pallowed`=?';
                $pallowed=$data['pallowed'];
            }
            if (isset($data['autoRestart']) and active_check($data['autoRestart'])) {
                $updateArray[]=$data['autoRestart'];
                $eventualUpdate.=',`autoRestart`=?';
                $autoRestart=$data['autoRestart'];
            }
            if (isset($data['minram']) and isid($data['minram'],10)) {
                $updateArray[]=$data['minram'];
                $eventualUpdate.=',`minram`=?';
                $minram=$data['minram'];
            }
            if (isset($data['maxram']) and isid($data['maxram'],10)) {
                $updateArray[]=$data['maxram'];
                $eventualUpdate.=',`maxram`=?';
                $maxram=$data['maxram'];
            }
            if (isset($data['cores']) and cores($data['cores'])) {
                $updateArray[]=$data['cores'];
                $eventualUpdate.=',`cores`=?';
                $cores=$data['cores'];
            }
            if (isset($data['port']) and port($data['port']) and !in_array($data['port'],$usedPorts)) {
                $updateArray[]=$data['port'];
                $eventualUpdate.=',`port`=?';
                $port=$data['port'];
            }
            if (isset($data['port2']) and port($data['port2']) and !in_array($data['port'],$usedPorts)) {
                $updateArray[]=$data['port2'];
                $eventualUpdate.=',`port2`=?';
                $port2=$data['port2'];
            }
            if (isset($data['port3']) and port($data['port3']) and !in_array($data['port'],$usedPorts)) {
                $updateArray[]=$data['port3'];
                $eventualUpdate.=',`port3`=?';
                $port3=$data['port3'];
            }
            if (isset($data['port4']) and port($data['port4']) and !in_array($data['port'],$usedPorts)) {
                $updateArray[]=$data['port4'];
                $eventualUpdate.=',`port4`=?';
                $port4=$data['port4'];
            }
            if (isset($data['port5']) and port($data['port5']) and !in_array($data['port'],$usedPorts)) {
                $updateArray[]=$data['port5'];
                $eventualUpdate.=',`port5`=?';
                $port5=$data['port5'];
            }
            if (isset($data['active']) and active_check($data['active'])) $active=$data['active'];
            if (count($updateArray)>0) {
                $eventualUpdate=trim($eventualUpdate,',');
                $eventualUpdate .=',';
            }
            $updateArray[]=$localID;
            $updateArray[]=$resellerID;
            $query=$sql->prepare("UPDATE `gsswitch` SET $eventualUpdate`jobPending`='Y' WHERE `id`=? AND `resellerid`=? LIMIT 1");
            $query->execute($updateArray);
            customColumns('G',$localID,'save',$data);
            $customID=$localID;
            if ((isset($active) and ($active=='Y' or $active=='N') and $active!=$oldActive) or $slots!=$oldSlots or (isset($port) and $port!=$oldPort)) {
                $update=$sql->prepare("UPDATE `jobs` SET `status`='2' WHERE `type`='gs' AND (`status` IS NULL OR `status`='1') AND `action`!='ad' AND `affectedID`=? and `resellerID`=?");
                $update->execute(array($localID,$resellerID));
                $insert=$sql->prepare("INSERT INTO `jobs` (`api`,`type`,`hostID`,`invoicedByID`,`affectedID`,`userID`,`name`,`status`,`date`,`action`,`extraData`,`resellerID`) VALUES ('A','gs',?,?,?,?,?,NULL,NOW(),'md',?,?)");
                $insert->execute(array($hostID,$resellerID,$localID,$userID,$name,json_encode(array('newActive'=>$active,'newPort'=>$port)),$resellerID));
            }
        }
        if(!isset($oldSlots)) {
            $success['false'][]='No server can be found to edit';
        }
    } else {
        $success['false'][]='No data for this method: '.$data['action'];
    }
} else if (!isset($success['false']) and array_value_exists('action','del',$data)) {
    $identifyServerBy=$data['identify_server_by'];
    $localServerID=isid($data['server_local_id'],21);
    $externalServerID=$data['server_external_id'];
    $from=array('server_local_id'=>'id','server_external_id'=>'externalID');
    if (dataExist('identify_server_by',$data)) {
        $query=$sql->prepare("SELECT r.`externalID`,g.`id`,g.`serverip`,g.`port`,g.`userid`,g.`rootID` FROM `gsswitch` g LEFT JOIN `rserverdata` r ON g.`rootID`=r.`id` WHERE g.`".$from[$data['identify_server_by']]."`=? AND g.`resellerid`=?");
        $query->execute(array($data[$data['identify_server_by']],$resellerID));
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $localID=$row['id'];
            $userID=$row['userid'];
            $name=$row['serverip'].':'.$row['port'];
            $hostID=$row['rootID'];
            $hostExternalID=$row['rootID'];
        }
        if(isset($localID) and isset($name)) {
            $query=$sql->prepare("UPDATE `gsswitch` SET `jobPending`='Y' WHERE `id`=? AND `resellerid`=? LIMIT 1");
            $query->execute(array($localID,$resellerID));
            $query=$sql->prepare("UPDATE `jobs` SET `status`='2' WHERE `type`='gs' AND (`status` IS NULL OR `status`='1') AND `affectedID`=? and `resellerID`=?");
            $query->execute(array($localID,$resellerID));
            $query=$sql->prepare("INSERT INTO `jobs` (`api`,`type`,`hostID`,`invoicedByID`,`affectedID`,`userID`,`name`,`status`,`date`,`action`,`resellerid`) VALUES ('A','gs',?,?,?,?,?,NULL,NOW(),'dl',?)");
            $query->execute(array($hostID,$resellerID,$localID,$userID,$name,$resellerID));
        } else {
            $success['false'][]='No server can be found to delete';
        }
    } else {
        $success['false'][]='No data for this method: '.$data['action'];
    }
} else if (array_value_exists('action','ls',$data)) {
    $query=$sql->prepare("SELECT r.`id`,r.`ip`,r.`altips`,r.`maxslots`,r.`maxserver`,r.`maxserver`-COUNT(g.`id`) AS `freeserver`,COUNT(g.`id`) AS `installedserver`,r.`active` AS `hostactive`,r.`resellerid` AS `resellerid`,(r.`maxslots`-SUM(g.`slots`)) AS `leftslots`,SUM(g.`slots`) AS `installedslots` FROM `rserverdata` r LEFT JOIN `gsswitch` g ON g.`rootID`=r.`id` GROUP BY r.`id` HAVING ((`freeserver` > 0 OR `freeserver` IS NULL) AND (`leftslots`>0 OR `leftslots` IS NULL) AND `hostactive`='Y' AND `resellerid`=?) ORDER BY `freeserver` DESC");
    $query->execute(array($resellerID));
    $list=true;
    if ($apiType=='xml') {
        $reply="<?xml version='1.0' encoding='UTF-8'?>
<!DOCTYPE gserver>
<gserver>";
        foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $reply .=' <server>
                <id>'.$row['id'].'</id>
                <ip>'.$row['ip'].'</ip>
                <altips>'.$row['altips'].'</altips>
                <maxslots>'.$row['maxslots'].'</maxslots>
                <maxserver>'.$row['maxserver'].'</maxserver>
                <freeserver>'.$row['freeserver'].'</freeserver>
                <installedserver>'.$row['installedserver'].'</installedserver>
                <leftslots>'.$row['leftslots'].'</leftslots>
                <installedslots>'.$row['installedslots'].'</installedslots>
            </server>';
        }
        $reply .='</gserver>';
        header("Content-type: text/xml; charset=UTF-8");
        echo $reply;
    } else if ($apiType=='json') {
        header("Content-type: application/json; charset=UTF-8");
        echo json_encode($query->fetchAll(PDO::FETCH_ASSOC));
    } else {
        header('HTTP/1.1 403 Forbidden');
        die('403 Forbidden');
    }
} else if (!isset($success['false']) and array_value_exists('action','gs',$data)) {
    $identifyServerBy=$data['identify_server_by'];
    $localServerID=isid($data['server_local_id'],21);
    $externalServerID=$data['server_external_id'];
    if (isset($data['restart']) and ($data['restart']=='re' or $data['restart']=='st')) {
        $gsRestart=$data['restart'];
        $from=array('server_local_id'=>'id','server_external_id'=>'externalID');
        if (dataExist('identify_server_by',$data)) {
            $query=$sql->prepare("SELECT `id`,`userid`,`rootID`,`serverip`,`port` FROM `gsswitch` WHERE `".$from[$data['identify_server_by']]."`=? AND `resellerid`=? LIMIT 1");
            $query->execute(array($data[$data['identify_server_by']],$resellerID));
            foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $hostID=$row['rootID'];
                $userID=$row['userid'];
                $localID=$row['id'];
                $name=$row['serverip'].':'.$row['port'];
            }
            if(isset($localID) and isset($userID)) {
                $query=$sql->prepare("UPDATE `gsswitch` SET `jobPending`='Y' WHERE `id`=? AND `resellerid`=? LIMIT 1");
                $query->execute(array($localID,$resellerID));
                $query=$sql->prepare("UPDATE `jobs` SET `status`='2' WHERE `type`='gs' AND (`status` IS NULL OR `status`='1') AND (`action`='re' OR `action`='st') AND `affectedID`=? and `resellerID`=?");
                $query->execute(array($localID,$resellerID));
                $query=$sql->prepare("INSERT INTO `jobs` (`api`,`type`,`hostID`,`invoicedByID`,`affectedID`,`userID`,`name`,`status`,`date`,`action`,`resellerid`) VALUES ('A','gs',?,?,?,?,?,NULL,NOW(),?,?)");
                $query->execute(array($hostID,$resellerID,$localID,$userID,$name,$gsRestart,$resellerID));
            } else {
                $success['false'][]='No server can be found to edit';
            }
        } else {
            $success['false'][]='Server cannot be identified';
        }
    } else {
        $success['false'][]='(Re)start or Stop not defined';
    }
} else {
    $success['false'][]='Not supported method or incomplete data';
}

if ($apiType=='xml' and !isset($list)) {
    header("Content-type: text/xml; charset=UTF-8");
    if (isset($success['false'])) {
        $errors=implode(', ',$success['false']);
        $action='fail';
    } else {
        $errors='';
        $action='success';
    }
    $reply=<<<XML
<?xml version='1.0' encoding='UTF-8'?>
<!DOCTYPE gserver>
<gserver>
	<action>$action</action>
	<private>$private</private>
	<active>$active</active>
	<identify_server_by>$identifyServerBy</identify_server_by>
	<slots>$slots</slots>
	<server_external_id>$externalServerID</server_external_id>
	<server_local_id>$localServerID</server_local_id>
	<identify_user_by>$identifyUserBy</identify_user_by>
	<user_localid>$localUserID</user_localid>
	<user_externalid>$externalUserID</user_externalid>
	<username>$username</username>
	<taskset>$taskset</taskset>
    <cores>$cores</cores>
    <eacallowed>$eacallowed</eacallowed>
    <brandname>$brandname</brandname>
    <tvenable>$tvenable</tvenable>
    <pallowed>$pallowed</pallowed>
    <port>$port</port>
    <port2>$port2</port2>
    <port3>$port3</port3>
    <port4>$port4</port4>
    <port5>$port5</port5>
    <minram>$minram</minram>
    <maxram>$maxram</maxram>
    <master_server_id>$hostID</master_server_id>
    <master_server_external_id>$hostExternalID</master_server_external_id>
    <initialpassword>$initialpassword</initialpassword>
    <installGames>$installGames</installGames>
    <autoRestart>$autoRestart</autoRestart>
	<errors>$errors</errors>
XML;
    if (isset ($shorten) and is_array($shorten)) {
        foreach ($shorten as $short) {
            $reply .='
<shorten>'.$short.'</shorten>';
        }
    }

    foreach(customColumns('G',$customID) as $row) {
        $reply .="
        <${row['name']}>${row['value']}</${row['name']}>";
    }
    $reply .='
</gserver>';
    print $reply;
} else if ($apiType=='json' and !isset($list)) {
    header("Content-type: application/json; charset=UTF-8");
    echo json_encode(array('action'=>$action,'private'=>$private,'active'=>$active,'identify_server_by'=>$identifyServerBy,'shorten'=>$shorten,'slots'=>$slots,'server_external_id'=>$externalServerID,'server_local_id'=>$localServerID,'identify_user_by'=>$identifyUserBy,'user_localid'=>$localUserID,'user_externalid'=>$externalUserID,'username'=>$username,'errors'=>$errors));
} else if (!isset($list)) {
    header('HTTP/1.1 403 Forbidden');
    die('403 Forbidden');
}