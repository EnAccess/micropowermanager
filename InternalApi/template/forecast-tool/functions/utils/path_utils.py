import os

import pandas as pd

from functions.Configuration.configuration_class import Configuration


def config_path_to_full_path(folder_path, section, name, check_path_end=False):
    """
    Function

        Get relative path from config and combine them to a full path (especially for Socket Server use)


    Returns

        total_path: string
            combined path
    """
    # check ending of path if \ is set otherwise set it
    if check_path_end is True:
        relative_path = check_path_ending(Configuration.getConfigValue(section, name))
    else:
        relative_path = Configuration.getConfigValue(section, name)
    # add relative path to folder path
    total_path = folder_path + '/' + relative_path
    return total_path


def check_path_ending(path):
    """
    Function

        check if a '/' is at the end of the path otherwise add

    Parameters

        path : str/path
            to check

    Returns

        path: str
            corrected path
    """
    # check path ending
    if not path.endswith('/'):
        path = path + '/'
    else:
        pass
    # return corrected path
    return path


def check_path_exist(path):
    """
    Function

        check if given path exists otherwise folder is created

    Parameter

        path : str/path
            to check

    Return

        result : bool

    """
    if not os.path.isdir(path):
        os.makedirs(path)
        return False
    else:
        return True


def check_file_cache(data_cache_path, data_cache_name):
    '''
    Function

        Check if cached files are available and return true or false

    Parameter

        data_cache_path: str
            path to where data is stored
        data_cache_name: str
            name of file where data is stored

    Return

        Bool:
            if cache file exists
    '''
    if not os.path.isdir(data_cache_path):
        os.makedirs(data_cache_path)
        return False
    else:
        data_cache_path = check_path_ending(data_cache_path)
        data_path = data_cache_path + data_cache_name
        if not os.path.isfile(data_path):
            return False
        elif os.path.isfile(data_path):
            return True


def __add_zero_to_date(date):
    if date < 10:
        date = '0' + str(date)
    return date


def time_to_path(timestamp, path_prefix=None, data_name_ending=None, create_path=False):
    """
    Function

        Converts a given timestamp to folder path

    Parameter

        timestamp : pd.datetime
            timestmap to convert

        path_prefix : str
            string to add before string of time

        data_name_ending : str
            str to add after string of time

        create_path : bool
            if path should be created


    Returns

        combined_time_path : str
            path including time as folder name
    """
    year = timestamp.year
    month = timestamp.month
    day = timestamp.day
    month = __add_zero_to_date(month)
    day = __add_zero_to_date(day)
    time = str(timestamp.time()).replace(':', '-')
    combined_time_path = str(year) + '/' + str(month) + '/' + str(day)
    if path_prefix is not None:
        combined_time_path = path_prefix + combined_time_path
    if create_path is True:
        os.makedirs(combined_time_path, exist_ok=True)
    combined_time_path = combined_time_path + '/' + time
    if data_name_ending is not None:
        combined_time_path = combined_time_path + data_name_ending
    return combined_time_path


def path_to_time(file, path, file_split, path_split, timezone=None):
    """
    Function

        Converts path with timestamp to time

    Returns

        time_stamp : pd.datetime
            time extracted from path
    """
    time = file.split(file_split)[0]
    date = path.split(path_split)[-1].replace('\\', '-')
    combined = date + ' ' + time.replace('-', ':')
    time_stamp = pd.to_datetime(combined)
    if timezone is not None:
        time_stamp = time_stamp.tz_localize(None).tz_localize(timezone)
    return time_stamp


def delete_file(filepath):
    """
    Function

        deletes exisiting file

    Parameter

        filepath : str
            path to file to be removed
    """

    os.remove(filepath)
