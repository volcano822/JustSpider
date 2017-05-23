--------------------------------------
#股票&&北京二手房成交（链家）的数据抓取
--------------------------------------
##抓取脚本( 积攒了所有的历史股价信息 && 二手房成交信息)

###股票列表[更新]

0 10,12,20,22  * * * cd /home/work/newTools/hxf && /home/work/odp/bin/php yii stock/init-list 2>&1 1>>ct.log

###股票今日

5 17 * * * cd /home/work/newTools/hxf && /home/work/odp/bin/php yii stock/spider-today-stock-info 2>&1 1>>ct.log

###新股

40 9-10 * * * cd /home/work/newTools/hxf && /home/work/odp/bin/php yii stock/spider-new-stock 2>&1 1>>ct.log

###链家成交信息

0 10 * * * cd /home/work/newTools/hxf && /home/work/odp/bin/php yii house/spider-lj 2>&1 1>>ct.log

0 9 * * *  cd /home/work/newTools/hxf && /home/work/odp/bin/php yii house/spider-lj-village-data 2>&1 1>>ct.log

--------------------------------------
##代码结构

console/controllers

    HouseController.php

        actionSpiderLj 抓取每日链家成交数据

        actionSpiderLjVillageData 抓取链家小区数据

        actionSpiderLjVillage 抓取小区成交数据

    StockController.php

        actionInitList 初始化股票列表

        actionInitHistory 初始化历史股价数据

        actionSpiderTodayStockInfo 抓取今日股价数据

        actionSpiderNewStock 抓取今日新股
--------------------------------------
