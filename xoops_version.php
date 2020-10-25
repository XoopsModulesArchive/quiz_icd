<?php

$modversion['name'] = QUIZ_ICD_NAME;
$modversion['version'] = 0.1;
$modversion['description'] = QUIZ_ICD_NAME_DESC;
$modversion['credits'] = '';
$modversion['author'] = '<a href="http://xoops-modules.sourceforge.jp/" target="_blank">xoops-modules project</a>';
$modversion['help'] = 'help.html';
$modversion['license'] = 'See ReadMe File';
$modversion['official'] = 0;
$modversion['image'] = 'logo.gif';
$modversion['dirname'] = 'quiz_icd';
$modversion['sqlfile']['mysql'] = 'sql/mysql.sql';
$modversion['tables'][0] = 'quiz_icd';
//Admin things
$modversion['hasAdmin'] = 0;
$modversion['adminmenu'] = '';
// Menu
$modversion['hasMain'] = 1;
$modversion['sub'][1]['name'] = 'あなたの成績';
$modversion['sub'][1]['url'] = 'all_result.php';
$modversion['sub'][2]['name'] = '上位の成績';
$modversion['sub'][2]['url'] = 'top_ranking.php';
$modversion['templates'][] = [
    'file' => 'quiz_icd.html',
'description' => 'quiz icd',
];
$modversion['templates'][] = [
    'file' => 'quiz_icd_index.html',
'description' => 'Blog List',
];
$modversion['templates'][] = [
    'file' => 'quiz_icd_all_result.html',
'description' => 'Blog List',
];
$modversion['templates'][] = [
    'file' => 'quiz_icd_top_ranking.html',
'description' => 'Blog List',
];
