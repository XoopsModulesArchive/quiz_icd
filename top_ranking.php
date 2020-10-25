<?php

// $Id: top_ranking.php,v 1.1 2005/10/15 02:05:50 kousuke Exp $
require __DIR__ . '/header.php';
$icd = new ICD();
$xoopsTpl->assign('all_result', $icd->getTopRanking());
$GLOBALS['xoopsOption']['template_main'] = 'quiz_icd_top_ranking.html';
require __DIR__ . '/footer.php';
