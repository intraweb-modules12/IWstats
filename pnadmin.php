<?php

function IWstats_admin_main() {
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    return pnRedirect(pnModURL('IWstats', 'admin', 'view'));
}

function IWstats_admin_view($args) {
    $startnum = FormUtil::getPassedValue('startnum', isset($args['startnum']) ? $args['startnum'] : 1, 'GET');
    $moduleId = FormUtil::getPassedValue('moduleId', isset($args['moduleId']) ? $args['moduleId'] : 0, 'GETPOST');
    $uname = FormUtil::getPassedValue('uname', isset($args['uname']) ? $args['uname'] : null, 'GETPOST');
    $ip = FormUtil::getPassedValue('ip', isset($args['ip']) ? $args['ip'] : null, 'GET');

    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }
    $rpp = 50;

    if ($uname != null && $uname != '') {
        // get user id from uname
        $uid = pnUserGetIDFromName($uname);
        if (!$uid) {
            LogUtil::registerError(__f('User \'%s\' not found', array($uname), $dom));
            $uname = '';
        }
    }
    // get last records
    $records = pnModAPIFunc('IWstats', 'user', 'getAllRecords', array('rpp' => $rpp,
        'init' => $startnum,
        'moduleId' => $moduleId,
        'uid' => $uid,
        'ip' => $ip,
            ));

    // get last records
    $nRecords = pnModAPIFunc('IWstats', 'user', 'getAllRecords', array('onlyNumber' => 1,
        'moduleId' => $moduleId,
        'uid' => $uid,
        'ip' => $ip,
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
            $records[$record['statsid']]['func'] = (isset($valueArray['func'])) ? $valueArray['func'] : 'main';
            $records[$record['statsid']]['type'] = (isset($valueArray['type'])) ? $valueArray['type'] : 'user';

            $params = '';
            foreach ($valueArray as $key => $v) {
                if ($key != 'module' && $key != 'func' && $key != 'type') {
                    $params .= $key . '=' . $v . '&';
                }
            }
        } else
            $params = '';

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
    $users[0] = __('Unregistered');

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
    $pnRender->assign('pager', array('numitems' => $nRecords,
        'itemsperpage' => $rpp));
    $pnRender->assign('modulesNames', $modulesNames);
    $pnRender->assign('modulesArray', $modulesArray);
    $pnRender->assign('moduleId', $moduleId);
    $pnRender->assign('uname', $uname);
    // Return the output that has been generated by this function
    return $pnRender->fetch('IWstats_admin_view.htm');
}

function IWstats_admin_reset($args) {
    $dom = ZLanguage::getModuleDomain('IWstats');
    $confirmation = FormUtil::getPassedValue('confirmation', isset($args['confirmation']) ? $args['confirmation'] : null, 'POST');
    $deletiondays = FormUtil::getPassedValue('deletiondays', isset($args['deletiondays']) ? $args['deletiondays'] : null, 'POST');

    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Check for confirmation.
    if (empty($confirmation)) {
        $pnRender = pnRender::getInstance('IWstats', false);
        return $pnRender->fetch('IWstats_admin_reset.htm');
    }

    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('IWstats', 'admin', 'main'));
    }

    // reset the site statistics
    if (!pnModAPIFunc('IWstats', 'admin', 'reset', array('deletiondays' => $deletiondays))) {
        LogUtil::registerError(__('IWstats reset error.', $dom));
        return pnRedirect(pnModURL('IWstats', 'admin', 'main'));
    }
    // Success
    LogUtil::registerStatus(__('IWstats reset', $dom));
    return pnRedirect(pnModURL('IWstats', 'admin', 'main'));
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
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $pnRender = pnRender::getInstance('IWstats', false);

    // Assign all the module variables to the template
    $pnRender->assign(pnModGetVar('IWstats'));

    // Return the output that has been generated by this function
    return $pnRender->fetch('IWstats_admin_modifyconfig.htm');
}

/**
 * Update the configuration
 *
 * @author       Mark West
 * @param        bold           print items in bold
 * @param        itemsperpage   number of items per page
 */
function IWstats_admin_updateconfig() {
    $dom = ZLanguage::getModuleDomain('IWstats');
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Confirm authorisation code
    if (!SecurityUtil::confirmAuthKey()) {
        return LogUtil::registerAuthidError(pnModURL('IWstats', 'admin', 'view'));
    }

    // The configuration has been changed, so we clear all caches for this module.
    $pnRender = pnRender::getInstance('IWstats');
    $pnRender->clear_all_cache();

    // the module configuration has been updated successfuly
    LogUtil::registerStatus(__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('IWstats', 'admin'));
}
