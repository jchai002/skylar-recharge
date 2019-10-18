#!/usr/bin/env bash

cd /home/deploy/repos/$1/skylar-shopify-theme
echo $PWD
git checkout master
git pull
git checkout settings-theme-$2 && git pull || git checkout -b settings-theme-$2
echo $5 > src/config/settings_data.json
hub pr list -h settings-theme-$2
git commit -am "pull settings from shopify" && git push --set-upstream origin settings-theme-$2
PULL_REQUEST=$(curl -u JTimNolan:$3 -X GET "https://api.github.com/repos/JTimNolan/skylar-shopify-theme/pulls?head=skylar-shopify-theme:settings-theme-$2" | jq -r '.')
if [[ PULL_REQUEST == "[]" ]]
then
    PULL_REQUEST=$(curl -g -u JTimNolan:$3 -H "Content-Type: application/json" -d '{"title":"Settings update: '$4'", "head":"settings-theme-'$2'", "base":"master"}' -X POST "https://api.github.com/repos/JTimNolan/skylar-shopify-theme/pulls" | jq -r '.')
else
    PULL_REQUEST=$(PULL_REQUEST | jq -r '.[0]')
fi
echo PULL_REQUEST