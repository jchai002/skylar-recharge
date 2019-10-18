#!/usr/bin/env bash

whoami
cd /home/deploy/repos/$1/skylar-shopify-theme
echo $PWD
git checkout master
git pull
git checkout settings-theme-$2 || git checkout -b settings-theme-$2
echo $4 > src/config/settings_data.json
exit
git commit -am "pull settings from shopify" && git push
[[ -z $(hub pr list -h settings-theme-$2) ]] && hub pull-request -m "Settings update: "$3