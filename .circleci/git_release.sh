#set -x
cd ..
EXISTING_TAGS=(`echo $(git tag -l)`);

COMPOSER_TAG=$(grep -o '^ *"version": *"[0-9\.]*"' composer.json|awk '{print $2}'|sed -e 's/"\(.*\)"/\1/g')

for constant in $EXISTING_TAGS
do
  if [ "$constant" = "$COMPOSER_TAG" ]; then
    echo "The tag exists already: ${COMPOSER_TAG}";
    exit;
  fi

done

echo "Create new tag: ${COMPOSER_TAG}"

git tag ${COMPOSER_TAG}
git push origin --tags
#GET /repos/PowerSync/:repo/releases/latest
