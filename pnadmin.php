<?php

function IWstats_admin_main() {
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        throw new Zikula_Exception_Forbidden();
    }

    return pnredirect(pnModurl('IWstats', 'admin', 'view'));
}

function IWstats_admin_view($args) {
    $statsSaved = unserialize(SessionUtil::getVar('statsSaved'));
    $startnum = FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : 1, 'GETPOST');
    $moduleId = FormUtil::getPassedValue('moduleId', isset($args['moduleId']) ? $args['moduleId'] : $statsSaved['moduleId'], 'GETPOST');
    $uname = FormUtil::getPassedValue('uname', isset($args['uname']) ? $args['uname'] : $statsSaved['uname'], 'GETPOST');
    $ip = FormUtil::getPassedValue('ip', isset($args['ip']) ? $args['ip'] : $statsSaved['ip'], 'GETPOST');
    $registered = FormUtil::getPassedValue('registered', isset($args['registered']) ? $args['registered'] : $statsSaved['registered'], 'GETPOST');
    $reset = FormUtil::getPassedValue('reset', isset($args['reset']) ? $args['reset'] : 0, 'GET');
    $fromDate = FormUtil::getPassedValue('fromDate', isset($args['fromDate']) ? $args['fromDate'] : null, 'GETPOST');
    $toDate = FormUtil::getPassedValue('toDate', isset($args['toDate']) ? $args['toDate'] : null, 'GETPOST');


    SessionUtil::setVar('statsSaved', serialize(array('moduleId' => $moduleId,
                'uname' => $uname,
                'ip' => $ip,
                'registered' => $registered,
            )));

    if ($reset == 1) {
        $ip = null;
        $uname = null;
        $registered = 0;
        $moduleId = 0;
        SessionUtil::delVar('statsSaved');
    }

    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        throw new Zikula_Exception_Forbidden();
    }

    $uid = 0;
    $rpp = 50;
    $lastDays = 10;

    if ($uname != null && $uname != '') {
        // get user id from uname
        $uid = UserUtil::getIdFromName($uname);
        if (!$uid) {
            LogUtil::registerError(__f('User \'%s\' not found', array($uname)));
            $uname = '';
        }
    }

    $time = time();

    if ($fromDate != null) {
        $fromDate = mktime(0, 0, 0, substr($fromDate, 3, 2), substr($fromDate, 0, 2), substr($fromDate, 6, 4));
        $fromDate = date('Y-m-d 00:00:00', $fromDate);
        $fromDate = DateUtil::makeTimestamp($fromDate);
        $fromDate = date('d-m-Y', $fromDate);
    } else {
        $fromDate = date('d-m-Y', $time - $lastDays * 24 * 60 * 60);
    }

    if ($toDate != null) {
        $toDate = mktime(0, 0, 0, substr($toDate, 3, 2), substr($toDate, 0, 2), substr($toDate, 6, 4));
        $toDate = date('Y-m-d 00:00:00', $toDate);
        $toDate = DateUtil::makeTimestamp($toDate);
        $toDate = date('d-m-Y', $toDate);
    } else {
        $toDate = date('d-m-Y', $time);
    }

    // get last records
    $records = pnModAPIFunc('IWstats', 'user', 'getAllRecords', array('rpp' => $rpp,
        'init' => $startnum,
        'moduleId' => $moduleId,
        'uid' => $uid,
        'ip' => $ip,
        'registered' => $registered,
        'fromDate' => $fromDate,
        'toDate' => $toDate,
            ));

    // get last records
    $nRecords = pnModAPIFunc('IWstats', 'user', 'getAllRecords', array('onlyNumber' => 1,
        'moduleId' => $moduleId,
        'uid' => $uid,
        'ip' => $ip,
        'registered' => $registered,
        'fromDate' => $fromDate,
        'toDate' => $toDate,
            ));

    $usersList = '';
    foreach ($records as $record) {
        if ($record['params'] != '') {
            $valueArray = array();
            $paramsArray = explode('&', $record['params']);
            foreach ($paramsArray as $param) {
                $value = explode('=', $param);
                $valueArray[$value[0]] = $value[1];
            }
            if ($record['moduleid'] > 0) {
                $records[$record['statsid']]['func'] = (isset($valueArray['func'])) ? $valueArray['func'] : 'main';
                $records[$record['statsid']]['type'] = (isset($valueArray['type'])) ? $valueArray['type'] : 'user';
            } else {
                $records[$record['statsid']]['func'] = '';
                $records[$record['statsid']]['type'] = '';
            }

            $params = '';
            foreach ($valueArray as $key => $v) {
                if ($key != 'module' && $key != 'func' && $key != 'type') {
                    $params .= $key . '=' . $v . '&';
                }
            }
        } else {
            $params = '';
            if ($record['moduleid'] > 0) {
                $records[$record['statsid']]['func'] = 'main';
                $records[$record['statsid']]['type'] = 'user';
            } else {
                $records[$record['statsid']]['func'] = '';
                $records[$record['statsid']]['type'] = '';
            }
        }

        $params = str_replace('%3F', '?', $params);
        $params = str_replace('%3D', '=', $params);
        $params = str_replace('%2F', '/', $params);
        $params = str_replace('%26', '&', $params);
        $params = str_replace('%7E', '~', $params);

        $records[$record['statsid']]['params'] = substr($params, 0, -1);

        $usersList .= $record['uid'] . '$$';
    }

    $sv = pnModFunc('iw_main', 'user', 'genSecurityValue');
    $users = pnModFunc('iw_main', 'user', 'getAllUsersInfo', array('info' => 'ncc',
        'sv' => $sv,
        'list' => $usersList));

    $sv = pnModFunc('iw_main', 'user', 'genSecurityValue');
    $usersMails = pnModFunc('iw_main', 'user', 'getAllUsersInfo', array('info' => 'l',
        'sv' => $sv,
        'list' => $usersList));

    $users[0] = __('Unregistered', $dom);

    // get all modules
    $modules = pnModAPIFunc('modules', 'admin', 'list');

    foreach ($modules as $module) {
        $modulesNames[$module['id']] = $module['name'];
        $modulesArray[] = array('id' => $module['id'],
            'name' => $module['name']);
    }

    // Create output object
    $pnRender = pnRender::getInstance('IWstats', false);
    $pnRender->assign('records', $records);
    $pnRender->assign('users', $users);
    $pnRender->assign('usersMails', $usersMails);
    $pnRender->assign('pager', array('numitems' => $nRecords, 'itemsperpage' => $rpp));
    $pnRender->assign('modulesNames', $modulesNames);
    $pnRender->assign('modulesArray', $modulesArray);
    $pnRender->assign('moduleId', $moduleId);
    $pnRender->assign('url', pnGetBaseURL());
    $pnRender->assign('uname', $uname);
    $pnRender->assign('registered', $registered);
    $pnRender->assign('fromDate', $fromDate);
    $pnRender->assign('toDate', $toDate);
    $pnRender->assign('maxDate', date('Ymd', time()));
    return $pnRender->fetch('IWstats_admin_view.htm');
}

function IWstats_admin_reset($args) {
    $confirmation = FormUtil::getPassedValue('confirmation', isset($args['confirmation']) ? $args['confirmation'] : null, 'POST');
    $deletiondays = FormUtil::getPassedValue('deletiondays', isset($args['deletiondays']) ? $args['deletiondays'] : null, 'POST');

    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        throw new Zikula_Exception_Forbidden();
    }

    // Check for confirmation.
    if (empty($confirmation)) {
        $pnRender = pnRender::getInstance('IWstats', false);
        return $pnRender->fetch('IWstats_admin_reset.htm');
    }

    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(ModUtil::url('IWstats', 'admin', 'main'));
    }

    // reset the site statistics
    if (!pnModAPIFunc('IWstats', 'admin', 'reset', array('deletiondays' => $deletiondays))) {
        LogUtil::registerError(__('IWstats reset error.'));
        return System::redirect(ModUtil::url('IWstats', 'admin', 'main'));
    }
    // Success
    LogUtil::registerStatus(__('IWstats reset'));
    return System::redirect(ModUtil::url('IWstats', 'admin', 'main'));
}

/**
 * Modify configuration
 *
 * @author       The Zikula Development Team
 * @return       output       The configuration page
 */
function IWstats_admin_modifyconfig() {
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        throw new Zikula_Exception_Forbidden();
    }

    // get all modules
    $modules = pnModAPIFunc('modules', 'admin', 'list');

    $moduleIds = unserialize(pnModgetvar('IWstats', 'modulesSkipped'));
    $i = 0;
    foreach ($modules as $module) {
        $modules[$i]['active'] = (in_array($module['id'], $moduleIds)) ? 1 : 0;
        $i++;
    }

    // Assign all the module variables to the template
    $pnRender = pnRender::getInstance('IWstats', false);
    $pnRender->assign('skipedIps', pnModgetvar('IWstats', 'skipedIps'));
    $pnRender->assign('modules', $modules);
    return $pnRender->fetch('IWstats_admin_modifyconfig.htm');
}

/**
 * Update the configuration
 *
 * @author       Mark West
 * @param        bold           print items in bold
 * @param        itemsperpage   number of items per page
 */
function IWstats_admin_updateconfig($args) {
    $skipedIps = FormUtil::getPassedValue('skipedIps', isset($args['skipedIps']) ? $args['skipedIps'] : 1, 'POST');
    $moduleId = FormUtil::getPassedValue('moduleId', isset($args['moduleId']) ? $args['moduleId'] : array(), 'POST');
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        throw new Zikula_Exception_Forbidden();
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('IWstats', 'admin', 'main'));
    }

    $modulesIdArray = array();
    foreach ($moduleId as $m) {
        $modulesIdArray[] = $m;
    }

    pnModSetVar('IWstats', 'skipedIps', $skipedIps);
    pnModSetVar('IWstats', 'modulesSkipped', serialize($modulesIdArray));

    $pnRender = pnRender::getInstance('IWstats', false);

    // The configuration has been changed, so we clear all caches for this module.
    $pnRender->clear_all_cache();

    // the module configuration has been updated successfuly
    LogUtil::registerStatus(__('Done! Module configuration updated.'));

    return pnredirect(pnModurl('IWstats', 'admin', 'modifyconfig'));
}

function IWstats_admin_deleteIp($args) {
    $ip = FormUtil::getPassedValue('ip', isset($args['ip']) ? $args['ip'] : 1, 'GETPOST');
    $confirm = FormUtil::getPassedValue('confirm', isset($args['confirm']) ? $args['confirm'] : 0, 'POST');

    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        throw new Zikula_Exception_Forbidden();
    }

    if (!$confirm) {
        // Assign all the module variables to the template
        $pnRender = pnRender::getInstance('IWstats', false);
        $pnRender->assign('ip', $ip);
        return $pnRender->fetch('IWstats_admin_deleteip.htm');
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('IWstats', 'admin', 'main'));
    }

    if (!pnModAPIFunc('IWstats', 'admin', 'deleteIp', array('ip' => $ip))) {
        LogUtil::registerError(__f('Error deleting the ip \'%s\'', array($ip)));
        return pnredirect(pnModurl('IWstats', 'admin', 'view'));
    }

    // Success
    LogUtil::registerStatus(__f('Ip \'%s\' deleted', array($ip)));
    return pnredirect(pnModurl('IWstats', 'admin', 'view'));
}

function IWstats_admin_viewStats($args) {
    $statsSaved = unserialize(SessionUtil::getVar('statsSaved'));
    $startnum = FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : 1, 'GETPOST');
    $moduleId = FormUtil::getPassedValue('moduleId', isset($args['moduleId']) ? $args['moduleId'] : $statsSaved['moduleId'], 'GETPOST');
    $uname = FormUtil::getPassedValue('uname', isset($args['uname']) ? $args['uname'] : $statsSaved['uname'], 'GETPOST');
    $ip = FormUtil::getPassedValue('ip', isset($args['ip']) ? $args['ip'] : $statsSaved['ip'], 'GETPOST');
    $registered = FormUtil::getPassedValue('registered', isset($args['registered']) ? $args['registered'] : $statsSaved['registered'], 'GETPOST');
    $reset = FormUtil::getPassedValue('reset', isset($args['reset']) ? $args['reset'] : 0, 'GET');
    $fromDate = FormUtil::getPassedValue('fromDate', isset($args['fromDate']) ? $args['fromDate'] : null, 'GETPOST');
    $toDate = FormUtil::getPassedValue('toDate', isset($args['toDate']) ? $args['toDate'] : null, 'GETPOST');
    pnSessionSetVar('statsSaved', serialize(array('moduleId' => $moduleId,
                'uname' => $uname,
                'ip' => $ip,
                'registered' => $registered,
            )));

    if ($reset == 1) {
        $ip = null;
        $uname = null;
        $registered = 0;
        $moduleId = 0;
        pnSessionDelVar('statsSaved');
    }

    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        throw new Zikula_Exception_Forbidden();
    }

    $uid = 0;
    $rpp = 50;
    $lastDays = 10;

    if ($uname != null && $uname != '') {
        // get user id from uname
        $uid = UserUtil::getIdFromName($uname);
        if (!$uid) {
            LogUtil::registerError(__f('User \'%s\' not found', array($uname)));
            $uname = '';
        }
    }

    $time = time();

    if ($fromDate != null) {
        $fromDate = mktime(0, 0, 0, substr($fromDate, 3, 2), substr($fromDate, 0, 2), substr($fromDate, 6, 4));
        $fromDate = date('Y-m-d 00:00:00', $fromDate);
        $fromDate = DateUtil::makeTimestamp($fromDate);
        $fromDate = date('d-m-Y', $fromDate);
    } else {
        $fromDate = date('d-m-Y', $time - $lastDays * 24 * 60 * 60);
    }

    if ($toDate != null) {
        $toDate = mktime(0, 0, 0, substr($toDate, 3, 2), substr($toDate, 0, 2), substr($toDate, 6, 4));
        $toDate = date('Y-m-d 00:00:00', $toDate);
        $toDate = DateUtil::makeTimestamp($toDate);
        $toDate = date('d-m-Y', $toDate);
    } else {
        $toDate = date('d-m-Y', $time);
    }

    // get last records
    $records = pnModAPIFunc('IWstats', 'user', 'getAllRecords', array('rpp' => -1,
        'init' => -1,
        'moduleId' => $moduleId,
        'uid' => $uid,
        'ip' => $ip,
        'registered' => $registered,
        'fromDate' => $fromDate,
        'toDate' => $toDate,
            ));

    $usersList = '';
    $usersIdsCounter = array();
    $usersIpCounter = array();
    foreach ($records as $record) {
        $usersIpCounter[$record['ip']] = (isset($usersIpCounter[$record['ip']])) ? $usersIpCounter[$record['ip']] + 1 : 1;
        $usersIdsCounter[$record['uid']] = (isset($usersIdsCounter[$record['uid']])) ? $usersIdsCounter[$record['uid']] + 1 : 1;
        $usersList .= $record['uid'] . '$$';
    }
    /*
      foreach ($records as $record) {
      if ($record['params'] != '') {
      $valueArray = array();
      $paramsArray = explode('&', $record['params']);
      foreach ($paramsArray as $param) {
      $value = explode('=', $param);
      $valueArray[$value[0]] = $value[1];
      }
      if ($record['moduleid'] > 0) {
      $records[$record['statsid']]['func'] = (isset($valueArray['func'])) ? $valueArray['func'] : 'main';
      $records[$record['statsid']]['type'] = (isset($valueArray['type'])) ? $valueArray['type'] : 'user';
      } else {
      $records[$record['statsid']]['func'] = '';
      $records[$record['statsid']]['type'] = '';
      }

      $params = '';
      foreach ($valueArray as $key => $v) {
      if ($key != 'module' && $key != 'func' && $key != 'type') {
      $params .= $key . '=' . $v . '&';
      }
      }
      } else {
      $params = '';
      if ($record['moduleid'] > 0) {
      $records[$record['statsid']]['func'] = 'main';
      $records[$record['statsid']]['type'] = 'user';
      } else {
      $records[$record['statsid']]['func'] = '';
      $records[$record['statsid']]['type'] = '';
      }
      }

      $params = str_replace('%3F', '?', $params);
      $params = str_replace('%3D', '=', $params);
      $params = str_replace('%2F', '/', $params);
      $params = str_replace('%26', '&', $params);
      $params = str_replace('%7E', '~', $params);

      $records[$record['statsid']]['params'] = substr($params, 0, -1);

      $usersList .= $record['uid'] . '$$';
      }
     */

    $sv = pnModFunc('iw_main', 'user', 'genSecurityValue');
    $users = pnModFunc('iw_main', 'user', 'getAllUsersInfo', array('info' => 'ncc',
        'sv' => $sv,
        'list' => $usersList));

    // get all modules
    $modules = pnModAPIFunc('modules', 'admin', 'list');

    foreach ($modules as $module) {
        $modulesNames[$module['id']] = $module['name'];
        $modulesArray[] = array('id' => $module['id'],
            'name' => $module['name']);
    }

    $pnRender = pnRender::getInstance('IWstats', false);
    $pnRender->assign('records', $records);
    $pnRender->assign('users', $users);
    $pnRender->assign('usersIdsCounter', $usersIdsCounter);
    $pnRender->assign('usersIpCounter', $usersIpCounter);
    $pnRender->assign('modulesNames', $modulesNames);
    $pnRender->assign('modulesArray', $modulesArray);
    $pnRender->assign('moduleId', $moduleId);
    $pnRender->assign('url', pnGetBaseURL());
    $pnRender->assign('uname', $uname);
    $pnRender->assign('registered', $registered);
    $pnRender->assign('fromDate', $fromDate);
    $pnRender->assign('toDate', $toDate);
    $pnRender->assign('maxDate', date('Ymd', time()));
    return $pnRender->fetch('IWstats_admin_stats.htm');
}

