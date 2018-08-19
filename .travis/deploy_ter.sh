#!/usr/bin/env bash

set -ev

phpenv config-rm xdebug.ini

# Rename our working directory, required for Extension upload to TER.
cd .. && mv typo3-extension-mediaoembed mediaoembed

if [ -z "$TRAVIS_TAG" ]; then
    echo "No Travis tag is available. Upload only runs for new tags."
    exit 0
fi

if [ -z "$TYPO3_ORG_USERNAME" ]; then
    echo "The $TYPO3_ORG_USERNAME env var is not set."
    exit 1
fi

if [ -z "$TYPO3_ORG_PASSWORD" ]; then
    echo "The $TYPO3_ORG_PASSWORD env var is not set."
    exit 1
fi

TAG_MESSAGE=`git tag -n10 -l $TRAVIS_TAG | sed 's/^[0-9.]*[ ]*//g'`

if [ -z "$TAG_MESSAGE" ]; then
    echo "The tag message could not be detected or was empty."
    exit 1
fi

echo "Installing TYPO3 repository client..."

composer create-project --no-dev namelesscoder/typo3-repository-client typo3-repository-client

echo "Uploading release ${TRAVIS_TAG} to TER"

./typo3-repository-client/bin/setversion ${TRAVIS_TAG} stable mediaoembed

./typo3-repository-client/bin/upload mediaoembed "$TYPO3_ORG_USERNAME" "$TYPO3_ORG_PASSWORD" "$TAG_MESSAGE"
