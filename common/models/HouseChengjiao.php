<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "house_list".
 *
 * @property string $house_code
 * @property string $title
 * @property string $house_info
 * @property string $deal_date
 * @property integer $price
 * @property string $position_info
 * @property string $src
 * @property string $unit_price
 * @property integer $insert_time
 * @property string $ext
 * @property string $village
 */
class HouseChengjiao extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'house_chengjiao';
    }

    /**
     * 获取所有小区信息
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAllVillageInfos($page, $pageSize)
    {
        return self::find()->groupBy('village')->select(array('village', 'house_code'))->orderBy('village desc')->limit($pageSize)->offset($page * $pageSize)->asArray()->all();
    }

    /**
     * @param $houseCode
     * @return bool|null|static
     */
    public static function isExisted($houseCode)
    {
        $condition = [
            'house_code' => $houseCode,
        ];
        $ret = self::findOne($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * @param $name
     * @return bool|null|static
     */
    public static function isVillageExisted($village)
    {
        $condition = [
            'village' => $village,
        ];
        $ret = self::findOne($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

}
