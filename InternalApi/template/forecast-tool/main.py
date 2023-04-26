from functions.utils.parser import string_to_bool
from functions.forecasting_module import ForecastingModule
from functions.Configuration.configuration_class import Configuration
import pandas as pd
import sys
import argparse
import datetime
import logging
import os

os.environ['CUDA_VISIBLE_DEVICES'] = '-1'


'''
Add logging for troubleshooting problems arising during execution
'''
logger = logging.getLogger('Forecasting-Tool')
logger.setLevel(logging.INFO)

# create a file handler
handler = logging.FileHandler('forecast_tool.log')
handler.setLevel(logging.INFO)

# create a logging format
formatter = logging.Formatter(
    '%(asctime)s - %(name)s - %(levelname)s - %(message)s')
handler.setFormatter(formatter)

# add the file handler to the logger
logger.addHandler(handler)


def main():
    '''
    Function

        Main forecasting function to be used in production phase. This function calls forecasting procedure
        once and writes predictions into output excel file to be used in linear optimizer.

    Principle

        General workflow is described in Documentation Doc provided to INENSUS. Some further notes here:
        At first current timestamp is retrieved from system time and coverted/localized to timezone of
        site. It is important that timezone is set correctly in Config.txt provided with the tool in
        [general] section.

        For socket server path is read from Config.txt (please change if needed in Config in
        section [general].

        Forecasting module is then loaded in which is the main script combining different functions to:
        1. update data
        2. eval recent forecasts
        3. prepare datasets for forecasting and training
        4. train Neural Network
        5. Predict with Neural Network
        6. Predict with statistical and persistency models
        7. Save forecast to Excel to provide it to linear optimizer

    Additional Notes

        Testing Purpose:
        For testing please use main_test_run by enabling testing mode via config file.

    Returns

        Nothing to Return
    '''
    path = Configuration.getConfigValue('general', 'app_path')
    # create a file handler
    handler = logging.FileHandler(path + '/forecast_tool.log')
    handler.setLevel(logging.INFO)

    # create a logging format
    formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s')
    handler.setFormatter(formatter)

    # add the file handler to the logger
    logger.addHandler(handler)
    # get current system time and add Timezone. Timezone has to be changed if wrong!
    timezone_site = Configuration.getConfigValue('general', 'timezone_site')
    time_now = pd.to_datetime(datetime.datetime.now().astimezone().isoformat(timespec='seconds')).tz_convert(
        timezone_site)
    # read app path for socket server

    # Initialize forecasting module
    fm = ForecastingModule(current_path=path)
    # update data (get data from server or somewhere else)
    fm.update_data(time_now)
    # evaluate recent forecast to check if training is needed
    fm.eval_recent_forecasts(time_now)
    # prepare dataset for forecasting, train and predict
    fm.prepare_train_predict(time_now)
    # predict with statistical approaches (PSLP...)
    fm.add_statistical_forecasts(time_now)
    # write forecast into a specified excel file
    fm.forecasts_to_excel(time_now)


def reset_datasets(date: str, path_pre=''):
    '''
    Function

        Shortening dataset to specified date. This is important when testing functions. Datasets are
        shortened to specified date to use in testing stage to not contain data which would not be
        available. All methods are then used as intended in a standalone environment

    Parameters

        date (pd.datetime): date to be used in format of: year-month-day hour:minutes

    Returns

        Nothing to return datasets are saved in their respective folder directly

    Notes
        Function should be used only in testing and troubleshooting before using main_test_run function.
    '''
    path = Configuration.getConfigValue('general', 'app_path')
    # create a file handler
    handler = logging.FileHandler(path + '/forecast_tool.log')
    handler.setLevel(logging.INFO)

    # create a logging format
    formatter = logging.Formatter(
        '%(asctime)s - %(name)s - %(levelname)s - %(message)s')
    handler.setFormatter(formatter)

    # add the file handler to the logger
    logger.addHandler(handler)

    timezone_site = Configuration.getConfigValue('general', 'timezone_site')
    # string date to pandas datetime
    date = pd.to_datetime(date).tz_localize(timezone_site)

    # default paths to the datasets
    paths = {'load': f'{path_pre}/resources/01_measurements/load/load_measurements.csv',
             'pv': f'{path_pre}/resources/01_measurements/pv/pv_measurements.csv',
             'weather': f'{path_pre}/resources/01_measurements/weather_data/historical/weather_measurements.csv'}

    # reset every dataset to given date
    date_columns = {'load': 'read_out',
                    'pv': 'time_stamp', 'weather': 'timestamp'}
    for key in paths.keys():
        try:
            data = pd.read_csv(paths.get(key))
            data.index = pd.to_datetime(data[date_columns.get(key)])
            data = data.drop(columns=[date_columns.get(key)])
            data = data[data.index <= date-pd.to_timedelta('1D')]
            data.to_csv(paths.get(key))
        except FileNotFoundError:
            pass


def remove_files_folders(top):
    '''
    Function

        Removes all files from previous test runs and therefore does a clean start.

    Parameters

        top: string or path to folder where data should be removed

    Returns

        Nothing to return
    '''
    for root, dirs, files in os.walk(top, topdown=False):
        for name in files:
            os.remove(os.path.join(root, name))
        for name in dirs:
            os.rmdir(os.path.join(root, name))


def reset_models_predictions_evaluation(path):
    '''
    Function

        Removes folders containing: trained models, predictions and evaluation

    Parameters

        path (str): absolute path to folder where data should be removed

    Returns

        Nothing to return
    '''
    print('!!!DELETING ALL PREVIOUSLY CALCULATED DATA!!!')
    remove_files_folders(f'{path}/resources/02_models')
    remove_files_folders(f'{path}/resources/03_predictions')
    remove_files_folders(f'{path}/resources/04_evaluation')
    remove_files_folders(f'{path}/resources/05_output/saved_predictions')


def main_test_run(date, shift_by='1D'):
    '''
     Function

         Testing forecasting function to be used in production phase. This function calls
         forecasting procedure once and writes predictions into output excel file to be used
         in linear optimizer.

     Principle

         General workflow is described in Documentation Doc provided to INENSUS.
         Some further notes here: At first current timestamp is retrieved from system time and
         coverted/localized to timezone of site. It is important that timezone is set correctly
         in Config.txt provided with the tool in [general] section.

         For socket server path is read from Config.txt (please change if needed in Config
         in section [general]

         Forecasting module is then loaded in which is the main script combining different functions to:
         1. update data
         2. eval recent forecasts
         3. prepare datasets for forecasting and training
         4. train Neural Network
         5. Predict with Neural Network
         6. Predict with statistical and persistency models
         7. Save forecast to Excel to provide it to linear optimizer

     Additional Notes

         Testing Purpose:
         For productive phase use main by disabling testing mode via config file.

     Returns

         Nothing to Return
     '''
    # get simulated current time
    timezone_site = Configuration.getConfigValue('general', 'timezone_site')
    time_now = pd.to_datetime(date).tz_localize(timezone_site)
    # get real current time (as last point)
    time = pd.to_datetime(datetime.datetime.now().astimezone().isoformat(timespec='seconds')).tz_convert(
        timezone_site)
    path = Configuration.getConfigValue('general', 'app_path')
    # delete all models previously trained
    reset_models_predictions_evaluation(path)
    # reset dataset to state at simulated current time
    reset_datasets(date, path)
    # Initialize forecasting module
    fm = ForecastingModule(current_path=path)
    # while current_time lower than time make prediction
    time = time + \
        pd.to_timedelta(Configuration.getConfigValue(
            'testing_mode', 'test_period'))
    while time_now < time:
        # update data (get data from server or somewhere else)
        fm.update_data(time_now)
        # evaluate recent forecast to check if training is needed
        fm.eval_recent_forecasts(time_now)
        # prepare dataset for forecasting, train and predict
        fm.prepare_train_predict(time_now)
        # predict with statistical approaches (PSLP...)
        fm.add_statistical_forecasts(time_now)
        # write forecast into a specified excel file
        fm.forecasts_to_excel(time_now)
        # add specified timedelta to time
        time_now += pd.to_timedelta(shift_by)


if __name__ == '__main__':
    '''
    Config-file has to be added as argument to start script.
    '''
    # set arguments
    argument_parser = argparse.ArgumentParser()
    argument_parser.add_argument(
        "-c", "--config", required=True, help="File containing properties")
    args = vars(argument_parser.parse_args())

    propertyFile = args['config']
    try:
        # Hier wird Config Datei festgelegt
        Configuration.setConfigFilePath(propertyFile)
    except FileNotFoundError:
        print("Could not find property file: ")
        print(propertyFile)
        sys.exit()

    enable_test_mode = string_to_bool(
        Configuration.getConfigValue('testing_mode', 'enable_testing_mode'))
    if enable_test_mode is True:
        date = pd.to_datetime(Configuration.getConfigValue(
            'testing_mode', 'start_date'))
        main_test_run(date)
    else:
        try:
            main()
        except Exception as e:
            # log exception info at CRITICAL log level
            logger.critical(e, exc_info=True)
