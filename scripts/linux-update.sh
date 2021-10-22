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
SCRIPTPATH="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"
UPGRADEPATH=$SCRIPTPATH"/upgrade"
UPGRADEFILE=$SCRIPTPATH"/upgrade/upgrade.zip"
FOLDER=$UPGRADEPATH"/Organizr-"${BRANCH#v}
URL=https://github.com/causefx/Organizr/archive/${BRANCH}.zip
mkdir -p $UPGRADEPATH                                                  && \
curl -sSL ${URL} > $UPGRADEFILE                                        && \
unzip $UPGRADEFILE -d $UPGRADEPATH                                     && \
cp -r $FOLDER/ $SCRIPTPATH/../                                         && \
rm $UPGRADEFILE                                                        && \
rm -rf $FOLDER                                                         && \
rm -rf $UPGRADEPATH                                                    && \
exit 0