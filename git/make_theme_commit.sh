#!/usr/bin/env bash

cd /home/deploy/repos/$1/skylar-shopify-theme || exit 1
echo $PWD
git checkout master
git pull
git checkout settings-theme-$2 && git pull || git checkout -b settings-theme-$2 && git branch --set-upstream-to=origin settings-theme-$2
curl -g -X GET "https://"$3"@maven-and-muse.myshopify.com/admin/api/2019-10/themes/"$2"/assets.json?asset[key]=config/settings_data.json" | jq -r '.asset.value' > src/config/settings_data.json
git commit -am "pull settings from shopify" && git push --set-upstream origin settings-theme-$2