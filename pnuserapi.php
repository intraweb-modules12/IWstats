<?php

function IWstats_userapi_collect($args) {
    $dom = ZLanguage::getModuleDomain('IWstats');

    // prepare data
    $uid = (pnUserLoggedIn()) ? pnUserGetVar('uid') : 0;

    // get module identity
    $modid = pnModGetIDFromName(pnModGetName());
    // get func
    $params = $_SERVER['QUERY_STRING'];

    $isadmin = (SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) ? 1 : 0;

    $ip = '';
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = pnModAPIFunc('IWstats' ,'user', 'cleanremoteaddr', array('originaladdr' => $_SERVER['REMOTE_ADDR']));
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = pnModAPIFunc('IWstats' ,'user', 'cleanremoteaddr', array('originaladdr' => $_SERVER['HTTP_X_FORWARDED_FOR']));
    }
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = pnModAPIFunc('IWstats' ,'user', 'cleanremoteaddr', array('originaladdr' => $_SERVER['HTTP_CLIENT_IP']));
    }

    $item = array('moduleid' => $modid,
        'params' => $params,
        'uid' => $uid,
        'ip' => $ip,
        'datetime' => date('Y-m-d H:i:s', time()),
        'isadmin' => $isadmin,
    );

    if (!DBUtil::insertObject($item, 'IWstats')) {
        return LogUtil::registerError(__('Error! Creation attempt failed.', $dom));
    }

    return true;
}

function IWstats_userapi_cleanremoteaddr($args) {
    $originaladdr = $args['originaladdr'];
    $matches = array();
    // first get all things that look like IP addresses.
    if (!preg_match_all('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/',$args['originaladdr'],$matches,PREG_SET_ORDER)) {
        return '';
    }
    $goodmatches = array();
    $lanmatches = array();
    foreach ($matches as $match) {
        //        print_r($match);
        // check to make sure it's not an internal address.
        // the following are reserved for private lans...
        // 10.0.0.0 - 10.255.255.255
        // 172.16.0.0 - 172.31.255.255
        // 192.168.0.0 - 192.168.255.255
        // 169.254.0.0 -169.254.255.255
        $bits = explode('.',$match[0]);
        if (count($bits) != 4) {
            // weird, preg match shouldn't give us it.
            continue;
        }
        if (($bits[0] == 10)
            || ($bits[0] == 172 && $bits[1] >= 16 && $bits[1] <= 31)
            || ($bits[0] == 192 && $bits[1] == 168)
            || ($bits[0] == 169 && $bits[1] == 254)) {
            $lanmatches[] = $match[0];
            continue;
        }
        // finally, it's ok
        $goodmatches[] = $match[0];
    }
    if (!count($goodmatches)) {
        // perhaps we have a lan match, it's probably better to return that.
        if (!count($lanmatches)) {
            return '';
        } else {
            return array_pop($lanmatches);
        }
    }
    if (count($goodmatches) == 1) {
        return $goodmatches[0];
    }

    // We need to return something, so return the first
    return array_pop($goodmatches);
}

function IWstats_userapi_getAllRecords($args) {
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    $items = array();
    $init = (isset($args['init'])) ? $args['init'] - 1 : -1;
    $rpp = (isset($args['rpp'])) ? $args['rpp'] : -1;
    $table = pnDBGetTables();
    $where = "";
    $c = $table['IWstats_column'];

    if (isset($args['moduleId']) && $args['moduleId'] > 0) {
        $where = "$c[moduleid] = $args[moduleId]";
    }

    if (isset($args['uid']) && $args['uid'] > 0) {
        $where = "$c[uid] = $args[uid]";
    }
    if (isset($args['ip'])) {
        $where = "$c[ip] = '$args[ip]'";
    }

    $and = ($where != '') ? ' AND ' : '';
    $where .= $and . "$c[isadmin]=0";

    $orderby = "$c[statsid] desc";

    if (isset($args['onlyNumber']) && $args['onlyNumber'] == 1) {
        $items = DBUtil::selectObjectCount('IWstats', $where);
    } else {
        $items = DBUtil::selectObjectArray('IWstats', $where, $orderby, $init, $rpp, 'statsid');
    }

    // Check for an error with the database code, and if so set an appropriate
    // error message and return
    if ($items === false) {
        return LogUtil::registerError($this->__('No s\'han pogut carregar els registres.'));
    }
    // Return the items
    return $items;
}