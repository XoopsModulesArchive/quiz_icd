<?php

// $Id: index.php,v 1.1 2005/10/15 02:05:50 kousuke Exp $
require __DIR__ . '/header.php';
function quiz_icd_showQuestion($icd)
{
    global $xoopsTpl;
    $xoopsTpl->assign('question', $icd->getQuestion());
    $xoopsTpl->assign('category', $icd->getCategory());
    $xoopsTpl->assign('question_index', $icd->getCurrentQuestionIndex() + 1);
}

$a = $_POST['a'] ?? "";
if (!$xoopsUser) {
    $GLOBALS['xoopsOption']['template_main'] = 'quiz_icd_index.html';
} else {
    $xoopsTpl->assign('xoopsUser', $xoopsUser);
    $icd                          = new ICD();
    $GLOBALS['xoopsOption']['template_main'] = 'quiz_icd.html';
    if (!$icd->isStarted()) { // まだ問題を作っていない。
        if (isset($_POST['start'])) { // 問題作成
            $icd->createQuestions();
            quiz_icd_showQuestion($icd);
        } else {
            $GLOBALS['xoopsOption']['template_main'] = 'quiz_icd_index.html';
        }
    } elseif ($icd->gameOver()) { // この回は終了している
        $xoopsTpl->assign('main_message', 'クイズは終了しました。お疲れ様でした');
        $xoopsTpl->assign('all_result', $icd->getAllQuizResult());
    } elseif ($a != '') { // 回答&問題表示
        $answers = $icd->getAnswers();
        $xoopsTpl->assign('question_id', $icd->getCurrentQuestionID());
        $result = $icd->doAnswer($a);
        $xoopsTpl->assign('answer', $a);
        $xoopsTpl->assign('correct_answer', $answers);
        if ($result == '') {
            $xoopsTpl->assign('is_correct', 'ok');
        }
        if ($icd->gameOver()) {
            $xoopsTpl->assign('correctNum', $icd->getCorrectNum());
            $xoopsTpl->assign('ranking', $icd->getRank());
        } else {
            quiz_icd_showQuestion($icd);
        }
    } else { // 問題表示
        $xoopsTpl->assign('question_id', $icd->getCurrentQuestionID());
        quiz_icd_showQuestion($icd);
    }
}
/*
$result = array();
$result = SimpleBlog::get_blog_list();
$xoopsTpl->assign('simpleblog', $result);
*/
require __DIR__ . '/footer.php';

