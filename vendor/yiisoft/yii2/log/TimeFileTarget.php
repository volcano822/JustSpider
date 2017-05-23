<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\log;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\FileHelper;

/**
 * 按“logFile_{DATA}_{HOUR}”滚动的日志
 * 
 * 用于Minos平台采集日志用
 *
 * @author liangdong01 <liangdong01@baidu.com>
 * @since 2.0
 */
class TimeFileTarget extends Target
{
    /**
     * @var string log file path or path alias. If not set, it will use the "@runtime/logs/app.log" file.
     * The directory containing the log files will be automatically created if not existing.
     */
    public $logFile;
    /**
     * @var integer the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;


    /**
     * Initializes the route.
     * This method is invoked after the route is created by the route manager.
     * @param
     * @return
     */
    public function init()
    {
        parent::init();
        if ($this->logFile === null) {
            $this->logFile = Yii::$app->getRuntimePath() . '/logs/app.log';
        } else {
            $this->logFile = Yii::getAlias($this->logFile);
        }
        $logPath = dirname($this->logFile);
        if (!is_dir($logPath)) {
            FileHelper::createDirectory($logPath, $this->dirMode, true);
        }
    }

    /**
     * Writes log messages to a file.
     * @throws InvalidConfigException if unable to open the log file for writing
     */
    public function export()
    {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        
        $curFileName = $this->logFile .'.'. date('YmdH');
        if (($fp = @fopen($curFileName, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$curFileName}");
        }
		@fwrite($fp, $text);
		@fclose($fp);
    }
}
