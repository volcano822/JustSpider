<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "house".
 *
 * @property integer $id
 * @property string $name
 * @property string $state
 * @property string $company
 * @property string $location
 * @property integer $count
 * @property string $area
 * @property string $avg_price
 * @property string $apply_url
 * @property string $hot_line
 * @property integer $update_time
 * @property integer $create_time
 */
class House extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'house';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['update_time', 'create_time'], 'integer'],
            [['name', 'count', 'state', 'company', 'location', 'area', 'avg_price', 'apply_url', 'hot_line'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'state' => 'State',
            'company' => 'Company',
            'location' => 'Location',
            'count' => 'Count',
            'area' => 'Area',
            'avg_price' => 'Avg Price',
            'apply_url' => 'Apply Url',
            'hot_line' => 'Hot Line',
            'update_time' => 'Update Time',
            'create_time' => 'Create Time',
        ];
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
  
}
