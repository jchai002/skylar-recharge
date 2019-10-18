#!/usr/bin/env bash

GITHUB_TOKEN=$3
echo $GITHUB_TOKEN
cd /home/deploy/repos/$1/skylar-shopify-theme
echo $PWD
git checkout master
git pull
git checkout settings-theme-$2 && git pull || git checkout -b settings-theme-$2
echo $5 > src/config/settings_data.json
hub pr list -h settings-theme-$2
git commit -am "pull settings from shopify" && git push --set-upstream origin settings-theme-$2
[[ -z $(hub pr list -h settings-theme-$2) ]] && hub pull-request -m "Settings update: "$4