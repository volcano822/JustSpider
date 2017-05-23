<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "stock_detail".
 *
 * @property integer $id
 * @property integer $stock_id
 * @property string $date
 * @property double $open_price
 * @property double $highest_price
 * @property double $lowest_price
 * @property double $price
 * @property integer $turnover_size
 * @property integer $turnover_money
 * @property double $turnover_rate
 * @property double $advance_decline
 * @property integer $advance_decline_rate
 * @property integer $insert_time
 */
class StockDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'stock_detail';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['stock_id', 'date', 'open_price', 'highest_price', 'lowest_price', 'price', 'turnover_size', 'turnover_money', 'turnover_rate', 'advance_decline', 'advance_decline_rate', 'insert_time'], 'required'],
            [['stock_id', 'turnover_size',   'insert_time'], 'integer'],
            [['open_price', 'highest_price', 'turnover_money','advance_decline_rate','lowest_price', 'price', 'turnover_rate', 'advance_decline'], 'number'],
            [['date'], 'string', 'max' => 8],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stock_id' => 'Stock ID',
            'date' => 'Date',
            'open_price' => 'Open Price',
            'highest_price' => 'Highest Price',
            'lowest_price' => 'Lowest Price',
            'price' => 'Price',
            'turnover_size' => 'Turnover Size',
            'turnover_money' => 'Turnover Money',
            'turnover_rate' => 'Turnover Rate',
            'advance_decline' => 'Advance Decline',
            'advance_decline_rate' => 'Advance Decline Rate',
            'insert_time' => 'Insert Time',
        ];
    }

    /**
     * 获取指定日期前(含当前日期)的最后股价
     * @param $stockId
     * @param $d
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getStockPrice($stockId, $d) {
        $condition = [
            'stock_id' => $stockId,
        ];
        return self::find()->where($condition)->andWhere(['<=', 'date', $d])->select('price')->orderBy("date DESC")->limit(1)->asArray()->one();
    }
    /**
     * 获取近期换手率的平均值
     * @param $stockId
     * @param $days, 最近N天内的平均值
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getNearestTurnoverRate($stockId,$days = 5) {
        $condition = [
            'stock_id' => $stockId,
        ];
        $dbRet =  self::find()->select('turnover_rate')->where($condition)->orderBy("date DESC")->limit($days)->asArray()->all();
        $total = 0;
        if(empty($dbRet)) {
            return 0;
        }
        foreach($dbRet as $item) {
           $total += $item['turnover_rate'];
        }
        return $total / count($dbRet);
    }
}
