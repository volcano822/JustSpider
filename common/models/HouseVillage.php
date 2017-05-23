<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "house_village".
 *
 * @property integer $id
 * @property string $name
 * @property integer $status
 * @property string $one_house_code
 * @property string $village_code
 */
class HouseVillage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'house_village';
    }

    /**
     * @param $name
     * @return bool|null|static
     */
    public static function isExisted($name)
    {
        $condition = [
            'name' => $name,
        ];
        $ret = self::findOne($condition);
        if (empty($ret)) {
            return false;
        }
        return $ret;
    }

    /**
     * @param $name
     * @return int
     */
    public static function updateStatus($name)
    {
        $condition = [
            'name' => $name,
        ];
        $attrs = [
            'status' => 1,
        ];
        return self::updateAll($attrs, $condition);
    }

    /**
     * @param $name
     * @return int
     */
    public static function updateVillageCode($name, $villageCode)
    {
        $condition = [
            'name' => $name,
        ];
        $attrs = [
            'village_code' => $villageCode,
        ];
        return self::updateAll($attrs, $condition);
    }

    /**
     * 获取所有小区信息
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getAllVillageInfos($page, $pageSize)
    {
        $condition = [
            'status' => 0,
        ];
        return self::find()->where($condition)->orderBy('id asc')->select(array('name', 'one_house_code', 'village_code',))->limit($pageSize)->offset($page * $pageSize)->asArray()->all();
    }
}
