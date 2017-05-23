<?php
namespace console\controllers;

use common\lib\HtmlParser;
use common\lib\HttpRequest;
use common\models\StockBasic;
use common\models\StockDetail;
use common\models\StockFeed;
use common\models\StockScore;
use Yii;
use yii\base\Exception;

/**
 * Stock controller
 */
class StockController extends \yii\console\Controller
{
    const CATEGORY = 'stock';
    // 沪深A股所有股票
    const ALL_STOCK_URL_FORMAT = 'http://nufm.dfcfw.com/EM_Finance2014NumericApplication/JS.aspx/JS.aspx?type=ct&st=(FFARank)&sr=1&p=%d&ps=50&js={"pages":(pc),"data":[(x)]}&token=894050c76af8597a853f5b408b759f5d&cmd=C._A&sty=DCFFITAMA&rt=49577118';
    // 股票历史
    const STOCK_HISTORY_URL_FORMAT = "http://d.10jqka.com.cn/v2/line/hs_%s/01/last.js";
    // 股票某一年的价格历史
    const STOCK_YEAR_HISTORY_URL_FORMAT = "http://d.10jqka.com.cn/v2/line/hs_%s/01/%d.js";
    // 股票当天价格
    const STOCK_TODAY_URL_FORMAT = "http://d.10jqka.com.cn/v2/line/hs_%s/01/today.js";

    // 分页大小
    const RECS_PER_PAGE = 500;

    /**
     *  初始化股票列表(沪深A股)
     */
    public function actionInitList()
    {
        Yii::info("[stock/init-list][starts]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $count = 0;
        for ($i = 1; ;) {
            $url = sprintf(self::ALL_STOCK_URL_FORMAT, $i);

            $ret = HttpRequest::curlRequest($url, array(), "GET", "JSON", 3000);
            if (empty($ret)) {
                sleep(10);
                Yii::warning("Data is empty: " . $url, self::CATEGORY);
                continue;
            } else if (empty($ret['data'])) {
                Yii::warning("Init ends at : $i => " . $url, self::CATEGORY);
                break;
            }
            $pages = $ret['pages'];
            if ($i > $pages) {
                break;
            }
            foreach ($ret['data'] as $item) {
                $ws = explode(',', $item);
                $stockCode = $ws[1];
                $stockName = $ws[2];
                $stockType = 'hs';
                if (!StockBasic::isExisted($stockCode)) { // 不存在插入
                    $rec = new StockBasic();
                    $rec->insert_time = time();
                    $rec->stock_code = $stockCode;
                    $rec->stock_name = (!empty($stockName) ? $stockName : "null");
                    $rec->stock_type = (!empty($stockType) ? $stockType : "null");

                    if (!$rec->save()) {
                        // 数据库操作失败
                        Yii::warning("Mysql Insert Error : " . json_encode($rec->getErrors()), self::CATEGORY);
                    }
                    ++$count;

                } else {
                    Yii::info("rec existed : " . $stockCode . "=>" . $url, self::CATEGORY);
                }
            }
            Yii::info("[stock/init-list][ends][count=" . count($ret['data']) . "][url=" . $url . "]" . date('Y-m-d H:i:s'), self::CATEGORY);
            ++$i;
        }
        Yii::info("[stock/init-list][ends][count=" . $count . "]" . date('Y-m-d H:i:s'), self::CATEGORY);
    }

    /**
     * 初始化历史股价数据
     */
    public function actionInitHistory($page)
    {
        Yii::info("[stock/init-history][starts][$page]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $pageSize = 100;
        $allStock = StockBasic::find()->addOrderBy('id ASC')->offset($page * $pageSize)->limit($pageSize)->all();

        foreach ($allStock as $stock) {
            $stockName = $stock->getAttribute('stock_name');
            $stockCode = $stock->getAttribute('stock_code');
            $stockId = $stock->getAttribute('id');

            $logData = "[stock/init-history][ongoing][$page][" . date('Y-m-d H:i:s') . "][stockName=" . $stockName . "]";
            $logData .= "[stockCode=" . $stockCode . "]";

            $url = sprintf(self::STOCK_HISTORY_URL_FORMAT, $stockCode);
            $data = HttpRequest::curlRequest($url, array(), 'GET', '', 3000);

            $matches = array();
            $contentPattern = '/quotebridge_v2_line_hs_' . $stockCode . '_01_last\((.+)\).*/';
            if (preg_match_all($contentPattern, $data, $matches) == 1) {
                $content = $matches[1][0];
                $data = json_decode($content, true);
                if ($data['name'] != $stockName) {
                    $stockName = $data['name'];
                    $stock->setAttribute('stock_name', $data['name']);
                    if (!$stock->save()) {
                        // 数据库操作失败
                        Yii::warning("Mysql Insert Error : " . json_encode($stock->getErrors()), self::CATEGORY);
                    }
                }
                $total = $data['total'];
                $years = $data['year'];

                $lastPrice = 0;
                $tc = 0;
                foreach ($years as $year => $count) {
                    $stockInfos = array();
                    $dates = array();
                    $c = 0;
                    $url = sprintf(self::STOCK_YEAR_HISTORY_URL_FORMAT, $stockCode, $year);
                    $infos = HttpRequest::curlRequest($url, array(), 'GET', '', 3000);
                    $contentPattern = '/quotebridge_v2_line_hs_' . $stockCode . '_01_' . $year . '\((.+)\)/';
                    if (preg_match_all($contentPattern, $infos, $matches) == 1) {
                        $content = $matches[1][0];
                        $data = json_decode($content, true);
                        $data = $data['data'];
                        $lines = explode(';', $data);
                        foreach ($lines as $line) {
                            $items = explode(',', $line);
                            if (count($items) != 8) {
                                Yii::warning("Content Error: " . $stockName . "-" . $stockCode . "-" . $year, self::CATEGORY);
                                continue;
                            }

                            if (in_array($items[0], $dates)) {
                                continue;
                            }
                            $stockInfos[] = array(
                                $stockId,
                                time(),
                                $items[0],
                                $items[1],
                                $items[2],
                                $items[3],
                                $items[4],
                                $items[5],
                                $items[6],
                                (empty($items[7])) ? 0 : $items[7],
                                ($items[4] - $lastPrice),
                                (($lastPrice > 0) ? ($items[4] - $lastPrice) / $lastPrice * 100 : 0),
                            );

                            $lastPrice = $items[4];
                            ++$c;
                            ++$tc;
                            $dates[] = $items[0];
                        }
                        try {
                            StockDetail::getDb()->createCommand()->batchInsert(StockDetail::tableName(), ['stock_id',
                                'insert_time',
                                'date',
                                'open_price',
                                'highest_price',
                                'lowest_price',
                                'price',
                                'turnover_size',
                                'turnover_money',
                                'turnover_rate',
                                'advance_decline',
                                'advance_decline_rate',], $stockInfos)->execute();
                        } catch (Exception $e) {
                            Yii::warning($e->getMessage(), self::CATEGORY);
                        }

                    } else {
                        Yii::warning("Data Error : " . $stockName . "-" . $stockCode . "-" . $year, self::CATEGORY);
                    }
                    $logData .= "[" . $year . "=" . $c . ":" . $count . "]";
                    if ($c != $count) {
                        Yii::warning("Data Error : $c, $count" . $stockName . "-" . $stockCode . "-" . $year, self::CATEGORY);
                    }
                }

            } else {
                Yii::warning("Data Error : " . $stockName . "-" . $stockCode);
            }
            $logData .= "[" . $total . "=" . $tc . "]";
            Yii::info($logData, self::CATEGORY);
        }
        Yii::info("[stock/init-history][ends][$page]" . date('Y-m-d H:i:s'), self::CATEGORY);
    }

    /**
     * 抓取今天股价信息
     */
    public function actionSpiderTodayStockInfo()
    {
        Yii::info("[stock/spider-today-stock-info][starts]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $allStock = StockBasic::find()->addOrderBy('id ASC')->all();

        $count = 0;
        foreach ($allStock as $stock) {
            $stockName = $stock->getAttribute('stock_name');
            $stockCode = $stock->getAttribute('stock_code');
            $stockId = $stock->getAttribute('id');
            $logData = "[stock/spider-today-stock-info][" . date('Y-m-d H:i:s') . "][stockName=" . $stockName . "]";
            $logData .= "[stockCode=" . $stockCode . "]";
            $url = sprintf(self::STOCK_TODAY_URL_FORMAT, $stockCode);
            $data = HttpRequest::curlRequest($url, array(), 'GET', '', 3000);
            $matches = array();
            $contentPattern = '/quotebridge_v2_line_hs_' . $stockCode . '_01_today\((.+)\).*/';
            if (preg_match_all($contentPattern, $data, $matches) == 1) {
                $content = $matches[1][0];
                $data = json_decode($content, true);

                $key = sprintf('hs_%s', $stockCode);
                $items = $data[$key];
                // 判断是否更新股票简称(存在为空的case)
                if ($items['name'] != $stockName) {
                    $stock->setAttribute('stock_name', $data['name']);
                    $stockName = $data['name'];
                    if (!$stock->save()) {
                        // 数据库操作失败
                        Yii::warning("Mysql Insert Error : " . json_encode($stock->getErrors()), self::CATEGORY);
                    }
                }

                $stockDetail = new StockDetail();
                $stockDetail->stock_id = $stockId;
                $stockDetail->insert_time = time();
                $stockDetail->date = $items[1];
                $stockDetail->open_price = $items[7];
                $stockDetail->highest_price = $items[8];
                $stockDetail->lowest_price = $items[9];
                $stockDetail->price = $items[11];
                $stockDetail->turnover_size = $items[13];
                $stockDetail->turnover_money = $items[19];
                $stockDetail->turnover_rate = $items[1968584];
                $lastPrice = StockDetail::getStockPrice($stockId, date("Ymd", strtotime('-1 day')));
                $lastPrice = $lastPrice['price'];
                $stockDetail->advance_decline = ($items[11] - $lastPrice);
                $stockDetail->advance_decline_rate = (($lastPrice > 0) ? ($items[11] - $lastPrice) / $lastPrice * 100 : 0);
                try {
                    if (!$stockDetail->save()) {
                        // 数据库操作失败
                        Yii::warning("Mysql Insert Error : " . json_encode($stockDetail->getErrors()) . '[stockCode=' . $stockCode . "][date=" . $items[1] . "]", self::CATEGORY);
                    } else {
                        ++$count;
                        $logData .= '[data=' . json_encode($items) . ']';
                        Yii::info($logData);
                    }
                } catch (\Exception $e) {
                    Yii::warning("Mysql Insert Error : " . $e->getMessage(), self::CATEGORY);
                }
            } else {
                Yii::warning("Data Error : " . $stockName . "-" . $stockCode, self::CATEGORY);
            }
        }
        Yii::info("[stock/spider-today-stock-info][ends]" . date('Y-m-d H:i:s') . '[count=' . $count . ']', self::CATEGORY);
    }

    /**
     *  抓取今日评分
     */
    public function actionSpiderTodayStockScore()
    {
        $parser = new HtmlParser();
        Yii::info("[stock/spider-today-stock-score][starts]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $allStock = StockBasic::find()->addOrderBy('id ASC')->all();

        $count = 0;
        foreach ($allStock as $stock) {
            $stockCode = $stock->getAttribute('stock_code');
            $stockId = $stock->getAttribute('id');
            $url = 'http://vaserviece.10jqka.com.cn/advancediagnosestock/html/' . $stockCode . '/index.html';
            $parser->load_file($url);
            $res = $parser->find('ins[class=scoreall]');
            if (empty($res)) {
                Yii::warning("[stock/spider-today-stock-score]Spider Error : [url=" . $url . "]", self::CATEGORY);
                continue;
            }
            $lastScore = StockScore::getStockScore($stockId, date("Ymd", strtotime('-1 day')));
            $score = $res[0]->innertext;
            if ($lastScore['score'] != $score) {
                ++$count;
                $obj = new StockScore();
                $obj->stock_id = $stockId;
                $obj->date = date("Ymd");
                $obj->score = $score;
                $obj->create_time = time();
                $obj->update_time = time();
                if (!$obj->save()) {
                    // 数据库操作失败
                    Yii::warning("Mysql Insert Error : " . json_encode($obj->getErrors()), self::CATEGORY);
                }
            }
            Yii::info("[stock/spider-today-stock-score][stockCode=" . $stockCode . '][score=' . $score . "]", self::CATEGORY);
        }
        Yii::info("[stock/spider-today-stock-score][ends]" . date('Y-m-d H:i:s') . '[count=' . $count . ']', self::CATEGORY);

    }

    /**
     *  抓取大单实时占比(在流入中占比超过50%,则提醒)
     */
    public function actionSpiderRealFunds()
    {

        $urlFormat = "http://stockpage.10jqka.com.cn/spService/%s/Funds/realFunds";
        Yii::info("[stock/spider-real-funds][starts]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $allStock = StockBasic::find()->addOrderBy('id ASC')->all();

        // 默认时间间隔2小时;
        $mailInter = 2 * 3600;
        // 大单流入占比阈值(最低值)
        $bigOrderInflowMonitorRate = 0.5;
        // 当前涨幅阈值(最高值)
        $incMonitorRate = 2;
        // 最近平均换手率的阈值(最低值)
        $turnoverMonitorRate = 5;
        // 邮件记录, <stock_code:上一次邮件时间戳>
        $mailRec = array();

        // 最近换手率不低于阈值的股票代码
        $stockCodes = array();
        foreach ($allStock as $stock) {
            $stockCode = $stock->getAttribute('stock_code');
            if ($stockCode[0] == '3') {
                continue;
            }
            $stockName = $stock->getAttribute('stock_name');
            $stockId = $stock->getAttribute('id');

            // 获取最近5个交易日的换手率平均值
            $r = StockDetail::getNearestTurnoverRate($stockId, 5);
            // 最近换手率小于阈值,则终止
            if ($r < $turnoverMonitorRate) {
//                Yii::info("[stock/spider-real-funds][stock_name=" . $stockName . "][stock_code=" . $stockCode . "][nearest_turnover_rate=$r][status=10000]", self::CATEGORY);
                continue;
            } else {
                $stockCodes[] = $stockCode;
            }
        }
        Yii::info("[stock/spider-real-funds][nearest_turnover_rate > $turnoverMonitorRate][count=" . count($stockCodes) . "]", self::CATEGORY);


        while (true) {
            // 休市
            if (date('H') >= 15) {
                break;
            }
            // 不在交易时间
            if (!$this->isInTradingTime()) {
                sleep(60);
                continue;
            }
            foreach ($stockCodes as $stockCode) {
                $url = sprintf($urlFormat, $stockCode);
                $cc = 0;
                do {
                    $str = HttpRequest::curlRequest($url, array(), 'GET', '', 3000);
                    $data = json_decode($str, true);
                    if (!empty($data)) {
                        break;
                    }
                    sleep(rand(1, 5));
                    $cc++;
                } while ($cc < 3);

                // 总流入
                $inflowTotal = $data['title']['zlr'];
                // 大单流入
                $bigOrderInflow = 0;
                if (empty($data) || empty($inflowTotal)) {
                    Yii::warning("Data Error : " . $url, self::CATEGORY);
                    continue;
                }
                // 大单流出
                $bigOrderOutflow = 0;
                foreach ($data['flash'] as $item) {
                    if ($item['name'] == '大单流入') {
                        $bigOrderInflow = $item['sr'];
                    } else if ($item['name'] == '大单流出') {
                        $bigOrderOutflow = $item['sr'];
                    }
                }
                // 流出大于流入,则终止
                if ($bigOrderOutflow > $bigOrderInflow) {
                    Yii::info("[stock/spider-real-funds][stock_code=" . $stockCode . "][in=$bigOrderInflow][out=$bigOrderOutflow][status=10001][msg=out>in]", self::CATEGORY);
                    continue;
                }
                // 大单流入占比
                $rate = $bigOrderInflow / $inflowTotal;


                // 当前占比大于阈值,则进入
                if ($rate > $bigOrderInflowMonitorRate) {
                    // 抓取当前涨幅
                    $curUrlFormat = 'http://d.10jqka.com.cn/v2/realhead/hs_%s/last.js';
                    $url = sprintf($curUrlFormat, $stockCode);
                    $data = HttpRequest::curlRequest($url, array(), 'GET', '', 3000);
                    $data = str_replace('quotebridge_v2_realhead_hs_' . $stockCode . '_last(', '', $data);
                    $data = str_replace(')', '', $data);
                    $data = json_decode($data, true);
                    $incRate = $data['items'][199112];
                    // 当前涨幅大于阈值,则终止
                    if ($incRate > $incMonitorRate) {
                        Yii::info("[stock/spider-real-funds][stock_code=" . $stockCode . "][big-order-inflow-rate=" . $rate . "][inc_rate=" . $incRate . "][status=10002][msg=inc_rate>$incMonitorRate]", self::CATEGORY);
                        continue;
                    }

                    if (isset($mailRec[$stockCode])) {
                        if (time() - $mailRec[$stockCode] < $mailInter) {
                            Yii::info("[stock/spider-real-funds][stock_code=" . $stockCode . "][big-order-inflow-rate=" . $rate . "][inc_rate=" . $incRate . "][status=10003][msg=last_remind_in_" . ($incMonitorRate / 3600.0) . "_hour]", self::CATEGORY);
                            continue;
                        }
                    } else {
                        $mailRec[$stockCode] = time();
                    }
                    $mail = Yii::$app->mailer->compose();
                    $mail->setTo('18911984488@163.com');
                    $mail->setSubject('主力动向');
                    $mail->setFrom("stock@hxf.com");
                    $mail->setTextBody("[stock/spider-real-funds][stock_code=" . $stockCode . "][big-order-inflow-rate=" . $rate . "][inc_rate=" . $incRate . "]");
                    if (!$mail->send()) {
                        Yii::warning("Send Email Error", self::CATEGORY);
                    }
                    Yii::info("[stock/spider-real-funds][stock_code=" . $stockCode . "][big-order-inflow-rate=" . $rate . "][inc_rate=" . $incRate . "][status=10004][msg=remind_now]", self::CATEGORY);

                } else {
                    Yii::info("[stock/spider-real-funds][stock_code=" . $stockCode . "][big-order-inflow-rate=" . $rate . "][status=10005][msg=big-order-inflow-rate<$bigOrderInflowMonitorRate]", self::CATEGORY);
                }
            }
        }

        Yii::info("[stock/spider-real-funds][ends]" . date('Y-m-d H:i:s'), self::CATEGORY);

    }

    /**
     * 是否在交易时间段
     * @return bool
     */
    private function isInTradingTime()
    {
        $res = false;
        $hour = date('H');
        $minute = date('i');
        switch ($hour) {
            case 9:
                if ($minute >= 30) {
                    $res = true;
                }
                break;
            case 10:
                $res = true;
                break;
            case 11:
                if ($minute <= 30) {
                    $res = true;
                }
                break;
            case 13:
                $res = true;
                break;
            case 14:
                $res = true;
                break;
        }
        return $res;
    }

    /**
     * 抓取今日新股
     */
    public function actionSpiderNewStock()
    {
        Yii::info("[stock/spider-new-stock][starts]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $ret = HttpRequest::curlRequest("http://datainterface.eastmoney.com/EM_DataCenter/JS.aspx?type=NS&sty=NSST&st=12&sr=-1&p=1&ps=50", array(), 'GET', '', 3000);

        $ret = str_replace("([", "", $ret);
        $ret = str_replace("])", "", $ret);

        $ret = "\$ret=array(" . $ret . ");";

        eval($ret);

        foreach ($ret as $item) {
            $ws = explode(",", $item);
            $name = $ws[3];
            $code = $ws[5];
            $date = $ws[11];
            $now = date("Y-m-d");
            if ($now == $date) {
                $mail = Yii::$app->mailer->compose();
                $mail->setTo('18911984488@163.com');
                $mail->setSubject('今日新股');
                $mail->setFrom("stock@hxf.com");
                $mail->setTextBody("[stock/spider-new-stock]" . "$name-$code-$date");
                if (!$mail->send()) {
                    Yii::warning("Send Email Error", self::CATEGORY);
                }
            }
        }
        Yii::info("[stock/spider-new-stock][ends]" . date('Y-m-d H:i:s'), self::CATEGORY);
    }

    /**
     * 抓取咨询[新浪]
     */
    public function actionSpiderFeed($page)
    {
        Yii::info("[stock/spider-feed][starts][$page]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $data = array();
        $allStock = StockBasic::find()->addOrderBy('id ASC')->offset($page * self::RECS_PER_PAGE)->limit(self::RECS_PER_PAGE)->all();

        $parser = new HtmlParser();
        $urlFormat = 'http://vip.stock.finance.sina.com.cn/corp/go.php/vCB_AllNewsStock/symbol/%s.phtml';
        $count = 0;

        foreach ($allStock as $stock) {
            $stockType = $stock->getAttribute('stock_type');
            $stockCode = $stock->getAttribute('stock_code');
            $stockName = $stock->getAttribute('stock_name');
            $stockId = $stock->getAttribute('id');
            if ($stockType == 'sza') {
                $url = sprintf($urlFormat, 'sz' . $stockCode);
            } else if ($stockType == 'sha') {
                $url = sprintf($urlFormat, 'sh' . $stockCode);
            } else {
                continue;
            }
            $parser->load_file($url);
            $res = $parser->find('div[class=datelist]');
            if (empty($res)) {
                Yii::warning("content error[url=$url]", self::CATEGORY);
                continue;
            }
            $content = $res[0]->innertext;
            $content = str_replace('&nbsp;', ' ', $content);
            $recs = explode('<br>', $content);
            $pattern = '/.*([0-9]{4}\-[0-9]{2}\-[0-9]{2} [0-9]{2}:[0-9]{2}).*href=\'(.*)\'>(.*)<\/a>/';
            foreach ($recs as $rec) {
                $matches = array();
                $rec = trim($rec);
                if ($rec == '</ul>') {
                    break;
                }
                preg_match_all($pattern, $rec, $matches);
                if (count($matches) != 4) {
                    Yii::warning("[stock/spider-feed][content format error][rec=$rec]" . date('Y-m-d H:i:s'), self::CATEGORY);
                    continue;
                }
                $contentDate = trim($matches[1][0]);
                $contentUrl = trim($matches[2][0]);
                $contentTitle = trim($matches[3][0]);
                if (empty($contentTitle)) {
                    $contentTitle = '-';
                }

                $ts = strtotime($contentDate);
                if ($ts < 1480521600) {
                    Yii::info("[stock/spider-feed][rec older than 2016-12][date=$contentDate]" . date('Y-m-d H:i:s'), self::CATEGORY);
                    break;
                }
                $key = $contentDate . ':' . $contentTitle . ':' . $contentUrl;
                $keyId = crc32($key);
                if (!StockFeed::isExisted($stockId, $keyId)) {
                    $data[] = array(
                        'date' => $contentDate,
                        'title' => $contentTitle,
                        'url' => $contentUrl,
                        'code' => $stockCode,
                        'name' => $stockName,
                    );
                    $obj = new StockFeed();
                    $obj->url = $contentUrl;
                    $obj->title = $contentTitle;
                    $obj->date = $contentDate;
                    $obj->stock_id = $stockId;
                    $obj->crc32_value = $keyId;
                    $obj->create_time = time();
                    if (!$obj->save()) {
                        // 数据库操作失败
                        Yii::warning("Mysql Insert Error :[rec=$rec] " . json_encode($obj->getErrors()), self::CATEGORY);
                    }
                } else {
                    Yii::info("[stock/spider-feed][rec existed][key=$key]" . date('Y-m-d H:i:s'), self::CATEGORY);
                    break;
                }
            }
            Yii::info("[stock/spider-feed][$page][ongoing][stockCode=$stockCode]" . date('Y-m-d H:i:s'), self::CATEGORY);
        }
        $data = $this->_filterContent($data);
        if (count($data) > 0) {
            $this->_mailContent($data);
        }
        Yii::info("[stock/spider-feed][ends][$page]" . date('Y-m-d H:i:s'), self::CATEGORY);
    }

    /**
     * 抓取咨询[百度]
     */
    public function actionSpiderFeedBaidu($page)
    {
        Yii::info("[stock/spider-feed-baidu][starts][$page]" . date('Y-m-d H:i:s'), self::CATEGORY);
        $data = array();

        $allStock = StockBasic::find()->addOrderBy('id ASC')->offset($page * self::RECS_PER_PAGE)->limit(self::RECS_PER_PAGE)->all();

        $parser = new HtmlParser();
        $urlFormat = 'https://gupiao.baidu.com/stock/%s.html?from=aladingpc';
        $count = 0;

        foreach ($allStock as $stock) {
            $stockType = $stock->getAttribute('stock_type');
            $stockCode = $stock->getAttribute('stock_code');
            $stockName = $stock->getAttribute('stock_name');
            $stockId = $stock->getAttribute('id');
            if ($stockType == 'sza') {
                $url = sprintf($urlFormat, 'sz' . $stockCode);
            } else if ($stockType == 'sha') {
                $url = sprintf($urlFormat, 'sh' . $stockCode);
            } else {
                continue;
            }
            $parser->load_file($url);
            $res = $parser->find('div[class=stock-related-list]');
            if (empty($res)) {
                Yii::warning("content error[url=$url]", self::CATEGORY);
                continue;
            }
            $content = $res[0];
            $lis = $content->find('li[class=row]');
            if (empty($lis)) {
                Yii::warning("content li error[url=$url]", self::CATEGORY);
                continue;
            }
            $baseUrl = dirname($url);
            foreach ($lis as $li) {
                $as = $li->find('a');
                if (empty($as)) {
                    Yii::warning("content li a error[url=$url]", self::CATEGORY);
                    continue;
                }
                foreach ($as as $a) {
                    $contentUrl = $baseUrl . '/..' . $a->href;
                    $contentTitle = trim($a->plaintext);
                }
                $bs = $li->find('div[class=bottom]');;
                if (empty($bs)) {
                    Yii::warning("content li bottom error[url=$url]", self::CATEGORY);
                    continue;
                }
                $b = $bs[0];
                $bls = $b->find('li');
                if (empty($bls)) {
                    Yii::warning("content li bottom li error[url=$url]", self::CATEGORY);
                    continue;
                }
                $contentDate = $bls[0]->innertext;

                $ts = strtotime($contentDate);
                if ($ts < 1480521600) {
                    Yii::info("[stock/spider-feed-baidu][rec older than 2016-12][date=$contentDate]" . date('Y-m-d H:i:s'), self::CATEGORY);
                    break;
                }

                $key = $contentDate . ':' . $contentTitle . ':' . $contentUrl;
                $keyId = crc32($key);
                if (!StockFeed::isExisted($stockId, $keyId)) {
                    $data[] = array(
                        'code' => $stockCode,
                        'date' => $contentDate,
                        'title' => $contentTitle,
                        'url' => $contentUrl,
                        'name' => $stockName,
                    );
                    $obj = new StockFeed();
                    $obj->url = $contentUrl;
                    $obj->title = $contentTitle;
                    $obj->date = $contentDate;
                    $obj->stock_id = $stockId;
                    $obj->crc32_value = $keyId;
                    $obj->create_time = time();
                    if (!$obj->save()) {
                        // 数据库操作失败
                        Yii::warning("Mysql Insert Error :[rec=$li] " . json_encode($obj->getErrors()), self::CATEGORY);
                    }
                } else {
                    Yii::info("[stock/spider-feed-baidu][rec existed][key=$key]" . date('Y-m-d H:i:s'), self::CATEGORY);
                    break;
                }
            }
            Yii::info("[stock/spider-feed-baidu][$page][ongoing][stockCode=$stockCode]" . date('Y-m-d H:i:s'), self::CATEGORY);
        }
        $data = $this->_filterContent($data);
        if (count($data) > 0) {
            $this->_mailContent($data);
        }
        Yii::info("[stock/spider-feed-baidu][ends][$page]" . date('Y-m-d H:i:s'), self::CATEGORY);
    }

    /**
     * @param $data
     * @return array
     */
    private function _filterContent($data)
    {
        // 过滤非工作时间的数据
        $ret = array();
        foreach ($data as $rec) {
            $flag = true;
            // 时间过滤
            $d = $rec['date'];
            $dt = explode(' ', $d);
            if (count($dt) < 2) {
                continue;
            }
            $time = $dt[1];
            $hm = explode(':', $time);
            if (count($hm) < 2) {
                continue;
            }
            $h = $hm[0];
            $m = $hm[1];
            if ($h == 8 || $h == 12) {
                //
            } else if ($h == 9 && $h < 30) {
                //
            } else if ($h == 13 && $h < 5) {
                //
            } else {
                $flag = false;
            }

            if (!$flag) {
                continue;
            }

            // 标题过滤
            $title = $rec['title'];
            $name = $rec['name'];
            if (strpos($title, $name) !== false) {
                $ret[] = $rec;
            }
        }
        return $ret;
    }

    /**
     * @param $data
     */
    private function _mailContent($data)
    {
        if (!$this->_inWorkTime()) {
            return;
        }
        Yii::info("[send email][count=" . count($data) . ']' . date('Y-m-d H:i:s'), self::CATEGORY);
        $htmlBody = '<table border=1><tr><th>代码</th><th>时间</th><th>标题</th><th>地址</th></tr>';
        foreach ($data as $rec) {
            $htmlBody .= '<tr><td>' . $rec['code'] . '</td><td>' . $rec['date'] . '</td><td>' . $rec['title'] . '</td><td>' . $rec['url'] . '</td></tr>';
        }
        $htmlBody .= '</table>';
        $ret = \Yii::$app->mailer->compose()
            ->setFrom('stock@hxf.com')
            ->setTo('hexuefei_mail@163.com')
            ->setSubject('最新资讯')
            ->setHtmlBody($htmlBody)
            ->send();
    }

    /**
     * @return bool
     */
    private function _inWorkTime()
    {
        return false;
        $res = false;
        $hour = date('H');
        $minute = date('i');
        if ($hour == 8 || $hour == 12) {
            $res = true;
        } else if ($hour == 9 && $minute < 30) {
            $res = true;
        } else if ($hour == 13 && $minute < 5) {
            $res = true;
        }

        return $res;
    }
}
