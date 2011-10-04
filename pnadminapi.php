<?php

function IWstats_adminapi_reset($args) {
    $dom = ZLanguage::getModuleDomain('IWstats');

    // Security check
    if (!SecurityUtil::checkPermission('IWstats::', '::', ACCESS_DELETE)) {
        return LogUtil::registerError(__('Sorry! No authorization to access this module.', $dom));
    }
    
    $deletiondays = $args['deletiondays'];
    
    // TODO: delete depending on the number of days not all the table like now
    if (!DBUtil::truncateTable('IWstats')) {
        return LogUtil::registerError(__('Error! Sorry! Deletion attempt failed.', $dom));
    }


    // Return the id of the newly created item to the calling process
    return true;
}

/**
 * get available admin panel links
 *
 * @author Mark West
 * @return array array of admin links
 */
function IWstats_adminapi_getlinks() {
    $dom = ZLanguage::getModuleDomain('IWstats');
    $links = array();
    
    if (SecurityUtil::checkPermission('IWstats::', '::', ACCESS_READ)) {
        $links[] = array('url' => pnModURL('IWstats', 'user'), 'text' => __('View', $dom));
    }
    if (SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('IWstats', 'admin', 'reset'), 'text' => __('Reset', $dom));
    }
    if (SecurityUtil::checkPermission('IWstats::', '::', ACCESS_ADMIN)) {
        $links[] = array('url' => pnModURL('IWstats', 'admin', 'modifyconfig'), 'text' => __('Settings', $dom));
    }

    return $links;
}