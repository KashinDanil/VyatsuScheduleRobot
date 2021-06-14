#!/bin/bash

START=$(date +"%Y-%m-%d %H:%M:%S")
echo "start $START"

cd /home/admin/web/Diplomwork/

javac -classpath ".:mysql-connector-java.jar:tabula-with-dependencies.jar" Main.java
java -classpath ".:mysql-connector-java.jar:tabula-with-dependencies.jar" Main

FINISH=$(date +"%Y-%m-%d %H:%M:%S")
echo "finish $START -- $FINISH"