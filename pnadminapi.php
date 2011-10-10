<?php

function IWstats_adminapi_reset($args) {
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_DELETE)) {
        return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'));
    }

    $deletiondays = $args['deletiondays'];

    // TODO: delete depending on the number of days not all the table like now
    if (!DBUtil::executeSQL('Truncate table ' . System::getVar('prefix') . '_IWstats')) {
        return LogUtil::registerError($this->__('Error! Sorry! Deletion attempt failed.'));
    }

    // Return the id of the newly created item to the calling process
    return true;
}

// skipped value to 1 for IP
function IWstats_adminapi_deleteIp($args) {
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_DELETE)) {
        return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'));
    }
    $table = pnDBGetTables();
    $where = "";
    $c = $table['IWstats_column'];
    $where = "$c[ip] = '$args[ip]'";
    $items = array('skipped' => 1);

    if (!DBUtil::updateObject($items, 'IWstats', $where)) {
        return LogUtil::registerError($this->__('Error! Sorry! Deletion attempt failed.'));
    }

    return true;
}

function IWstats_adminapi_skipModules($args) {
    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_EDIT)) {
        return LogUtil::registerError($this->__('Sorry! No authorization to access this module.'));
    }

    $table = pnDBGetTables();
    $c = $table['IWstats_column'];
    $where = "1=1";
    $items = array('skippedModule' => 0);
    if (!DBUTil::updateObject($items, 'IWstats', $where)) {
        return LogUtil::registerError(__('Error! Update attempt failed.', $dom));
    }

    $items = array('skippedModule' => 1);
    foreach ($args['moduleId'] as $module) {
        $where = "$c[moduleid] = $module";
        if (!DBUTil::updateObject($items, 'IWstats', $where)) {
            return LogUtil::registerError(__('Error! Update attempt failed.', $dom));
        }
    }

    return true;
}

function IWstats_adminapi_resume($args) {
    $time = time();
    $fromDate = date('d-m-Y', $time - 10000 * 24 * 60 * 60);
    $toDate = date('d-m-Y', $time - $args['daysAgo'] * 24 * 60 * 60);

    // get last records
    $records = pnModAPIFunc('IWstats', 'user', 'getAllRecords', array('fromDate' => $fromDate,
        'toDate' => $toDate,
        'all' => 1,
            ));

    $recordsArray = array();

    foreach ($records as $record) {
        if (key_exists(substr($record['datetime'], 0, 10), $recordsArray)) {
            $recordsArray[substr($record['datetime'], 0, 10)]['nRecords']++;
            if (($record['uid'] > 0))
                $recordsArray[substr($record['datetime'], 0, 10)]['registered']++;
            if (key_exists($record['uid'], $recordsArray[substr($record['datetime'], 0, 10)]['users'])) {
                $recordsArray[substr($record['datetime'], 0, 10)]['users'][$record['uid']]++;
            } else {
                $recordsArray[substr($record['datetime'], 0, 10)]['users'][$record['uid']] = 1;
            }
            if (key_exists($record['moduleid'], $recordsArray[substr($record['datetime'], 0, 10)]['modules'])) {
                $recordsArray[substr($record['datetime'], 0, 10)]['modules'][$record['moduleid']]++;
            } else {
                $recordsArray[substr($record['datetime'], 0, 10)]['modules'][$record['moduleid']] = 1;
            }
            if (($record['skipped'] == 1))
                $recordsArray[substr($record['datetime'], 0, 10)]['skipped']++;
            if (($record['skippedModule'] == 1))
                $recordsArray[substr($record['datetime'], 0, 10)]['skippedModule']++;
            if (($record['isadmin'] == 1))
                $recordsArray[substr($record['datetime'], 0, 10)]['isadmin']++;
        } else {
            $recordsArray[substr($record['datetime'], 0, 10)]['nRecords'] = 1;
            $recordsArray[substr($record['datetime'], 0, 10)]['registered'] = ($record['uid'] > 0) ? 1 : 0;
            if ($record['uid'] > 0) {
                $recordsArray[substr($record['datetime'], 0, 10)]['users'][$record['uid']] = 1;
            }
            $recordsArray[substr($record['datetime'], 0, 10)]['datetime'] = substr($record['datetime'], 0, 10) . ' 00:00:00';
            $recordsArray[substr($record['datetime'], 0, 10)]['modules'][$record['moduleid']] = 1;
            $recordsArray[substr($record['datetime'], 0, 10)]['skipped'] = ($record['skipped'] == 1) ? 1 : 0;
            $recordsArray[substr($record['datetime'], 0, 10)]['skippedModule'] = ($record['skippedModule'] == 1) ? 1 : 0;
            $recordsArray[substr($record['datetime'], 0, 10)]['isadmin'] = ($record['isadmin'] == 1) ? 1 : 0;
        }
    }

    // save records in ddbb
    foreach ($recordsArray as $record) {
        $users = '$';
        foreach ($record['users'] as $key => $value) {
            $users .= '$' . $key . '|' . $value . '$';
        }
        $modules = '$';
        foreach ($record['modules'] as $key => $value) {
            $modules .= '$' . $key . '|' . $value . '$';
        }
        $item = array(
            'datetime' => $record['datetime'],
            'nrecords' => $record['nRecords'],
            'registered' => $record['registered'],
            'modules' => $modules,
            'skipped' => $record['skipped'],
            'skippedModule' => $record['skippedModule'],
            'isadmin' => $record['isadmin'],
            'users' => $users,
        );

        if (!DBUtil::insertObject($item, 'IWstats_resume')) {
            return LogUtil::registerError(__('Error! Creation attempt failed.', $dom));
        }
    }

    // delete records from database
}