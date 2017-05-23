<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "house_list".
 *
 * @property integer $id
 * @property string $verify_num
 * @property string $county
 * @property string $village
 * @property string $type
 * @property integer $area
 * @property string $price
 * @property string $publish_insitution
 * @property string $date
 * @property string $house_id
 * @property integer $insert_time
 */
class HouseList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'house_list';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['house_id', 'verify_num'], 'integer'],
            [['county', 'village', 'type', 'area', 'price', 'date', 'publish_insitution',], 'string', 'max' => 255],
        ];
    }

    /**
     * @param $verifyNum
     * @return bool|null|static
     */
    public static function isExisted($verifyNum)
    {
        $condition = [
            'verify_num' => $verifyNum,
        ];
        $ret = self::findOne($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

}
