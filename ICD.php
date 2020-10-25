<?php

require_once __DIR__ . '/nusoap.php';

/*
$icd = new ICD();
$categorys = array('java', 'windows', 'unix', 'network', 'server', 'xml');
$categoryKeys = array_rand($categorys, 6);
$num = 10;
$cat = $categorys[$categoryKeys[0]];
$res = $icd->fullTextSearch($cat);
$tmp = array();
$q = array();
foreach($res as $row){
$tmp[] =& $row['id'];
$q[$row['id']] = $row;
}
$tmp1 = array_unique($tmp);
$keys = array_rand($tmp1, count($tmp1));
echo ($cat."\n");
for($i = 0; $i<$num ; $i++){
echo("[".$i."]".$keys[$i]."\t".$tmp1[$keys[$i]]."\t".$q[$tmp1[$keys[$i]]]['title']."\n");
}
*/

class ICD
{
    public $Client = null;

    public $Proxy = null;

    public $debug = false;

    // db var

    public $xoopsUser = null;

    public $db = null;

    public $xoopsDB;

    public $soapCache = [];

    public $internalEncoding = 'euc-jp';

    public function __construct($debug = false)
    {
        global $xoopsUser, $xoopsDB;

        $this->debug = $debug;

        $this->Client = new soapclient('http://www.iwebmethod.net/icd1.0/icd.asmx?WSDL', true);

        $this->Proxy = $this->Client->getproxy();

        $this->xoopsDB = $xoopsDB;

        if ($xoopsUser) {
            $this->xoopsUser = $xoopsUser;

            $this->load();
        }
    }

    public function load($force = false)
    {
        if ($force) {
            if (null != ($tmp = $this->_load())) {
                $this->db = $tmp;

                return true;
            }

            return false;
        } elseif (null != $this->db) {
            return true;
        } elseif (null != ($tmp = $this->_load())) {
            $this->db = $tmp;

            return true;
        }

        return false;
    }

    public function _load()
    {
        global $xoopsUser;

        if ($xoopsUser) {
            $sql = sprintf('select * from %s where uid = %u and status = 1', $this->xoopsDB->prefix('quiz_icd'), $this->xoopsUser->uid());

            $dbResult = $this->xoopsDB->query($sql);

            $tmp = [];

            if (list(
                $tmp['uid'], $tmp['number_of_times'], $tmp['status'], $tmp['category'], $tmp['q0'], $tmp['q1'], $tmp['q2'], $tmp['q3'], $tmp['q4'], $tmp['q5'], $tmp['q6'], $tmp['q7'], $tmp['q8'], $tmp['q9'], $tmp['c0'], $tmp['c1'], $tmp['c2'], $tmp['c3'], $tmp['c4'], $tmp['c5'], $tmp['c6'], $tmp['c7'], $tmp['c8'], $tmp['c9'], $tmp['correctNum'], $tmp['start_date'], $tmp['quiz_time']
                ) = $this->xoopsDB->fetchRow($dbResult)) {
                return $tmp;
            }
        }

        return null;
    }

    public function getCurrentQuestionIndex()
    {
        $this->load();

        for ($i = 0; $i < 10; $i++) {
            if (0 == $this->db['c' . $i]) {
                return $i;
            }
        }

        return -1;
    }

    public function getCurrentQuestionID()
    {
        $this->load();

        $index = $this->getCurrentQuestionIndex();

        if ($index < 0) {
            return -1;
        }

        return $this->db['q' . $index];
    }

    public function gameOver()
    {
        if ($this->getCurrentQuestionIndex() < 0) {
            return true;
        }

        return false;
    }

    public function isStarted()
    {
        if (null === $this->db) {
            return false;
        }

        return true;
    }

    public function getQuestion()
    {
        $index = $this->getCurrentQuestionIndex();

        if ($index >= 0) {
            $r = $this->getItemById($this->db['q' . $index]);

            return ($r['question']);
        }

        return false;
    }

    public function getCorrectNum()
    {
        return $this->db['correctNum'];
    }

    public function getCategory()
    {
        return $this->db['category'];
    }

    public function getTotalAnswererNum()
    {
        $res = $this->xoopsDB->query('select count(*) total_answerer_num from ' . $xoopsDB->prefix('quiz_icd') . ' where status != 1');

        if (list($total_answerer_num) = $this->xoopsDB->fetchRow($res)) {
            return $total_answerer_num;
        }

        return false;
    }

    public function getRank()
    {
        $sql = sprintf(
            'select correctNum, quiz_time from %s where status != 1 and correctNum > %u order by quiz_time',
            $this->xoopsDB->prefix('quiz_icd'),
            (int)$this->db['correctNum']
        );

        $res = $this->xoopsDB->query($sql);

        $i = 1;

        while (list($correctNum, $quiz_time) = $this->xoopsDB->fetchRow($res)) {
            if ($correctNum > $this->db['correctNum']) {
                $i++;
            } elseif ($correctNum == $this->db['correctNum']) {
                if ($quiz_time < $this->db['quiz_time']) {
                    $i++;
                } else {
                    return $i;
                }
            }
        }

        return $i;
    }

    public function getTopRanking($limit = 50)
    {
        $sql = sprintf(
            'SELECT uid, number_of_times, category, correctNum, start_date, quiz_time FROM %s WHERE status != 1 ORDER BY correctNum DESC, quiz_time ASC',
            $this->xoopsDB->prefix('quiz_icd')
        );

        $dbResult = $this->xoopsDB->query($sql);

        $result = [];

        $i = 1;

        $userHander = new XoopsUserHandler($this->xoopsDB);

        while (list(
            $row['uid'], $row['number_of_times'], $row['category'], $row['correctNum'], $row['start_date'], $row['quiz_time']
            ) = $this->xoopsDB->fetchRow($dbResult)) {
            $row['ranking'] = $i;

            $user = $userHander->get($row['uid']);

            $row['uname'] = (is_object($user)) ? $user->uname() : 'ÉÔÌÀ';

            $result[] = $row;

            $i++;

            if ($i > $limit) {
                return $result;
            }
        }

        return $result;
    }

    public function getAllQuizResult()
    {
        $sql = sprintf(
            'select number_of_times, category, correctNum, start_date start_date, quiz_time from %s where status != 1 and uid = %u order by number_of_times',
            $this->xoopsDB->prefix('quiz_icd'),
            $this->xoopsUser->uid()
        );

        $dbResult = $this->xoopsDB->query($sql);

        $result = [];

        while (list(
            $row['number_of_times'], $row['category'], $row['correctNum'], $row['start_date'], $row['quiz_time']
            ) = $this->xoopsDB->fetchRow($dbResult)) {
            $result[] = $row;
        }

        return $result;
    }

    public function answerFormat($text)
    {
        if ('' == $text) {
            return $text;
        }

        $result = $text;

        $result = $this->preg_replace('¡¦', '', $result);

        $result = $this->preg_replace(' ', '', $result);

        $result = $this->preg_replace('¡¡', '', $result);

        return $this->strtoupper($result);
    }

    public function doAnswer($answer)
    {
        $current_index = $this->getCurrentQuestionIndex();

        $item = ($this->getItemById($this->db['q' . $current_index]));

        $correct_answers = $item['answers'];

        $result = $item['answers'];

        $formattedAnswer = $this->answerFormat($answer);

        $this->db['c' . $current_index] = 1;

        foreach ($correct_answers as $correct_answer) {
            if (('' != $formattedAnswer) && ($formattedAnswer == $this->answerFormat($correct_answer))) {
                $this->db['c' . $current_index] = 9;

                $result = '';
            }
        }

        if ($this->gameOver()) {
            $correct_num = 0;

            for ($i = 0; $i < 10; $i++) {
                if (9 == $this->db['c' . $i]) {
                    $correct_num++;
                }
            }

            $this->db['correctNum'] = $correct_num;

            $this->_store();

            $this->load(true);

            $this->xoopsDB->queryF('update ' . $this->xoopsDB->prefix('quiz_icd') . ' set quiz_time = NOW() - start_date where uid = ' . $this->xoopsUser->uid() . ' and status = 1');

            $this->db['status'] = 9;

            $this->_store();
        } else {
            $this->_store();
        }

        return $result;
    }

    public function getAnswers()
    {
        $current_index = $this->getCurrentQuestionIndex();

        $item = ($this->getItemById($this->db['q' . $current_index]));

        return $item['answers'];
    }

    public function _store()
    {
        $sql = sprintf(
            "update %s set uid = %u, status = %u, q0='%s', q1='%s', q2='%s', q3='%s', q4='%s', q5='%s', q6='%s', q7='%s', q8='%s', q9='%s', c0=%u, c1=%u, c2=%u, c3=%u, c4=%u, c5=%u, c6=%u, c7=%u, c8=%u, c9=%u, correctNum=%u where uid = %u and status = 1 ",
            $this->xoopsDB->prefix('quiz_icd'),
            $this->xoopsUser->uid(),
            $this->db['status'],
            $this->db['q0'],
            $this->db['q1'],
            $this->db['q2'],
            $this->db['q3'],
            $this->db['q4'],
            $this->db['q5'],
            $this->db['q6'],
            $this->db['q7'],
            $this->db['q8'],
            $this->db['q9'],
            $this->db['c0'],
            $this->db['c1'],
            $this->db['c2'],
            $this->db['c3'],
            $this->db['c4'],
            $this->db['c5'],
            $this->db['c6'],
            $this->db['c7'],
            $this->db['c8'],
            $this->db['c9'],
            $this->db['correctNum'],
            $this->xoopsUser->uid()
        );

        $this->xoopsDB->queryF($sql);
    }

    public function init_icd()
    {
        global $xoopsUser;

        if (!$xoopsUser) {
            return false;
        }

        if (null === $this->db) {
            if (load()) {
                return true;
            }
        }
    }

    public function createQuestions()
    {
        $categorys = ['java', 'windows', 'unix', 'network', 'server', 'xml'];

        $categoryKeys = array_rand($categorys, 6);

        $this->db['category'] = $categorys[$categoryKeys[0]];

        $num = 10;

        $res = $this->fullTextSearch($this->db['category']);

        $tmp = [];

        foreach ($res as $row) {
            $tmp[] = $row['id'];
        }

        $tmp1 = array_unique($tmp);

        $keys = array_rand($tmp1, count($tmp1));

        for ($i = 0; $i < $num; $i++) {
            $this->db['q' . $i] = $tmp1[$keys[$i]];

            $this->db['c' . $i] = 0;
        }

        // create new row

        $sql = sprintf('select max(number_of_times)+1 number_of_times from %s where uid = %u ', $this->xoopsDB->prefix('quiz_icd'), $this->xoopsUser->uid());

        $dbResult = $this->xoopsDB->query($sql);

        if (list($number_of_times) = $this->xoopsDB->fetchRow($dbResult)) {
            $this->db['number_of_times'] = $number_of_times;
        } else {
            $this->db['number_of_times'] = 1;
        }

        $sql = sprintf(
            "insert into %s values (%u, %u, 1, '%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s', 0, 0,0,0,0,0,0,0,0,0,0, NOW(), null)",
            $this->xoopsDB->prefix('quiz_icd'),
            $this->xoopsUser->uid(),
            $this->db['number_of_times'],
            $this->db['category'],
            $this->db['q0'],
            $this->db['q1'],
            $this->db['q2'],
            $this->db['q3'],
            $this->db['q4'],
            $this->db['q5'],
            $this->db['q6'],
            $this->db['q7'],
            $this->db['q8'],
            $this->db['q9']
        );

        $this->xoopsDB->queryF($sql);
    }

    //_________________________________________________________________________________________________

    //   wrapper methods

    public function _hasValue($key, $array)
    {
        // return (array_key_exists($key, $array) && (in_array($key, $array))) ? true : false;

        return (array_key_exists($key, $array) && (null != $array[$key])) ? true : false;
    }

    public function preg_replace($seachWord, $replaceWord, $text)
    {
        if (function_exists('mb_ereg_replace')) {
            return mb_preg_replace($seachWord, $replaceWord, $text);
        }

        return preg_replace($seachWord, $replaceWord, $text);
    }

    public function preg_split($delimita, $text)
    {
        if (function_exists('mb_split')) {
            return mb_preg_split($delimita, $text);
        }

        return preg_split($delimita, $text);
    }

    public function strtoupper($result)
    {
        if (function_exists('mb_strtoupper')) {
            return mb_strtoupper($result);
        }

        return mb_strtoupper($result);
    }

    //_________________________________________________________________________________________________

    //   soap methods

    public function getItemById($id)
    {
        if (array_key_exists($id, $this->soapCache)) {
            return $this->soapCache[$id];
        }

        $method = 'GetItemById';

        $result = [];

        $soapResult = $soapResult = $this->Proxy->$method(['id' => $id]);

        if ((null != $soapResult)) {
            $result = $this->_conv($soapResult, 'UTF-8', $this->internalEncoding);

            $answers = [];

            $result['answer'] = $result['word']['title'];

            $answers[$result['word']['title']] = $result['word']['title'];

            if ($this->_hasValue('english', $result['word'])) {
                $answers[$result['word']['english']] = $result['word']['english'];
            }

            if ($this->_hasValue('japanese', $result['word'])) {
                $answers[$result['word']['japanese']] = $result['word']['japanese'];
            }

            if (array_key_exists('aliases', $result)) {
                foreach ($result['aliases'] as $aliase) {
                    if ($this->_hasValue('title', $aliase)) {
                        $answers[$aliase['title']] = $aliase['title'];
                    }

                    if ($this->_hasValue('english', $aliase)) {
                        $answers[$aliase['english']] = $aliase['english'];
                    }

                    if ($this->_hasValue('japanese', $aliase)) {
                        $answers[$aliase['japanese']] = $aliase['japanese'];
                    }
                }
            }

            $result['answers'] = array_keys($answers);

            $tmpQuestion = $this->preg_replace($result['word']['title'], '¡û¡û¡û¡û', $result['meaning']) . "\n";

            foreach ($result['answers'] as $ans) {
                $tmpQuestion = $this->preg_replace($ans, '¡û¡û¡û¡û', $tmpQuestion) . "\n";
            }

            // $result['question'] = $tmpQuestion;

            $result['question'] = '';

            // mb_regex_encoding('euc-jp');

            $sp1 = $this->preg_split('\<html\>', $tmpQuestion);

            $i = 0;

            foreach ($sp1 as $w1) {
                if (0 == $i) {
                    $w1 = $this->preg_replace('<', '&lt', $w1);

                    $w1 = $this->preg_replace('>', '&gt', $w1);

                    $result['question'] .= $w1;
                } else {
                    $sp2 = $this->preg_split('\</html\>', $tmpQuestion);

                    if (in_array(0, $sp2, true)) {
                        $result['question'] .= $sp2[0];
                    }

                    if (in_array(1, $sp2, true)) {
                        $sp2[1] = $this->preg_replace('<', '&lt', $sp2[1]);

                        $sp2[1] = $this->preg_replace('>', '&gt', $sp2[1]);

                        $result['question'] .= $sp2[1];
                    }
                }

                $i++;
            }
        }

        $this->soapCache[$id] = $result;

        return $result;
    }

    public function enumWords()
    {
        $method = 'EnumWords';

        $soapResult = $soapResult = $this->Proxy->$method([]);

        $result = [];

        if ((null != $soapResult)) {
            $result = $this->_conv($soapResult, 'UTF-8', $this->internalEncoding);
        }

        return $result;
    }

    public function searchWord($word)
    {
        $method = 'SearchWord';

        $soapResult = $soapResult = $this->Proxy->$method(['query' => $word, 'partial' => true]);

        $result = [];

        if ((null != $soapResult)) {
            $result = $this->_conv($soapResult, 'UTF-8', $this->internalEncoding);
        }

        return $result;
    }

    public function fullTextSearch($word)
    {
        $method = 'FullTextSearch';

        $soapResult = $soapResult = $this->Proxy->$method(['query' => urlencode($this->_toUTF8($word))]);

        $result = [];

        if ((null != $soapResult)) {
            $result = $this->_conv($soapResult, 'UTF-8', $this->internalEncoding);
        }

        return $result;
    }

    /*
    function _execute($Func, $params = null){
    $max = $this->fetchNum;
    if ($max == 0){
    $max = 10;
    }
    $result = array();
    $resultNum = 0;
    $page = 0;
    $doNext = true;
    $soapResult = $this->Proxy->$Func($params);
    if($this->debug){
    print("SoapRequest:\n");
    print_r($params);
    // print_r($this->Proxy->getHeaders());
    print("SoapResule:\n");
    print_r($soapResult);
    }
    $page++;
    if( ($soapResult != null) ){
    $result = $this->_conv($soapResult, 'UTF-8', $this->internalEncoding);
    $result['question'] = mb_preg_replace($result['word']['title'], '¡û¡û¡û¡û', $result['meaning'])."\n";
    $result['answer'] = $result['word']['title'];
    }
    return $result;
    }
    */

    public function _conv($text, $fromCode, $toCode)
    {
        if (is_array($text)) {
            $keys1 = array_keys($text);

            $result = [];

            foreach ($keys1 as $key1) {
                $result[$key1] = $this->_conv($text[$key1], $fromCode, $toCode);
            }

            return $result;
        } elseif (function_exists('mb_convert_encoding')) {
            return mb_convert_encoding($text, $toCode, $fromCode);
        } elseif (function_exists('iconv')) {
            return iconv($fromCode, $toCode, $text);
        }

        print "no conv================================================================\n";

        return $text;
    }

    public function _toUTF8($text)
    {
        return $this->_conv($text, $this->internalEncoding, 'utf-8');
    }

    public function _toEUC($text)
    {
        return $this->_conv($text, 'utf-8', $this->internalEncoding);
    }

    /*
    function _encodeValues($values){
    if (is_array($values)){
    for ($i = 0; $i < count($values); $i++){
    $values[$i] = htmlentities($this->_toUTF8($values[$i]));
    }
    return $values;
    }else{
    return htmlentities($this->_toUTF8($values));
    }
    }
    */
}
