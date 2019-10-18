#!/usr/bin/env bash

cd /home/deploy/repos/$1/skylar-shopify-theme || exit 1
git commit -am "pull settings from shopify" && git push --set-upstream origin settings-theme-$2