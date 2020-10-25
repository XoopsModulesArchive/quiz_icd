<?php

// $Id: all_result.php,v 1.1 2005/10/15 02:05:50 kousuke Exp $
require __DIR__ . '/header.php';
$a = $_POST['a'] ?? '';
if (!$xoopsUser) {
    $GLOBALS['xoopsOption']['template_main'] = 'quiz_icd_index.html';
} else {
    $icd = new ICD();

    $xoopsTpl->assign('all_result', $icd->getAllQuizResult());

    $GLOBALS['xoopsOption']['template_main'] = 'quiz_icd_all_result.html';
}
require __DIR__ . '/footer.php';
