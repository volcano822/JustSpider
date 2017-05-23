<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "stock_basic".
 *
 * @property integer $id
 * @property string $stock_code
 * @property string $stock_name
 * @property integer $insert_time
 */
class StockBasic extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'stock_basic';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['stock_code', 'stock_name', 'insert_time','stock_type',], 'required'],
            [['insert_time'], 'integer'],
            [['stock_code'], 'string', 'max' => 10],
            [['stock_name'], 'string', 'max' => 32],
            [['stock_code'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stock_code' => 'Stock Code',
            'stock_name' => 'Stock Name',
            'insert_time' => 'Insert Time',
        ];
    }
    public static function isExisted($stockCode) {
        $condition = [
            'stock_code' => $stockCode,
        ];
        $ret = self::findOne($condition);
        if(empty($ret)) {
            return false;
        }
        return true;
    }
}
