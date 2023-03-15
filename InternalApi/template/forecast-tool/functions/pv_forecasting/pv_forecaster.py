import os

import numpy as np
import pandas as pd
from astral.sun import sun
from tensorflow import data as data_tensor

from functions.data.data_manager import DataManager
from functions.data.data_preparation import check_for_days_with_large_amount_of_missing_values, fill_night_data, fill_mv_potentialy
from functions.features.features import last_days_max, add_weather_data, prepare_long_term_weather, cyclic_time
from functions.scaling.scaling import Scaler
from functions.timeseriesdata.forecasting_functions import prepare_dataset_for_forecasting, round_up
from functions.utils.parser import time_to_str


def supervised_data_pv_long_term(preprocessed_long_weather, dataset_with_horizon, forecast_mode, scaler, time_now,
                                 split=0.9):
    """
    Function

        Algorithm for long term PV forecast needs special data format shown under principle

    Parameter

        preprocessed_long_weather : pd.DataFrame
            data with NASA weather data

        dataset_with_horizon : pd.DataFrame
            prolonged data, data with forecast horizon

        forecast_mode : bool
            if true scaler is not refitted

        scaler : scaler
            scaler used to scale data

        time_now : datetime
            current timestamp

        split : float
            split data into train and test data for training

    Return

        dict with train, val and forecast data

    Principle

        Dataset has to be in a 3D Shape with [Samples, Timestep, Features]
        Small Example:

        Raw Dataset Sample:
        Timestamp| Target | Feature 1 | Feature 2 |
        -------------------------------------------
        T1       | M1     | F1(T1)  | F2(T1)      |
        T2       | M2     | F1(T2)  | F2(T2)      |
        T3       | M3     | F1(T3)  | F2(T3)      |
        ...      | ...    | ...     | ...         |

        3 Datasets are needed:
        Train_x, Train_y, Forecast_x

        Train_x contains all features or observations describing target measurement at specific timestamp
        Train_y contains all data which are the target measurements
        forecast_x is the same as train_x with only values used for future observations

        For long term PV:
        Train_x consists of weather variables of NASA Dataset (shape: [varying, 24*90 = 2160, 90 (which is feature 1-90)]
        Train_y are the PV measurements [varying, 2160, 1 (which is target)]
        Forecast_x weather variables NASA Dataset over future hours [1, 2160, 90 (which is feature 1-90)]

        there is also a Split of train_x and train_y into train and val data. e.g. 0.9 of the data are in train and 0.1 in val
    """
    train_data = dataset_with_horizon.iloc[:-90 * 24, ]
    train_y = train_data.iloc[:, :1]
    if forecast_mode is False:
        scaler.scaler_fit(preprocessed_long_weather, train_y, save=True)
    scaler.set_index(dataset_with_horizon.iloc[-90 * 24:, :1].index)
    weather_data_array = []
    train_true = []
    preprocessed_long_weather_scaled, train_y_scaled = scaler.scaler_transform(preprocessed_long_weather, train_y)
    train_y_scaled['date'] = train_y_scaled.index.date
    for i in range(2, len(train_y_scaled.date.unique()) - 90):
        hist = train_y_scaled.iloc[24 * (i - 2):24 * i, :1].values
        a = pd.to_datetime(train_y_scaled.iloc[24 * i, 1])
        index = \
            np.where(
                preprocessed_long_weather_scaled.index == pd.to_datetime(train_y_scaled.iloc[24 * i, 1], utc=True))[0]
        if index:
            index = index[0]
            weather = preprocessed_long_weather_scaled.iloc[
                      index:index + 90 * 24].values
        else:
            weather = np.nan
        true = train_y_scaled.iloc[24 * i:24 * i + 90 * 24, :1].values
        if np.isnan(hist).any() or len(weather) < 2160 or len(true) < 2160:
            pass
        else:
            weather_data_array.append(weather)
            train_true.append(true)
    index = np.where(preprocessed_long_weather_scaled.index >= time_now)[0][0]
    weather_data_forecast = np.array(preprocessed_long_weather_scaled.iloc[
                                     index:index + 90 * 24].values).reshape(1, 90 * 24, 170)
    tensors = data_tensor.Dataset.from_tensor_slices(
        tuple([np.array(weather_data_array), np.array(train_true)])).shuffle(192)
    part = int(len(weather_data_array) * split)
    train = tensors.take(part)
    val = tensors.skip(part)
    forecast = weather_data_forecast.reshape(1, 2160, 170)
    return {'train': train, 'val': val, 'forecast': forecast}


def split_short_term_dataset(dataset):
    """
    Function

        splitting data into train and forecast

    Parameter

        dataset : pd.DataFrame
            dataset to split

    Return

        train_x : pd.DataFrame
            trainingdataset with features

        train_y : pd.DataFrame or pd.Series
            training dataset containing measurements

        forecast_x : pd.DataFrame
            forecast dataset with features
    """
    # everything until last two days of data are training
    dataset_train = dataset.iloc[:-96 * 2, :].dropna()
    train_x, train_y = dataset_train.iloc[:, 1:], dataset_train.iloc[:, :1]
    # last 96*2 (2 Days of data) are for forecasting
    forecast_x = dataset.iloc[-96 * 2:, 1:]
    return train_x, train_y, forecast_x


class PVForecasting:
    """
    Function

        Class for PV Forecasting

    Attributes

        forecasting_mode : bool
            only forecasting or also training

        dm : class
            DataManager containing PV measurements

        dataset_short_term_forecast : None or pd.DataFrame or dict
            dataset prepared for short term load forecast

        dataset_long_term_forecast : pd.DataFrame or dict
            dataset prepared for long term load forecast

        trick : Bool
            Pushes dataset together (NOT RECOMMENDED! but data was limited)

        sun_times : astral.suntimes
            timepoints of sunset and sunrise

        filled_dataset : pd.DataFrame
            dataset with nan filled (if possible)

        scaler_pv_short : class scaler
            scaler for short term forecasting

        scaler_pv_long : class scaler
            scaler for long term forecasting

    Methods

        get_data_from_server()
            request measurements from server

        preprocess_data
            preprocessed data e.g. missing values

        create_dataset_short_term_forecast
            prepare dataset for short term forecasting

        persistency_forecast
            calculate persistency forecast

        create_dataset_long_term_forecast
            prepare dataset for long term forecasting

        save_prediction
            saves prediction

        add_statistical_forecasts_short
            adds statistical forecast e.g. pv power max last 5 days

        add_statistical_forecasts_long
            adds currently Nothing
    """

    def __init__(self, sun_times, folder):
        """
        Attributes

            forecasting_mode : bool
                only forecasting or also training

            dm : class
                DataManager containing PV measurements

            dataset_short_term_forecast : None or pd.DataFrame or dict
                dataset prepared for short term load forecast

            dataset_long_term_forecast : pd.DataFrame or dict
                dataset prepared for long term load forecast

            trick : Bool
                Pushes dataset together (NOT RECOMMENDED! but data was limited)

            sun_times : astral.suntimes
                timepoints of sunset and sunrise

            filled_dataset : pd.DataFrame
                dataset with nan filled (if possible)

            scaler_pv_short : class scaler
                scaler for short term forecasting

            scaler_pv_long : class scaler
                scaler for long term forecasting

        """
        self.folder = folder
        self.dm = DataManager('pv')
        self.folder = folder
        self.dataset_short_term_forecast = None
        self.dataset_long_term_forecast = None
        self.trick = True
        self.load_all_data()
        self.sun_times = sun_times
        self.filled_dataset = None
        self.scaler_pv_short = None
        self.scaler_pv_long = None
        self.pv_power_max_short = None

    def load_all_data(self):
        """
        Function

            loads all needed data for load forecasting (Scaler, PSLP and Load Measurements)

        Parameter

            None

        Return

            None

        """
        self.__init_data_manager_and_suntimes(self.folder)
        self.scaler_pv_short = Scaler(self.folder, name='pv_short', forecasting_mode=True)
        self.scaler_pv_long = Scaler(self.folder, name='pv_long', forecasting_mode=True)

    def __init_data_manager_and_suntimes(self, folder):
        self.dm.read_config_from_file('pv', folder)
        self.dm.allow_updates(from_config=True, config_section='dataset_pv')
        self.dm.load_cached_file()

    def get_data_from_server(self, time_now):
        """
        Function

            request measurements from server

        Parameter

            time_now : datetime
                current time

        Return

            weather_links : pd.DataFrame
                empty if no new weather data is available else links
        """
        weather_links = self.dm.update_data(time_now)
        self.dm.standardize_new_data()
        self.dm.add_data()
        self.dm.resample_data_to_raw_frequency()
        if weather_links is not None:
            return weather_links['weather_data']
        else:
            return pd.DataFrame()

    def preprocess_data(self):
        """
        Function

            Pre-Process data

        Principle

            1. Outlier removal
            2. missing values
            3. save
            4. try to fill missing values
            5. save filled dataset


        Return

            None return is stored in class

        """
        self.dm.delete_outliers(max=380000, min=0)
        self.dm.show_missing_values()
        self.dm.save_cached_data()
        self.filled_dataset = fill_mv_potentialy(self.dm.data.copy(), self.sun_times, self.dm.data_frequency,
                                                 self.dm.target_columns)
        self.filled_dataset = self.dm.data

    def create_dataset_short_term_forecast(self, time_now, historical_weather_data, forecast_data, forecast_mode=False,
                                           split=0.9):
        """
        Function

            prepare dataset for short term forecasting

        Parameter

            time_now : datetime
                current time

            historical_weather_data : pd.DataFrame
                historical weather data collected

            forecast_data : pd.DataFrame
                weather forecasts from OWM

            forecast_mode : bool
                if scaler is refittet or not

            split : float [0, 1]
                split of validation and training


        Return

            dataset_short_term_forecast : dict with tensors
                train, val, forecast datasets

        """

        # resample all data
        pv_data = self.filled_dataset.copy(deep=True).resample('15Min').mean()
        dataset_with_horizon = prepare_dataset_for_forecasting(pv_data, '48H', '15Min',
                                                               time_now=time_now, repair_missing_values=False)
        weather_data = historical_weather_data.resample('15Min').interpolate(method='polynomial', order=2)
        forecast_data = forecast_data.resample('15Min').interpolate(method='polynomial', order=2)
        # generate additional feature
        pv_data_with_last_days_max = last_days_max(dataset_with_horizon, target_column=self.dm.target_columns, days=5,
                                                   fill_na=False)
        self.pv_power_max_short = pv_data_with_last_days_max.iloc[-96 * 2:, 1:]
        dataset_short_term_forecast = add_weather_data(pv_data_with_last_days_max, weather_data, forecast_data)
        dataset_short_term_forecast = cyclic_time(dataset_short_term_forecast, day=True, year=True)
        # split data
        train_x, train_y, forecast_x = split_short_term_dataset(dataset_short_term_forecast)
        if forecast_mode is False:
            self.scaler_pv_short.scaler_fit(dataset_y=train_y, dataset_x=train_x, save=True)
        train_x_scaled, train_y_scaled = self.scaler_pv_short.scaler_transform(dataset_y=train_y, dataset_x=train_x)
        forecast_x_scaled = self.scaler_pv_short.scaler_transform_for_prediction(forecast_x)
        part = int(len(train_y_scaled) * split)
        # array to tensor with specific shapes [samples = variable, timestep = 1, features = fixed value]
        tensors = data_tensor.Dataset.from_tensor_slices(
            tuple([np.array(train_x_scaled.values.reshape(train_x_scaled.shape[0], 1, train_x_scaled.shape[1])),
                   np.array(train_y_scaled.values)])).shuffle(192)
        train = tensors.take(part)
        val = tensors.skip(part)
        # forecast = forecast_x_scaled.reshape(forecast_x_scaled.shape[0], 1, forecast_x_scaled.shape[1])
        forecast = forecast_x_scaled.reshape(forecast_x_scaled.shape[0], 1, forecast_x_scaled.shape[1])

        self.dataset_short_term_forecast = {'train': train, 'val': val, 'forecast': forecast}
        return self.dataset_short_term_forecast

    def persistency_forecast(self, time_now):
        """
        Function

            calculate persistency forecast

        Parameter

            time_now : datetime
                current time

        Return

            Persistency Forecast

        """
        data = self.filled_dataset.loc[self.filled_dataset.index >= time_now - pd.to_timedelta('2D')].copy(deep=True)
        data.index = data.index + pd.to_timedelta('2D')
        return data[self.dm.target_columns]

    def persistency_forecast_long(self, time_now):
        """
        Function

            calculate persistency forecast

        Parameter

            time_now : datetime
                current time

        Return

            Persistency Forecast

        """
        data = self.dm.data.copy(deep=True)
        data = data.resample('1H').mean()
        data = data.loc[data.index >= time_now - pd.to_timedelta('90D')].copy(deep=True)
        data.index = data.index + pd.to_timedelta('90D')
        return data[self.dm.target_columns]

    def create_dataset_long_term_forecast(self, time_now, long_term_weather_data, forecast_mode):
        """
        Function

            prepare dataset for long term forecasting

        Parameter

            time_now : datetime
                current time

            long_term_weather_data : pd.DataFrame
                NASA long term weather data

            forecast_mode : bool
                if forecast mode is activated

        Return

            dataset_long_term_forecast : dict of tensors
                train, val, forecast datasets

        """
        pv_data = self.filled_dataset.copy(deep=True).resample('1H').mean()
        dataset_with_horizon = prepare_dataset_for_forecasting(pv_data, '90D', '1H',
                                                               time_now=time_now, repair_missing_values=False)
        preprocessed_long_weather = prepare_long_term_weather(long_term_weather_data)
        preprocessed_long_weather = preprocessed_long_weather.copy()
        if self.trick is True:
            dataset_with_horizon = dataset_with_horizon.copy()
            dataset_with_horizon['date'] = dataset_with_horizon.index.date
            preprocessed_long_weather['date'] = preprocessed_long_weather.index.date
            missing_value_days, frame = check_for_days_with_large_amount_of_missing_values(0, pv_data, '1H')
            dataset_with_horizon = dataset_with_horizon[~dataset_with_horizon['date'].isin(missing_value_days)]
            preprocessed_long_weather = preprocessed_long_weather[
                ~preprocessed_long_weather['date'].isin(missing_value_days)]
            preprocessed_long_weather = preprocessed_long_weather.drop(columns=['date'])
        self.dataset_long_term_forecast = supervised_data_pv_long_term(preprocessed_long_weather, dataset_with_horizon,
                                                                       forecast_mode, self.scaler_pv_long, time_now)
        return self.dataset_long_term_forecast

    def save_prediction(self, time_now, data, part):
        """
        Function

            saves prediction

        Parameter

            time_now : datetime
                current

            data : pd.DataFrame
                data to save

            part : str
                either LF, weather or PV

        Return

            None
        """
        if not os.path.isdir(self.folder + f'/resources/03_predictions/{part}/'):
            os.makedirs(self.folder + f'/resources/03_predictions/{part}/')
        data.to_csv(self.folder + f'/resources/03_predictions/{part}/{time_to_str(time_now)}.csv')

    def correct_ai_prediction(self, ai_prediction):
        """

        :param ai_prediction:
        :return:
        """
        ai_prediction['date'] = ai_prediction.index.date
        for date in ai_prediction['date'].unique():
            data_of_day = ai_prediction[ai_prediction['date'] == date].copy(deep=True)
            s = sun(self.sun_times.observer, date=date)
            night_time_data = data_of_day[(data_of_day.index <= s['sunrise']) | (
                    data_of_day.index >= s['sunset'])]
            night_time_data = fill_night_data(night_time_data, 'power')
            ai_prediction.loc[night_time_data.index, 'power'] = night_time_data['power']
        return ai_prediction.drop(columns=['date'])

    def add_statistical_forecasts_short(self, ai_prediction, time_now):
        """
        Function

            adding statistical forecasts like last days max or persistency & save prediction

        Parameter

            ai_prediction : pd.DataFrame
                prediction done by AI

            time_now : datetime
                current time

        Return

            combined_forecast : pd.DataFrame
                output of all short term prediction methods in one dataframe
        """
        combined_forecast = ai_prediction.copy(deep=True)  # self.correct_ai_prediction()
        combined_forecast['last_days_max'] = self.pv_power_max_short['last_days_max']
        combined_forecast['persistency'] = self.persistency_forecast(round_up(time_now, '15Min'))
        combined_forecast = combined_forecast.fillna(0)
        self.save_prediction(time_now, combined_forecast, 'pv_short')
        return combined_forecast

    def add_statistical_forecasts_long(self, ai_prediction, time_now):
        """
        Function

            save prediction

        Parameter

            ai_prediction : pd.DataFrame
                prediction done by AI

            time_now : datetime
                current time

        Return

            combined_forecast : pd.DataFrame
                output of all long term prediction methods in one dataframe
        """
        # corrected_ai_prediction = self.correct_ai_prediction(ai_prediction.copy(deep=True))
        combined_forecast = ai_prediction.copy(deep=True)
        combined_forecast['last_days_max'] = self.pv_power_max_short['last_days_max']
        combined_forecast['persistency'] = self.persistency_forecast_long(round_up(time_now, '15Min'))
        combined_forecast = combined_forecast.fillna(0)
        self.save_prediction(time_now, combined_forecast, 'pv_long')
        return ai_prediction
