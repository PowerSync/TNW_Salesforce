#set -x

VERSION=$(grep -o '^ *"version": *"[0-9\.]*"' ../composer.json|awk '{print $2}'|sed -e 's/"\(.*\)"/\1/g')

# Create update payload
ARCH_NAME=code.zip
SAMPLE=metapackage/composer.json.sample
TARGET=metapackage/composer.json
cp $SAMPLE $TARGET
sed -i "s/\$VERSION/$VERSION/" $TARGET
zip -j $ARCH_NAME $TARGET

# Upload update to Marketplace
git clone git@github.com:PowerSync/TNW_EQP.git eqp --branch main
mv $ARCH_NAME eqp
cd eqp
bin/main $ARCH_NAME $VERSION
