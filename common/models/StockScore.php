<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "stock_score".
 *
 * @property integer $id
 * @property integer $stock_id
 * @property string $date
 * @property double $score
 */
class StockScore extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     * @return string
     */
    public static function tableName()
    {
        return 'stock_score';
    }

    /**
     * @inheritdoc
     * @return array
     */
    public function rules()
    {
        return [
            [['stock_id', 'date', 'score', 'create_time', 'update_time',], 'required'],
            [['stock_id', 'create_time', 'update_time',], 'integer'],
            [['score'], 'number'],
            [['date'], 'string', 'max' => 8],
        ];
    }

    /**
     * @inheritdoc
     * @return arra
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stock_id' => 'Stock ID',
            'date' => 'Date',
            'score' => 'Score',
        ];
    }

    /**
     * 获取指定日期前(含当前日期)的最后得分
     * @param $stockId
     * @param $d
     * @return array|null|\yii\db\ActiveRecord
     */
    public static function getStockScore($stockId, $d)
    {
        $condition = [
            'stock_id' => $stockId,
        ];
        return self::find()->where($condition)->andWhere(['<=', 'date', $d])->select('score')->orderBy("date DESC")->limit(1)->asArray()->one();
    }
}
