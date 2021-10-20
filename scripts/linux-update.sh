#!/bin/bash
if [ -z "$1" ]
  then
  echo 'No branch setup.. using v2-master'
  BRANCH="v2-master"
elif [ "$1" == "v2-develop" ] || [ "$1" == "develop" ] || [ "$1" == "dev" ]
  then
  BRANCH="v2-develop"
elif [ "$1" == "v2-master" ] || [ "$1" == "master" ]
  then
  BRANCH="v2-master"
else
  echo "$1 is not a valid branch, exiting"
  exit 1
fi
FOLDER="Organizr-"${BRANCH#v}
URL=https://github.com/causefx/Organizr/archive/${BRANCH}.zip
mkdir -p ./upgrade                                                               && \
cd ./upgrade                                                                     && \
curl -sSL ${URL} > upgrade.zip                                                   && \
unzip upgrade.zip                                                                && \
cd ${FOLDER}                                                                     && \
cp -r ./ ../../../                                                               && \
cd ../                                                                           && \
rm upgrade.zip                                                                   && \
rm -rf ${FOLDER}                                                                 && \
exit 0