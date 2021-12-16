#set -x

VERSION=$(grep -o '^ *"version": *"[0-9\.]*"' ../composer.json|awk '{print $2}'|sed -e 's/"\(.*\)"/\1/g')
WD=$(pwd)

# Create shared package update payload
SHARED_ARCH_NAME=code_shared.zip
cd ..
zip -r $SHARED_ARCH_NAME ./*

# Upload shared package update to Marketplace
cd $WD
git clone git@github.com:PowerSync/TNW_EQP.git eqp --branch main
mv ../$SHARED_ARCH_NAME eqp
cp -r data eqp
cd eqp
bin/main $SHARED_ARCH_NAME $VERSION 0
rm $SHARED_ARCH_NAME

# Create meta package update payload
cd $WD
META_ARCH_NAME=code_meta.zip
SAMPLE=metapackage/composer.json.sample
TARGET=metapackage/composer.json
cp $SAMPLE $TARGET
sed -i "s/\$VERSION/$VERSION/" $TARGET
zip -j $META_ARCH_NAME $TARGET

# Upload update to Marketplace
mv $META_ARCH_NAME eqp
cd eqp
bin/main $META_ARCH_NAME $VERSION 1
rm $META_ARCH_NAME
