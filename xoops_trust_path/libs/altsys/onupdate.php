<?php

eval(' function xoops_module_update_' . $mydirname . '( $module ) { return altsys_onupdate_base( $module , "' . $mydirname . '" ) ; } ');

if (!function_exists('altsys_onupdate_base')) {
    /**
     * @param $module
     * @param $mydirname
     * @return bool
     */

    function altsys_onupdate_base($module, $mydirname)
    {
        // transations on module update

        global $msgs; // TODO :-D

        // for Cube 2.1

        if (defined('XOOPS_CUBE_LEGACY')) {
            $root = XCube_Root::getSingleton();

            $root->mDelegateManager->add('Legacy.Admin.Event.ModuleUpdate.' . ucfirst($mydirname) . '.Success', 'altsys_message_append_onupdate');

            $msgs = [];
        } else {
            if (!is_array($msgs)) {
                $msgs = [];
            }
        }

        $db = XoopsDatabaseFactory::getDatabaseConnection();

        $mid = $module->getVar('mid');

        // TABLES (write here ALTER TABLE etc. if necessary)

        // configs (Though I know it is not a recommended way...)

        $check_sql = 'SHOW COLUMNS FROM ' . $db->prefix('config') . " LIKE 'conf_title'";

        if (($result = $db->query($check_sql)) && ($myrow = $db->fetchArray($result)) && 'varchar(30)' == @$myrow['Type']) {
            $db->queryF('ALTER TABLE ' . $db->prefix('config') . " MODIFY `conf_title` VARCHAR(255) NOT NULL DEFAULT '', MODIFY `conf_desc` VARCHAR(255) NOT NULL DEFAULT ''");
        }

        // 0.4 -> 0.5

        $check_sql = 'SELECT COUNT(*) FROM ' . $db->prefix($mydirname . '_language_constants');

        if (!$db->query($check_sql)) {
            $db->queryF('CREATE TABLE ' . $db->prefix($mydirname . '_language_constants') . " (mid SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0,language VARCHAR(32) NOT NULL DEFAULT '',name VARCHAR(255) NOT NULL DEFAULT '',value TEXT,PRIMARY KEY (mid,language,name)) ENGINE=MyISAM");
        }

        // TEMPLATES (all templates have been already removed by modulesadmin)

        $tplfile_handler = xoops_getHandler('tplfile');

        $tpl_path = __DIR__ . '/templates';

        if ($handler = @opendir($tpl_path . '/')) {
            while (false !== ($file = readdir($handler))) {
                if ('.' == mb_substr($file, 0, 1)) {
                    continue;
                }

                $file_path = $tpl_path . '/' . $file;

                if (is_file($file_path)) {
                    $mtime = (int)(@filemtime($file_path));

                    $tplfile = $tplfile_handler->create();

                    $tplfile->setVar('tpl_source', file_get_contents($file_path), true);

                    $tplfile->setVar('tpl_refid', $mid);

                    $tplfile->setVar('tpl_tplset', 'default');

                    $tplfile->setVar('tpl_file', $mydirname . '_' . $file);

                    $tplfile->setVar('tpl_desc', '', true);

                    $tplfile->setVar('tpl_module', $mydirname);

                    $tplfile->setVar('tpl_lastmodified', $mtime);

                    $tplfile->setVar('tpl_lastimported', 0);

                    $tplfile->setVar('tpl_type', 'module');

                    if (!$tplfile_handler->insert($tplfile)) {
                        $msgs[] = '<span style="color:#ff0000;">ERROR: Could not insert template <b>' . htmlspecialchars($mydirname . '_' . $file, ENT_QUOTES | ENT_HTML5) . '</b> to the database.</span>';
                    } else {
                        $tplid = $tplfile->getVar('tpl_id');

                        $msgs[] = 'Template <b>' . htmlspecialchars($mydirname . '_' . $file, ENT_QUOTES | ENT_HTML5) . '</b> added to the database. (ID: <b>' . $tplid . '</b>)';

                        // generate compiled file

                        require_once XOOPS_TRUST_PATH . '/libs/altsys/include/altsys_functions.php';

                        altsys_clear_templates_c();

                        /*include_once XOOPS_ROOT_PATH.'/class/xoopsblock.php' ;
                        if( ! xoops_template_touch( $tplid ) ) {
                            $msgs[] = '<span style="color:#ff0000;">ERROR: Failed compiling template <b>'.htmlspecialchars($mydirname.'_'.$file).'</b>.</span>';
                        } else {
                            $msgs[] = 'Template <b>'.htmlspecialchars($mydirname.'_'.$file).'</b> compiled.</span>';
                        }*/
                    }
                }
            }

            closedir($handler);
        }

        include_once XOOPS_ROOT_PATH . '/class/xoopsblock.php';

        include_once XOOPS_ROOT_PATH . '/class/template.php';

        xoops_template_clear_module_cache($mid);

        return true;
    }

    /**
     * @param $log
     * @param mixed $module_obj
     */

    function altsys_message_append_onupdate(&$module_obj, $log)
    {
        if (is_array(@$GLOBALS['msgs'])) {
            foreach ($GLOBALS['msgs'] as $message) {
                $log->add(strip_tags($message));
            }
        }

        // use mLog->addWarning() or mLog->addError() if necessary
    }
}
