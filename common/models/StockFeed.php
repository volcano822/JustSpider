<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "stock_feed".
 *
 * @property integer $id
 * @property string $stock_id
 * @property string $date
 * @property integer $title
 * @property integer $url
 * @property integer $crc32_value
 * @property integer $create_time
 */
class StockFeed extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'stock_feed';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['stock_id', 'date', 'url', 'title', 'crc32_value',], 'required'],
            [['create_time', 'crc32_value',], 'integer'],
        ];
    }

    /**
     * @param $stockId
     * @param $crc32Value
     * @return bool
     */
    public static function isExisted($stockId, $crc32Value)
    {
        $condition = [
            'stock_id' => $stockId,
            'crc32_value' => $crc32Value,
        ];
        $ret = self::findOne($condition);
        if (empty($ret)) {
            return false;
        }
        return true;
    }
}
