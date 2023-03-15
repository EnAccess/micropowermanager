import ast
import json
import logging

import pandas as pd

from functions.Configuration.configuration_class import Configuration
from functions.data.update_manager import UpdateManager
from functions.timeseriesdata.timeseriesfunctions import TimeSeriesDataFrame
from functions.utils.parser import string_to_bool
from functions.utils.path_utils import check_file_cache, config_path_to_full_path

module_logger = logging.getLogger('Forecasting-Tool.DataManager')


class DataManager(TimeSeriesDataFrame):
    """
    Managing Data
    """

    def __init__(self, name):
        super().__init__()
        self.update_manager = None  # set by allow_updates
        self.historical_data = None
        self.raw_dataset_frequency = None
        self.do_clean_start = None
        self.target_columns = None
        self.time_column_name = None
        self.data_cache_path = None
        self.data_cache_name = None
        self.unpreprocessed_new_data = pd.DataFrame()
        self.data_processing_cache = None
        self.timezone_server = None
        self.name = name

    def delete_outliers_data_preprocessing_cache(self, max):
        """
        Function

            delete outliers from preprocessed cached data

        Parameter

            max : int
                maximum value

        Return

            data without outliers
        """
        module_logger.info(f'{self.name}: Deleting outliers in data.')
        if self.data_processing_cache is not None:
            data = self.data_processing_cache.copy(deep=True)
            data = data[data[self.target_columns] <= max]
            self.data_processing_cache = data
            return data

    def allow_updates(self, from_config=False, url=None, timezone_server=None, config_section=None):
        """
        Function

            Adds update manager

        Parameter

            from_config : bool
                True : load configuration from config file

            url : str
                url to get data from

            timezone_server : str
                timezone of server data

            config_section : str
                section of config to get configuration from

        Return

            None
        """
        if from_config is False:
            self.update_manager = UpdateManager(url, timezone_server)
        else:
            if config_section is None:
                raise ValueError('Specify config section')
            else:
                url = Configuration.getConfigValue(
                    config_section, 'update_server_url')
                self.timezone_server = Configuration.getConfigValue(
                    config_section, 'timezone_server')
                self.update_manager = UpdateManager(url, self.timezone_server)

    def load_cached_file(self):
        """
        loads cached data
        """
        module_logger.info(f'{self.name}: Loading cached files if available.')
        cached_files_available = check_file_cache(
            self.data_cache_path, self.data_cache_name)
        if cached_files_available:
            module_logger.info('Cached files available. Trying to load')
            data_path = self.data_cache_path + self.data_cache_name
            module_logger.info('Using path: ' + data_path)
            self.load_data_from_csv(data_path, sep=',')
            self.format_time_column(
                column_name=self.time_column_name, timezone=self.timezone_server)
            self.set_dataset_frequency(self.data_frequency)
            module_logger.info(
                f'{self.name}: Cached data loaded, time column formatted and dataset frequency is set!')

    def read_config_from_file(self, section, folder):
        """
        Function

            read configuration from config

        Parameter

            section : str
                section in config where configuration is written

            folder : str
                folder to read config from

        Return

            data is stored in class
        """
        module_logger.info(f'{self.name}: Reading config file.')
        self.data_cache_path = config_path_to_full_path(folder, 'dataset_' + section, 'data_cache_folder',
                                                        check_path_end=True)
        self.data_cache_name = Configuration.getConfigValue(
            'dataset_' + section, 'data_cache_name')
        self.do_clean_start = string_to_bool(
            Configuration.getConfigValue('dataset_' + section, 'clean_start'))
        self.target_columns = Configuration.getConfigValue(
            'dataset_' + section, 'target_column')
        self.time_column_name = Configuration.getConfigValue(
            'dataset_' + section, 'time_column_server_data')
        self.raw_dataset_frequency = Configuration.getConfigValue(
            'dataset_' + section, 'raw_data_frequency')
        self.timezone_server = Configuration.getConfigValue(
            'dataset_' + section, 'timezone_server')

    def update_data(self, time_now):
        """
        Function

            update data with manager

        Parameter

            time_now : datetime
                current timestamp

        Return

            received data
        """
        if self.update_manager is None:
            raise ValueError(
                f'{self.name}: Update Manager not initialized! use allow_update to initialize')
        module_logger.info(
            f'{self.name}: Updating data. Requesting from server.')
        data, exception = self.update_manager.request_data(
            self.last_timestamp, time_now, return_exception_raised=True)
        if not data.empty:
            module_logger.info(f'{self.name}: New data found.')
            self.unpreprocessed_new_data = pd.concat(
                [self.unpreprocessed_new_data, data[[self.time_column_name, self.target_columns]]])
            return data

    def standardize_new_data(self):
        """
        Function

            put new data in standard format

        Parameter

            None

        Return

            None
        """
        if self.unpreprocessed_new_data.empty:
            module_logger.info(
                f'{self.name}: No New data to preprocess. Skipping')
        else:
            module_logger.info(f'{self.name}: Standardizing new data.')
            new_data = self.unpreprocessed_new_data
            new_data[self.time_column_name] = pd.to_datetime(
                new_data[self.time_column_name])
            new_data[self.target_columns] = new_data[self.target_columns].astype(
                'float')
            if self.timezone_server is not None:
                new_data[self.time_column_name] = new_data[self.time_column_name].dt.tz_localize(
                    self.timezone_server)
            new_data.index = new_data[self.time_column_name]
            new_data = new_data.drop(columns=[self.time_column_name])
            self.data_processing_cache = new_data.dropna()
            self.unpreprocessed_new_data = pd.DataFrame()
            module_logger.info(f'{self.name}: Data now standardized.')

    def using_cumsum(self, df, change_resolution_to):
        """
        deprecated
        """
        result = pd.melt(df[[self.target_columns, 'date_time']],
                         id_vars=['power (kW/min)'], var_name='usage', value_name='date')
        result['usage'] = result['usage'].map(
            {'start_date': 1, 'end_date': -1})
        result['usage'] *= result['power (kW/min)']
        result = result.set_index('date')
        result = result[['usage']].resample('T').sum().fillna(0).cumsum()
        usage = result.resample(change_resolution_to).sum()
        return usage

    def add_data(self, change_resolution_to=None):
        """
        Function

            adds standardized new data to dataset

        Parameter

            change_resolution_to : str or None
                changes new data to another resolution

        Return

            Data is stored in class

        """
        module_logger.info(f'{self.name}: Adding data.')
        if self.data is None:
            self.data = self.data_processing_cache
            print(1)
        else:
            data_processing_cache = self.data_processing_cache
            if change_resolution_to is not None and data_processing_cache is not None:
                data_processing_cache['date_time'] = data_processing_cache.index
                data_processing_cache['Watt'] = data_processing_cache[self.target_columns] / (
                    data_processing_cache['date_time'].diff() / pd.to_timedelta('1H'))
                data_processing_cache.drop(columns=['date_time'], inplace=True)
                data_processing_cache.drop_duplicates(keep=False, inplace=True)
                data_processing_cache = data_processing_cache[data_processing_cache.index.duplicated(
                ) != True]
                data_processing_cache = data_processing_cache.resample(
                    '1s').asfreq()
                data_processing_cache = data_processing_cache.resample(
                    '15Min').sum()
                data_processing_cache[self.target_columns] = data_processing_cache['Watt'] / 3600
                data_processing_cache = data_processing_cache.drop(columns=[
                                                                   'Watt'])
            self.data = pd.concat([self.data, data_processing_cache])
            self.data.index = pd.to_datetime(self.data.index)
            self.data = self.data.groupby(by=self.data.index).first()

    def resample_data_to_raw_frequency(self, frequency=None):
        """
        Function

            resamples data to specified raw frequency

        Parameter

            frequency : str
                raw frequency

        Return

            resampled data

        """
        if frequency is None:
            self.data_frequency = self.raw_dataset_frequency
            frequency = self.data_frequency
        else:
            self.data_frequency = frequency
        try:
            if len(self.data) <= 20:
                pass
            else:
                self.data = self.data.asfreq('1s').resample(frequency).mean()
        except ValueError:
            self.data = self.data.groupby(self.data.index).mean()
            self.data = self.data.asfreq('1s').resample(frequency).mean()
        return self.data

    def resample_data_to_raw_frequency_load(self, frequency=None):
        """
        Function

            Load data resampler to raw frequency

        Parameter

            frequency : str
                frequency of raw data

        Return

            None or resampled data

        """
        if frequency is None:
            self.data_frequency = self.raw_dataset_frequency
            frequency = self.data_frequency
        else:
            self.data_frequency = frequency
        self.data = self.data.resample(
            '1Min').interpolate().resample(frequency).mean()
        return self.data

    def save_cached_data(self):
        """
        Function

            save current data

        Parameter

            None

        Return

            None
        """
        self.save_data(self.data_cache_path, self.data_cache_name)


class WeatherDataManager(TimeSeriesDataFrame):
    def __init__(self, name):
        super().__init__()
        self.name = name
        self.weather_forecast_data = pd.DataFrame()
        self.update_manager = None  # set by allow_updates
        self.historical_data = None
        self.raw_dataset_frequency = None
        self.do_clean_start = None
        self.target_columns = None
        self.time_column_name = None
        self.data_cache_path = None
        self.data_cache_name = None
        self.unpreprocessed_new_data = pd.DataFrame()
        self.data_processing_cache = None
        self.timezone_server = None
        self.weather_forecast_name = None
        self.weather_forecast_path = None
        self.weather_forecast_url = None
        self.time_column_server_data_forecast = None
        self.lat = None
        self.lon = None
        self.long_term_data_path = None
        self.long_term_data_name = None
        self.long_term_weather_data = None

    def allow_updates(self, from_config=False, url=None, timezone_server=None, config_section=None):
        """
        Function

            Adds update manager

        Parameter

            from_config : bool
                True : load configuration from config file

            url : str
                url to get data from

            timezone_server : str
                timezone of server data

            config_section : str
                section of config to get configuration from


        Return

            None
        """
        if from_config is False:
            self.update_manager = UpdateManager(url, timezone_server)
        else:
            if config_section is None:
                raise ValueError('Specify config section')
            else:
                url = Configuration.getConfigValue(
                    config_section, 'update_server_url')
                self.timezone_server = Configuration.getConfigValue(
                    config_section, 'timezone_server')
                self.update_manager = UpdateManager(url, self.timezone_server)

    def load_cached_historical_data(self):
        """
        Function

            loads cached historical weather data
        """
        cached_files_available = check_file_cache(
            self.data_cache_path, self.data_cache_name)
        if cached_files_available:
            module_logger.info(
                f'{self.name}: Cached files available. Trying to load')
            data_path = self.data_cache_path + self.data_cache_name
            module_logger.info(f'{self.name}: Using path: ' + data_path)
            self.load_data_from_csv(data_path, sep=',')
            self.format_time_column(
                column_name=self.time_column_name, timezone=self.timezone_server)
            self.set_dataset_frequency(self.raw_dataset_frequency)
            module_logger.info(
                f'{self.name}:Cached data loaded, time column formatted and dataset frequency is set!')

    def load_cached_weather_forecast(self):
        """
        Function

            loads cached forecast weather data
        """
        cached_files_available = check_file_cache(
            self.weather_forecast_path, self.weather_forecast_name)
        if cached_files_available:
            module_logger.info(
                f'{self.name}: Cached files available. Trying to load')
            data_path = self.weather_forecast_path + self.weather_forecast_name
            module_logger.info(f'{self.name}: Using path: ' + data_path)
            with open(self.weather_forecast_path + self.weather_forecast_name, 'r') as input_file:
                parsed = ast.literal_eval(json.load(input_file))
            forecast_data = self.update_manager.parse_forecast_data(parsed)
            forecast_data_reformatted_time_column = self.format_time_new_data(forecast_data,
                                                                              self.time_column_server_data_forecast)
            forecast_in_data_frequency = forecast_data_reformatted_time_column.resample(
                self.raw_dataset_frequency).interpolate()
            self.weather_forecast_data = forecast_in_data_frequency
            module_logger.info(
                f'{self.name}: Cached data loaded, time column formatted and dataset frequency is set!')
            return forecast_in_data_frequency

    def load_cached_long_term_weather(self):
        """
        Function

            loads cached NASA weather data
        """
        cached_files_available = check_file_cache(
            self.long_term_data_path, self.long_term_data_name)
        if cached_files_available:
            module_logger.info(
                f'{self.name}: Cached files available. Trying to load')
            data_path = self.long_term_data_path + self.long_term_data_name
            module_logger.info(f'{self.name}: Using path: ' + data_path)
            long_term_weather = pd.read_csv(data_path)
            long_term_weather.index = pd.to_datetime(
                long_term_weather['Unnamed: 0'])
            long_term_weather = long_term_weather.drop(columns=['Unnamed: 0'])
            long_term_weather.index = long_term_weather.index + \
                pd.to_timedelta('1y')
            self.long_term_weather_data = long_term_weather

    def read_config_from_file(self, current_path):
        """
        Function

            read configuration from config

        Parameter

            current_path : str
                path were config is situated
        """

        self.data_cache_path = config_path_to_full_path(current_path, 'dataset_historical_weather', 'data_cache_folder',
                                                        check_path_end=True)
        self.data_cache_name = Configuration.getConfigValue(
            'dataset_historical_weather', 'data_cache_name')
        self.do_clean_start = string_to_bool(Configuration.getConfigValue(
            'dataset_historical_weather', 'clean_start'))
        self.time_column_name = Configuration.getConfigValue(
            'dataset_historical_weather', 'time_column_server_data')
        self.raw_dataset_frequency = Configuration.getConfigValue(
            'dataset_historical_weather', 'raw_data_frequency')
        self.weather_forecast_path = config_path_to_full_path(current_path, 'data_weather_forecast',
                                                              'data_cache_folder')
        self.weather_forecast_name = Configuration.getConfigValue(
            'data_weather_forecast', 'data_cache_name')
        self.weather_forecast_url = Configuration.getConfigValue(
            'data_weather_forecast', 'url')
        self.time_column_server_data_forecast = Configuration.getConfigValue('data_weather_forecast',
                                                                             'time_column_server_data')
        self.timezone_server = Configuration.getConfigValue(
            'dataset_historical_weather', 'timezone_server')
        self.lat = Configuration.getConfigValue('data_nasa_weather', 'lat')
        self.lon = Configuration.getConfigValue('data_nasa_weather', 'lon')
        self.long_term_data_path = config_path_to_full_path(
            current_path, 'data_nasa_weather', 'data_cache_folder')
        self.long_term_data_name = Configuration.getConfigValue(
            'data_nasa_weather', 'data_cache_name')

    def update_data(self, url_list):
        """
        Function

            update data with manager

        Parameter

            url_list : list
                list of urls

        Return

            data from server
        """
        if self.update_manager is None:
            raise ValueError(
                'Update Manager not initialized! use allow_update to initialize')
        data = self.update_manager.request_historical_weather_data_from_server(
            url_list)
        if not data.empty:
            self.unpreprocessed_new_data = pd.concat(
                [self.unpreprocessed_new_data, data])
            return data

    def standardize_new_data(self):
        """
        Function

            put new data in standard format

        Parameter

            load : bool
                True if load data is standardized

        Return

            None
        """
        if self.unpreprocessed_new_data.empty:
            module_logger.info(
                f'{self.name}: No New data to preprocess. Skipping')
        else:
            new_data = self.unpreprocessed_new_data
            new_data[self.time_column_name] = pd.to_datetime(
                new_data[self.time_column_name], unit='s')
            if self.timezone_server is not None:
                new_data[self.time_column_name] = new_data[self.time_column_name].dt.tz_localize(
                    self.timezone_server)
            new_data.index = new_data[self.time_column_name]
            new_data = new_data.drop(columns=[self.time_column_name])
            self.data_processing_cache = new_data.dropna()
            self.unpreprocessed_new_data = pd.DataFrame()

    def add_data(self):
        """
        Function

            adds standardized new data to dataset

        Parameter

            None

        Return

            None

        """
        if self.data is None:
            self.data = self.data_processing_cache
        else:
            self.data = pd.concat([self.data, self.data_processing_cache])
            self.data.index = pd.to_datetime(
                self.data.index)  # .tz_conver(self.tz_info)

    def resample_data_to_raw_frequency(self, frequency=None):
        """
        Function

            resamples data to specified raw frequency

        Parameter

            frequency : str
                raw frequency

        Return

            resampled data

        """
        if frequency is None:
            self.data_frequency = self.raw_dataset_frequency
            frequency = self.data_frequency
        else:
            self.data_frequency = frequency
        self.data = self.data.resample(frequency).mean()
        return self.data

    def save_cached_data(self):
        """
        Function

            save current data

        Parameter

            None

        Return

            None
        """
        self.save_data(self.data_cache_path, self.data_cache_name)

    def check_weather_forecast_available(self, latest_forecast_data_link):
        """
        Function

            checks if weather forecst available cached or on server

        Parameter

            latest_forecast_data_link : str
                URL

        Return

            data or False
        """
        if not latest_forecast_data_link == []:
            if latest_forecast_data_link[-1] == self.weather_forecast_name:
                return check_file_cache(self.weather_forecast_path, self.weather_forecast_name)
            elif not latest_forecast_data_link[-1] == self.weather_forecast_name:
                return False
        else:
            return check_file_cache(self.weather_forecast_path, self.weather_forecast_name)

    def load_weather_forecast(self, weather_forecast_data_link):
        """
        Function

            get weather forecat from server and pre-process

        Parameter

            weather_forecast_data_link : str
                link to weather forecat data on server

        return

            pre-processed weather forecast
        """
        weather_forecast_available = self.check_weather_forecast_available(
            weather_forecast_data_link)
        if weather_forecast_available:
            with open(self.weather_forecast_path + self.weather_forecast_name, 'r') as input_file:
                parsed = ast.literal_eval(json.load(input_file))
            forecast_data = self.update_manager.parse_forecast_data(parsed)
        else:
            if weather_forecast_data_link == []:
                forecast_data = None
            else:
                forecast_data_url = self.weather_forecast_url + \
                    weather_forecast_data_link[-1]
                forecast_data = self.update_manager.request_forecast(forecast_data_url,
                                                                     self.weather_forecast_path,
                                                                     weather_forecast_data_link[-1])
        if not forecast_data.empty:
            forecast_data_reformatted_time_column = self.format_time_new_data(forecast_data,
                                                                              self.time_column_server_data_forecast)
            forecast_in_data_frequency = forecast_data_reformatted_time_column.resample(
                self.data_frequency).interpolate()
            self.weather_forecast_data = forecast_in_data_frequency
        else:
            forecast_in_data_frequency = pd.DataFrame()
        return forecast_in_data_frequency

    def format_time_new_data(self, data, time_column, timezone=None):
        """
        Function

            Format time column of new data

        Parameter

            data : pd.DataFrame
                new data

            time_column : str
                time column in data

            timezone : str
                timezone of time data

        Return

            data : pd.DataFrame
                data with formated time data
        """
        data = data.reset_index()
        data[time_column] = pd.to_datetime(data[time_column], unit='s')
        timezone_in_data = data.loc[data.last_valid_index(
        ), time_column].tzinfo
        if timezone_in_data is None:
            data[time_column] = data[time_column].dt.tz_localize(
                self.timezone_server)
        else:
            if not timezone_in_data == timezone:
                data[time_column] = data[time_column].dt.tz_convert(timezone)
            else:
                pass
        data.index = data[time_column]
        data = data.drop(columns=[time_column, 'index'])
        return data

    def __retrieve_last_available_date(self):
        if self.data is None and self.data.empty:
            return None
        return self.data.last_valid_index()

    def get_and_process_long_term_weather_data(self, time_now):
        """
        Function

            Load NASA weather data

        Parameter

            time_now : str
                current timestamp

        Return

            None
        """
        if self.long_term_weather_data is None or self.long_term_weather_data.index[-1].month < (time_now - pd.to_timedelta('4D')).month:
            weather_data = pd.DataFrame()
            for y in range(11, -1, -1):
                response, exception = self.update_manager.request_nasa(
                    self.lat, self.lon, time_now, start=None, counter=y)
                if exception is False:
                    weather_data_raw = response.text
                    parsed = json.loads(weather_data_raw)
                    properties = parsed.get('properties')
                    parameters = properties.get('parameter')
                    weather_data_tmp = pd.DataFrame.from_dict(parameters)
                    weather_data_tmp.index = pd.to_datetime(
                        weather_data_tmp.index, format='%Y%m%d%H', utc=True)
                    weather_data = weather_data.append(weather_data_tmp)
            self.long_term_weather_data = weather_data
            self.long_term_weather_data = self.long_term_weather_data.groupby(
                by=self.long_term_weather_data.index, as_index=True).first()
            self.long_term_weather_data.to_csv(
                self.long_term_data_path + self.long_term_data_name)
        else:
            pass
