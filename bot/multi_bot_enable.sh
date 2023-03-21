#!/bin/bash
count=5
for i in $(seq 1 $count)
do
    sh ./bot_enbale.sh &
done
wait