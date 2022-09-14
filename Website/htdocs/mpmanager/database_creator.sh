#!/bin/bash


################################################################################
# CORE FUNCTIONS
################################################################################
#
# VARIABLES
#
_bold=$(tput bold)
_underline=$(tput sgr 0 1)
_reset=$(tput sgr0)

_purple=$(tput setaf 171)
_red=$(tput setaf 1)
_green=$(tput setaf 76)
_tan=$(tput setaf 3)
_blue=$(tput setaf 38)

#
# HEADERS & LOGGING
#
function _debug()
{
    [ "$DEBUG" -eq 1 ] && $@
}

function _header()
{
    printf "\n${_bold}${_purple}==========  %s  ==========${_reset}\n" "$@"
}

function _arrow()
{
    printf "➜ $@\n"
}

function _success()
{
    printf "${_green}✔ %s${_reset}\n" "$@"
}

function _error()
 {
    printf "${_red}✖ %s${_reset}\n" "$@"
}

function _warning()
{
    printf "${_tan}➜ %s${_reset}\n" "$@"
}

function _underline()
{
    printf "${_underline}${_bold}%s${_reset}\n" "$@"
}

function _bold()
{
    printf "${_bold}%s${_reset}\n" "$@"
}

function _note()
{
    printf "${_underline}${_bold}${_blue}Note:${_reset}  ${_blue}%s${_reset}\n" "$@"
}

function _die()
{
    _error "$@"
    exit 1
}

function _safeExit()
{
    exit 0
}

#
# UTILITY HELPER
#
function _seekConfirmation()
{
  printf "\n${_bold}$@${_reset}"
  read -p " (y) " -n 1
  printf "\n"
}

# Test whether the result of an 'ask' is a confirmation
function _isConfirmed()
{
    if [[ "$REPLY" =~ ^[Yy]$ ]]; then
        return 0
    fi
    return 1
}


function _typeExists()
{
    if [ $(type -P $1) ]; then
        return 0
    fi
    return 1
}

function _isOs()
{
    if [[ "${OSTYPE}" == $1* ]]; then
      return 0
    fi
    return 1
}

function _checkRootUser()
{
    #if [ "$(id -u)" != "0" ]; then
    if [ "$(whoami)" != 'root' ]; then
        echo "You have no permission to run $0 as non-root user. Use sudo"
        exit 1;
    fi

}


################################################################################
# SCRIPT FUNCTIONS
################################################################################
function generatePassword()
{
    echo "$(openssl rand -base64 12)"
}

function _printUsage()
{
    echo -n "$(basename $0) [OPTION]...

Create MySQL db & user.
Version $VERSION

    Options:
        -h, --host        MySQL Host
        -d, --database    MySQL Database
        -u, --user        MySQL User
        -p, --pass        MySQL Password (If empty, auto-generated)
        -h, --help        Display this help and exit
        -v, --version     Output version information and exit
        --path            path of the config file

    Examples:
        $(basename $0) --help

"

    exit 1
}

function processArgs()
{
    # Parse Arguments
    for arg in "$@"
    do
        case $arg in
            -h=*|--host=*)
                DB_HOST="${arg#*=}"
            ;;
            -d=*|--database=*)
                DB_NAME="${arg#*=}"
            ;;
            -cid=*|--company_id=*)
                C_ID="${arg#*=}"
            ;;
            -u=*|--user=*)
                DB_USER="${arg#*=}"
            ;;
             -p=*|--pass=*)
                DB_PASS="${arg#*=}"
            ;;
            --path=*)
              SOURCE_PATH="${arg#*=}"
              ;;
            --debug)
                DEBUG=1
            ;;
            -h|--help)
                _printUsage
            ;;
            *)
                _printUsage
            ;;
        esac
    done
    [[ -z $DB_NAME ]] && _error "Database name cannot be empty." && exit 1
    [[ $DB_USER ]] || DB_USER=$DB_NAME
}

function createMysqlDbUser()
{

    SQL1="CREATE DATABASE IF NOT EXISTS ${DB_NAME};"
    #SQL2="CREATE USER '${DB_USER}'@'%' IDENTIFIED BY '${DB_PASS}';"
    #SQL3="GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'%';"
    #SQL4="FLUSH PRIVILEGES;"
    mysql -h db -P 3306 -uroot -pinensus2022. -e  "${SQL1}"

}

#todo remove after database connection is working in the middleware
function addConnectionToDatabase()
{
        sed -i '
        /'\'connections\''/a\
        '\'$DB_NAME\'' => [ \
            '\'driver\'' => '\'mysql\'', \
            '\'host\'' => '\'db\'', \
            '\'port\'' => '\'3306\'', \
            '\'database\'' => '\'$DB_NAME\'', \
            '\'username\'' => '\'root\'', \
            '\'password\'' => '\'inensus2022.\'', \
            '\'unix_socket\'' => '\'\'', \
            '\'charset\'' => '\'utf8mb4\'', \
            '\'collation\'' => '\'utf8mb4_unicode_ci\'', \
            '\'prefix\'' => '\'\'', \
            '\'strict\'' => 'false', \
            '\'engine\'' => 'null', \
        ],
        ' ${SOURCE_PATH}/config/database.php
}

# create new folder in migrations folder
function createNewMigrationFolder()
{
    if [ ! -d "${SOURCE_PATH}/database/migrations/$DB_NAME" ]; then
        mkdir -p ${SOURCE_PATH}/database/migrations/$DB_NAME
        chmod +x ${SOURCE_PATH}/database/migrations/$DB_NAME
    fi
}


# copy files in micropowermanager folder into new created folder
function copyMigrationFiles()
{
    cp -r ${SOURCE_PATH}/database/migrations/micropowermanager/* ${SOURCE_PATH}/database/migrations/$DB_NAME
}

# loop through new created folder files and perform sed
function sedMigrationFiles()
{
    for file in ${SOURCE_PATH}/database/migrations/$DB_NAME/*
    do
        sed -i 's/micropowermanager/shard/g' $file
    done
}

## run migrator command to migrate database
function runMigrator()
{
    cd ${SOURCE_PATH}
    php artisan optimize:clear
    php artisan migrator:migrate $DB_NAME $C_ID
}

################################################################################
# Main
################################################################################
export LC_CTYPE=C
export LANG=C

DEBUG=0 # 1|0
_debug set -x
VERSION="0.1.0"

BIN_MYSQL=$(which mysql)

DB_HOST='localhost'
DB_NAME=
DB_USER=
DB_PASS=$(generatePassword)

function main()
{
    [[ $# -lt 1 ]] && _printUsage
    _success "Processing arguments..."
    processArgs "$@"
    _success "Done!"

    echo "################################ " >> ${SOURCE_PATH}/creator.log
    echo "##### Creator Script Starts ###### " >> ${SOURCE_PATH}/creator.log
    echo "Creating MySQL db and user... " >> ${SOURCE_PATH}/creator.log
    createMysqlDbUser
    echo "Done! " >> ${SOURCE_PATH}/creator.log

     echo "Creating migration folder.. " >> ${SOURCE_PATH}/creator.log
     createNewMigrationFolder
     echo "Done! " >> ${SOURCE_PATH}/creator.log

     echo "Copying migration files.. " >> ${SOURCE_PATH}/creator.log
     copyMigrationFiles
     echo "Done! " >> ${SOURCE_PATH}/creator.log

     echo "Modifying migration files.. " >> ${SOURCE_PATH}/creator.log
     sedMigrationFiles
     echo "Done! " >> ${SOURCE_PATH}/creator.log

      echo "Running migrations for new database.. " >> ${SOURCE_PATH}/creator.log
      runMigrator
      echo "Done! " >> ${SOURCE_PATH}/creator.log


     echo "################################ " >> ${SOURCE_PATH}/creator.log
     echo "##### Creator Script Ends ###### " >> ${SOURCE_PATH}/creator.log
     echo "****************************************************************" >> ${SOURCE_PATH}/creator.log
    exit 0
}

main "$@"

_debug set +x
#./database_creator.sh --host=localhost --database=remove_db --user=root --company_id=23432
