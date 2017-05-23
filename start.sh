#/bin/sh

while [[ 0 -lt 1 ]]
do
    start=0
    while [[ $start -lt 5 ]]
    do
        nohup /home/work/odp/bin/php yii house/spider-lj-village $start &
        ((start=$start+1))
    done
    wait
    sleep 5
done
