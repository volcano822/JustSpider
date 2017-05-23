<?php
namespace console\controllers;

use common\lib\HtmlParser;
use common\lib\HttpRequest;
use common\models\HouseChengjiao;
use common\models\HouseVillage;
use Yii;
use yii\db\Exception;

/**
 * House controller
 */
class HouseController extends \yii\console\Controller
{
    const CATEGORY = 'house';

    /**
     * @param $content
     * @param string $subject
     */
    private
    function sendWfMail($content, $subject = '')
    {
        $mail = Yii::$app->mailer->compose();
        $mail->setTo('hexuefei_mail@163.com');
        if (empty($subject)) {
            $mail->setSubject('抓取失败');
        } else {
            $mail->setSubject($subject);
        }

        $mail->setFrom("spider@hxf.com");
        $mail->setTextBody($content);
        if (!$mail->send()) {
            Yii::warning("Send Email Error", self::CATEGORY);
        }
    }

    /**
     * 抓取小区成交数据
     * @param $page
     * @param int $pageSize
     */
    function actionSpiderLjVillage($page, $pageSize = 10)
    {
        $allVillageInfos = HouseVillage::getAllVillageInfos($page, $pageSize);

        foreach ($allVillageInfos as $info) {
            try {
                $villageCode = $info['village_code'];
                $village = $info['name'];
                if (!empty($villageCode)) {
                    $this->spiderVillageDealPage($village, $villageCode);
                }
            } catch (\yii\base\Exception $e) {
                $content = "[house/spider-lj-village][village=$village]" . $e->getMessage();
                Yii::warning($content, self::CATEGORY);
                $this->sendWfMail($content);
            }

        }
    }

    /**
     * 抓取小区成交页数据
     * @param $url
     * @return null|string
     */
    function spiderVillageDealPage($village, $villageCode)
    {
        $urlFormat = 'http://bj.lianjia.com/chengjiao/pg%d' . $villageCode . '/';
        for ($i = 1; ; ++$i) {
            $url = sprintf($urlFormat, $i);
            if (!$this->spiderDealPage($url)) {
                break;
            }
        }
        HouseVillage::updateStatus($village);

    }


    /**
     * 抓取链家每日成交数据
     */
    function actionSpiderLj()
    {
        $urlFormat = 'http://bj.lianjia.com/chengjiao/pg%d';
        for ($i = 1; $i <= 100; ++$i) {
            $url = sprintf($urlFormat, $i);
            if (!$this->spiderDealPage($url)) {
                break;
            }
        }
    }

    /**
     * 抓取成交页数据
     * @param $url
     * @return bool
     */
    function spiderDealPage($url)
    {
        $parser = new HtmlParser();

        $count = 0;
        try {
            if (!$parser->load_file($url)) {
                throw new \yii\base\Exception("[load_file Error]");
            }
            $content = $this->resolveDomNode($parser, 'ul[class=listContent]', 1);
            if (empty($content)) {
                throw new \yii\base\Exception("[listContent Error]");
            }
            $lis = $this->resolveDomNode($content, 'li');
            if (empty($lis)) {
                return false;
//                throw new \yii\base\Exception("[lis Error]");
            }

            $count = count($lis);
            foreach ($lis as $li) {
                try {
                    $rec = new HouseChengjiao();
                    $rec->insert_time = time();
                    $info = $this->resolveDomNode($li, 'div[class=info]', 1);
                    if (empty($info)) {
                        throw new \yii\base\Exception("[li_info error]");
                    }
                    $this->resolveTitle($info, $rec);
                    $this->resolveAddress($info, $rec);
                    $this->resolveFlood($info, $rec);
                    $this->resolveDealHouseInfo($info, $rec);

                    if (HouseChengjiao::isExisted($rec->house_code)) {
                        $content = "[house/spider-lj][existed=true][house_code=" . $rec->house_code . "]";
                        Yii::info($content, self::CATEGORY);
                        continue;
                    }
                    try {
                        $rec->save();
                    } catch (Exception $e) {
                        $content = "[house/spider-lj]Mysql Insert Error : " . $e->getMessage();
                        // 数据库操作失败
                        Yii::warning($content, self::CATEGORY);
                        $this->sendWfMail($content);
                    }

                    // 是否为新小区
                    if (!HouseVillage::isExisted($rec->village)) {
                        $ret = HttpRequest::curlRequest('http://bj.lianjia.com/api/headerSearch?channel=chengjiao&cityId=110000&keyword=' . $rec->village,
                            array(), 'GET', 'JSON');
                        $newVillage = new HouseVillage();
                        $newVillage->one_house_code = $rec->house_code;
                        $newVillage->name = $rec->village;
                        $newVillage->status = 0;
                        if (!empty($ret) && !empty($ret['data']['result'])) {
                            foreach ($ret['data']['result'] as $item) {
                                if ($item['title'] == $rec->village) {
                                    $newVillage->village_code = 'c' . $item['communityId'];
                                }
                            }
                        }
                        try {
                            $newVillage->save();
                            ++$count;
                        } catch (Exception $e) {
                            $content = "[house/spider-lj]Mysql Insert Error : " . $e->getMessage();
                            Yii::warning($content, self::CATEGORY);
                        }
                    }
                } catch (\yii\base\Exception $e) {
                    $content = "[house/spider-lj][url=$url]" . $e->getMessage();
                    Yii::warning($content, self::CATEGORY);
                    $this->sendWfMail($content);
                }

            }
        } catch (\yii\base\Exception $e) {
            $content = "[house/spider-lj][url=$url]" . $e->getMessage();
            Yii::warning($content, self::CATEGORY);
            $this->sendWfMail($content);
        }

        Yii::info("[house/spider-lj][counts=$count][url=$url]", self::CATEGORY);
        return true;
    }

    /**
     * 解析标题部分
     * @param $node
     * @param $rec
     * @throws \yii\base\Exception
     */
    private function resolveTitle($node, &$rec)
    {
        $title = $this->resolveDomNode($node, 'div[class=title]', 1);
        if (empty($title)) {
            throw new \yii\base\Exception("[title error]");
        }
        $a = $this->resolveDomNode($title, 'a', 1);
        if (empty($a)) {
            throw new \yii\base\Exception("[title_a error]");
        }
        $title = $a->innertext;
        $rec->title = $title;
        $items = explode(' ', $title);
        $rec->village = $items[0];

        $pattern = '/chengjiao\/([a-zA-Z0-9]+)\.html/';
        $matches = array();
        preg_match($pattern, $a->href, $matches);
        if (count($matches) < 2) {
            throw new \yii\base\Exception("[houseId Error]");
        }
        $rec->house_code = $matches[1];
    }

    /**
     *  解析地址部分
     * @param $node
     * @param $rec
     * @return bool
     * @throws \yii\base\Exception
     */
    private function resolveAddress($node, &$rec)
    {
        $address = $this->resolveDomNode($node, 'div[class=address]', 1);
        if (empty($address)) {
            throw new \yii\base\Exception("[address error]");
        }
        // 房子信息
        $houseInfo = $this->resolveDomNode($address, 'div[class=houseInfo]', 1);
        if (empty($houseInfo)) {
            throw new \yii\base\Exception("[address_houseInfo error]");
        }
        $content = strip_tags($houseInfo->innertext);
        $rec->house_info = str_replace('&nbsp;', '', $content);

        // 成交日期
        $dealDate = $this->resolveDomNode($address, 'div[class=dealDate]', 1);
        if (empty($dealDate)) {
            throw new \yii\base\Exception("[address_dealDate error]");
        }
        $dealDate = $dealDate->innertext;
        if (strlen($dealDate) == 7) {
            $dealDate .= '.01';
        }
        $rec->deal_date = $dealDate;

        //总价
        $price = $this->resolveDomNode($address, 'span[class=number]', 1);
        $rec->price = $price->innertext;
        if (empty($price)) {
            $rec->price = 0;
            // throw new \yii\base\Exception("[address_price error]");
        }


    }

    /**
     * 解析楼层部分
     * @param $node
     * @param $rec
     * @throws \yii\base\Exception
     */
    private function resolveFlood($node, &$rec)
    {
        $flood = $this->resolveDomNode($node, 'div[class=flood]', 1);
        if (empty($flood)) {
            throw new \yii\base\Exception("[flood error]");
        }
        // 位置信息
        $positionInfo = $this->resolveDomNode($flood, 'div[class=positionInfo]', 1);
        if (empty($positionInfo)) {
            throw new \yii\base\Exception("[flood_positionInfo error]");
        }
        $content = strip_tags($positionInfo->innertext);
        $rec->position_info = str_replace('&nbsp;', '', $content);

        // 数据来源
        $src = $this->resolveDomNode($flood, 'div[class=source]', 1);
        if (empty($src)) {
            throw new \yii\base\Exception("[flood_source error]");
        }
        $rec->src = $src->innertext;

        // 单价
        $unitPrice = $this->resolveDomNode($flood, 'span[class=number]', 1);
        $rec->unit_price = $unitPrice->innertext;
        if (empty($unitPrice)) {
//            throw new \yii\base\Exception("[flood_unitPrice error]");
            $rec->unit_price = 0;
        }
    }

    /**
     * 解析成交房源信息
     * @param $node
     * @param $rec
     */
    private function resolveDealHouseInfo($node, &$rec)
    {
        $dealHouseInfo = $this->resolveDomNode($node, 'span[class=dealHouseTxt]', 1);
        if (empty($dealHouseInfo)) {
            $rec->ext = '';
            return;
        }
        $contents = $this->resolveDomNode($dealHouseInfo, 'span');
        $txt = ' ';
        foreach ($contents as $content) {
            $txt = $txt . strip_tags($content->innertext) . '|';
        }
        $rec->ext = substr($txt, 0, strlen($txt) - 1);
    }

    /**
     * 解析DOM结构，查找指定子节点
     * @param $domNode 父节点
     * @param $key 查找关键字
     * @param int $num 返回结果数，默认为0，即全返回；非1，返回节点数组；为1，返回节点元素
     * @return mixed
     */
    private function resolveDomNode($domNode, $key, $num = 0)
    {
        $results = $domNode->find($key);
        if ($num != 0 && count($results) < $num) {
            $content = "[house/spider-lj]resolveDomNode Error : [expectNum=" . $num . "][got=" . count($results) . "][key=$key][content=" . $domNode->innertext . "]";
            Yii::warning($content, self::CATEGORY);
            return null;
        }
        if ($num == 1) {
            return $results[0];
        } else if ($num == 0) {
            return $results;
        }
        return array_slice($results, 0, $num);
    }

    /**
     * 抓取链家小区数据
     */
    public function actionSpiderLjVillageData()
    {
        $urlFormat = 'http://bj.lianjia.com/xiaoqu/pg%d';
        for ($i = 1; $i <= 100; ++$i) {
            $url = sprintf($urlFormat, $i);
            if (!$this->spiderVillagePage($url)) {
                break;
            }
        }
    }

    /**
     * 抓取小区页数据
     * @param $url
     * @return bool
     */
    function spiderVillagePage($url)
    {
        $parser = new HtmlParser();
        $count = 0;
        try {
            if (!$parser->load_file($url)) {
                throw new \yii\base\Exception("[load_file Error]");
            }
            $lis = $this->resolveDomNode($parser, 'li[class=clear xiaoquListItem]');
            if (empty($lis)) {
                throw new \yii\base\Exception("[lis Error]");
            }
            $count = count($lis);
            foreach ($lis as $li) {
                try {
                    $info = $this->resolveDomNode($li, 'div[class=info]', 1);
                    if (empty($info)) {
                        throw new \yii\base\Exception("[li_info error]");
                    }
                    $title = $this->resolveDomNode($info, 'div[class=title]', 1);
                    if (empty($title)) {
                        throw new \yii\base\Exception("[li_info_title error]");
                    }
                    $a = $this->resolveDomNode($title, 'a', 1);
                    if (empty($a)) {
                        throw new \yii\base\Exception("[li_info_title_a error]");
                    }
                    $pattern = '/xiaoqu\/([0-9A-Za-z]+)\//';
                    $href = $a->href;
                    $matches = array();
                    preg_match($pattern, $href, $matches);
                    if (count($matches) < 2) {
                        throw new \yii\base\Exception("[li_info_title_a code pattern error]");
                    }
                    $villageCode = 'c' . $matches[1];

                    $name = $a->innertext;
                    // 是否为新小区
                    if (!HouseVillage::isExisted($name)) {
                        $newVillage = new HouseVillage();
                        $newVillage->one_house_code = 0;
                        $newVillage->name = $name;
                        $newVillage->status = 0;
                        $newVillage->village_code = $villageCode;
                        try {
                            $newVillage->save();
                            ++$count;
                        } catch (Exception $e) {
                            $content = "[house/spider-lj-village-data]Mysql Insert Error : " . $e->getMessage();
                            Yii::warning($content, self::CATEGORY);
                        }
                    } else {
                        HouseVillage::updateVillageCode($name, $villageCode);
                    }
                } catch (\yii\base\Exception $e) {
                    $content = "[house/spider-lj-village-data][url=$url]" . $e->getMessage();
                    Yii::warning($content, self::CATEGORY);
                    $this->sendWfMail($content);
                }

            }
        } catch (\yii\base\Exception $e) {
            $content = "[house/spider-lj-village-data][url=$url]" . $e->getMessage();
            Yii::warning($content, self::CATEGORY);
            $this->sendWfMail($content);
        }

        Yii::info("[house/spider-lj-village-data][counts=$count][url=$url]", self::CATEGORY);
        return true;
    }
}
