#!/usr/bin/env bash

cd /home/deploy/repos/$1/skylar-shopify-theme || exit 1
echo $PWD
git checkout master
git pull
git checkout settings-theme-$2 && git pull || git checkout -b settings-theme-$2 --set-upstream origin settings-theme-$2