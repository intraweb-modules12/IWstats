<?php

function IWstats_pntables() {
    // Initialise table array
    $table = array();

    // Global table
    $table['IWstats'] = DBUtil::getLimitedTablename('IWstats');
    $table['IWstats_column'] = array('statsid' => 'iw_statsid',
        'datetime' => 'iw_datetime',
        'ip' => 'iw_ip',
        'moduleid' => 'iw_moduleid',
        'params' => 'iw_params',
        'uid' => 'iw_uid',
        'isadmin' => 'iw_isadmin',
    );
    $table['IWstats_column_def'] = array('statsid' => "I NOTNULL AUTOINCREMENT KEY",
        'datetime' => "T DEFDATETIME NOTNULL DEFAULT '1970-01-01 00:00:00'",
        'ip' => "C(15) NOTNULL DEFAULT ''",
        'moduleid' => "I NOTNULL DEFAULT '0'",
        'params' => "C(255) NOTNULL DEFAULT ''",
        'uid' => "I NOTNULL DEFAULT '0'",
        'isadmin' => "TINYINT(1) NOTNULL DEFAULT '0'",
    );

    // Return the table information
    return $table;
}
