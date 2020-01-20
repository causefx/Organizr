#!/usr/bin/env bash

##
 # tightenco/collect upgrader script
 #
 # usage
 #    ./upgrade.sh [laravel framework version]
 #
 #    or
 #
 #    bash upgrade.sh [laravel framework version]
 #

##
 # Include dotfiles on file operations
 #
shopt -s dotglob

##
 # App
 #

function main()
{
    echo "Upgrading..."

    checkDependencies

    prepareEnvironment $1

    displayVariables

    cleanDirectories

    downloadRepository

    extractZip

    copyClasses

    copyContracts

    copyTraits

    copyStubs

    downloadTests

    renameNamespace

    fillAliases

    cleanupDir

    runTests
}

##
 # Check if all dependencies are available
 #
function checkDependencies()
{
    for dependency in ${dependencies[@]}; do
        if ! [ -x "$(command -v ${dependency})" ]; then
            echo "Error: ${dependency} is not installed." >&2
            exit 1
        fi
    done
}

##
 # Prepare the environment
 #
function prepareEnvironment()
{
    ##
     # Define all variables
     #
    requestedVersion=$1
    rootDir=.
    baseDir=${rootDir}/src
    vendor=laravel
    project=framework
    oldNamespace='Illuminate'
    newNamespace='Tightenco\\Collect'
    newDir='Collect'
    logFile=$(mktemp /tmp/collect-log.XXXXXX)
    repository=https://github.com/$vendor/$project.git

    getCurrentVersionFromGitHub

    repositoryDir=${rootDir}/$project-${collectionVersion}
    repositorySrcDir=${repositoryDir}/src
    collectionZip=${rootDir}/$project-${collectionVersion}.zip
    collectionZipUrl=https://github.com/$vendor/$project/archive/v${collectionVersion}.zip
    oldNamespaceDir=${repositorySrcDir}/${oldNamespace}
    newNamespaceDir=${baseDir}/${newDir}
    testsDir=${rootDir}/tests
    testsBaseUrl=https://raw.githubusercontent.com/${vendor}/${project}/v${collectionVersion}/tests
    stubsDir=${rootDir}/stubs
    aliasFile=${baseDir}/${newDir}/Support/alias.php
carriageReturn="
"

    classes=(
        'Support/Collection'
        'Support/Arr'
        'Support/Carbon'
        'Support/HigherOrderCollectionProxy'
        'Support/HtmlString'
    )

    excludeFromAliases=(
        'Support/Carbon'
    )

    traits=(
        'Support/Traits/Macroable'
    )

    contracts=(
        'Contracts/Support/Arrayable'
        'Contracts/Support/Jsonable'
        'Contracts/Support/Htmlable'
    )

    tests=(
        'Support/SupportCollectionTest.php'
        'Support/SupportArrTest.php'
        'Support/SupportMacroableTest.php'
        'Support/SupportCarbonTest.php'
    )

    stubs=(
        'src/Collect/Support/helpers.php'
        'src/Collect/Support/alias.php'
        'tests/bootstrap.php'
    )

    dependencies=(
        'wget'
        'unzip'
        'mktemp'
    )
}

##
 # Display all variables
 #
function displayVariables()
{
    echo
    echo "-- Variables"
    echo "---------------------------------------------"

    echo baseDir = ${baseDir}
    echo collectionVersion = ${collectionVersion}
    echo repositoryDir = ${repositoryDir}
    echo repositorySrcDir = ${repositorySrcDir}
    echo collectionZip = ${collectionZip}
    echo baseDir = ${baseDir}
    echo oldNamespace = ${oldNamespace}
    echo newNamespace = ${newNamespace}
    echo oldNamespaceDir = ${oldNamespaceDir}
    echo newNamespaceDir = ${newNamespaceDir}
    echo testsDir = ${testsDir}
    echo testsBaseUrl = ${testsBaseUrl}

    echo "---------------------------------------------"
}

##
 # Clean the destination directory
 #
function cleanDirectories()
{
    echo "Cleaning ${baseDir} and ${testsDir}/Support..."

    if [ -d ${baseDir} ]; then
        rm -rf ${baseDir}
    fi

    if [ -d ${testsDir}/Support ]; then
        rm -rf ${testsDir}
    fi

    if [ -d ${repositoryDir} ]; then
        rm -rf ${repositoryDir}
    fi
}

##
 # Download a new version
 #
function downloadRepository()
{
    echo "-- Downloading ${collectionZipUrl} to ${baseDir}"

    wget ${collectionZipUrl} -O ${collectionZip} >${logFile} 2>&1

    handleErrors
}

##
 # Extract from compressed file
 #
function extractZip()
{
    echo "-- Extracting $project.zip..."

    unzip ${collectionZip} -d ${rootDir} >${logFile} 2>&1

    rm ${collectionZip}

    handleErrors
}

##
 # Copy classes
 #
function copyClasses()
{
    echo "-- Copying classes and contracts..."

    for class in ${classes[@]}; do
        echo "Copying ${oldNamespaceDir}.php/${class}.php..."

        mkdir -p $(dirname ${newNamespaceDir}/${class})

        cp ${oldNamespaceDir}/${class}.php ${newNamespaceDir}/${class}.php
    done
}

##
 # Move contracts
 #
function copyContracts()
{
    echo "-- Copying contracts..."

    for contract in ${contracts[@]}; do
        echo "Copying ${oldNamespaceDir}/${contract}.php..."

        mkdir -p $(dirname ${newNamespaceDir}/${contract})

        cp ${oldNamespaceDir}/${contract}.php ${newNamespaceDir}/${contract}.php
    done
}

##
 # Move traits
 #
function copyTraits()
{
    echo "-- Copying traits..."

    for trait in ${traits[@]}; do
        echo "Copying ${oldNamespaceDir}/${trait}.php..."

        mkdir -p $(dirname ${newNamespaceDir}/${trait})

        cp ${oldNamespaceDir}/${trait}.php ${newNamespaceDir}/${trait}.php
    done
}

##
 # Copy classes and contracts
 #
function copyStubs()
{
    echo "-- Copying stubs..."

    for stub in ${stubs[@]}; do
        echo "Copying ${stubsDir}/${stub} to ${rootDir}/${stub}..."

        mkdir -p $(dirname ${rootDir}/${stub})

        cp ${stubsDir}/${stub} ${rootDir}/${stub}
    done
}

##
 # Fill the alias.php file with the list of aliases
 #
function fillAliases()
{
    echo "-- Filling aliases.php..."

    indent='    '
    aliases='CARRIAGERETURN'

    for contract in ${contracts[@]}; do
        aliases="${aliases}${indent}${newNamespace}/${contract}::class => ${oldNamespace}/${contract}::class,CARRIAGERETURN"
    done

    for class in ${classes[@]}; do
        if [[ ! " ${excludeFromAliases[@]} " =~ " ${class} " ]]; then
            aliases="${aliases}${indent}${newNamespace}/${class}::class => ${oldNamespace}/${class}::class,CARRIAGERETURN"
        fi
    done

    for trait in ${traits[@]}; do
        aliases="${aliases}${indent}${newNamespace}/${trait}::class => ${oldNamespace}/${trait}::class,CARRIAGERETURN"
    done

    aliases=${aliases//\//\\\\}

    sed -i "" -e "s|/\*--- ALIASES ---\*/|${aliases}|g" $aliasFile
    sed -i "" -e "s|CARRIAGERETURN|\\${carriageReturn}|g" $aliasFile
}

##
 # Copy tests to our tests dir
 #
function getCurrentVersionFromGitHub()
{
    echo Getting current version from $repository...

    if [ -z "$requestedVersion" ]; then
        collectionVersion=$(git ls-remote $repository | grep tags/ | grep -v {} | cut -d \/ -f 3 | cut -d v -f 2  | grep -v RC | grep -vi beta | sort -t. -k 1,1n -k 2,2n -k 3,3n| tail -1)
    else
        collectionVersion=$requestedVersion
    fi

    echo Upgrading to $vendor/$project $collectionVersion
}

##
 # Download tests to tests dir
 #
function downloadTests()
{
    echo "-- Copying tests..."

    for test in ${tests[@]}; do
        echo "---- Downloading test ${testsBaseUrl}/${test} to ${testsDir}/${test}..."

        mkdir -p $(dirname ${testsDir}/${test})

        wget ${testsBaseUrl}/${test} -O ${testsDir}/${test} >/dev/null 2>&1
    done
}

##
 # Rename namespace on all files
 #
function renameNamespace()
{
    echo "-- Renaming namespace from $oldNamespace to $newNamespace..."

    find ${newNamespaceDir} -name "*.php" -exec sed -i "" -e "s|${oldNamespace}|${newNamespace}|g" {} \;
    find ${testsDir} -name "*.php" -exec sed -i "" -e "s|${oldNamespace}|${newNamespace}|g" {} \;
    find ${newNamespaceDir} -name "*.php" -exec sed -i "" -e "s|/\*--- OLDNAMESPACE ---\*/|${oldNamespace}|g" {} \;
}

##
 # Clean up dir
 #
function cleanupDir()
{
    echo "-- Cleaning up ${repositoryDir}..."

    rm -rf ${repositoryDir}
}

##
 # Run tests
 #
function runTests()
{
    echo "-- Running tests..."

    if [ -f ${rootDir}/composer.lock ]; then
        rm ${rootDir}/composer.lock
    fi

    if [ -d ${rootDir}/vendor ]; then
        rm -rf ${rootDir}/vendor
    fi

    composer install

    vendor/bin/phpunit
}

##
 # Handle command errors
 #
function handleErrors()
{
    if [[ $? -ne 0 ]]; then
        echo "FATAL ERROR occurred during command execution:"
        cat ${logFile}
        exit 1
    fi
}

##
 # Run the app
 #
main $@
