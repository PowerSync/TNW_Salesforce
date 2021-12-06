#set -x
cd ..

VERSION=$(grep -o '^ *"version": *"[0-9\.]*"' composer.json|awk '{print $2}'|sed -e 's/"\(.*\)"/\1/g')

# Upload update to Marketplace
zip -r code.zip ./*
git clone git@github.com:PowerSync/TNW_EQP.git --branch main
mv code.zip TNW_EQP
cd TNW_EQP
bin/main code.zip $VERSION
