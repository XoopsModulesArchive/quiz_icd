<?php

// $Id: header.php,v 1.1 2005/10/15 02:05:50 kousuke Exp $
require dirname(__DIR__, 2) . '/mainfile.php';
require_once XOOPS_ROOT_PATH . '/modules/quiz_icd/language/' . $xoopsConfig['language'] . '/main.php';
require_once XOOPS_ROOT_PATH . '/modules/quiz_icd/ICD.php';
require XOOPS_ROOT_PATH . '/header.php';
// $xoopsTpl->assign('xoops_module_header', '<link rel="alternate" type="application/rss+xml" title="RSS" href="'.XOOPS_URL.'/modules/simpleblog/backend.php">');
