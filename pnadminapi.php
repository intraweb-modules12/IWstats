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

function IWstats_adminapi_deleteIp($args) {

    $table = pnDBGetTables();
    $where = "";
    $c = $table['IWstats_column'];
    $where = "$c[ip] = '$args[ip]'";
    if (!DBUtil::deleteWhere('IWstats', $where)) {
        return LogUtil::registerError($this->__('Error! Sorry! Deletion attempt failed.'));
    }

    return true;
}