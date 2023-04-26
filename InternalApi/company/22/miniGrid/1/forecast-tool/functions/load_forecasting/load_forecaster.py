import os

import numpy as np
import pandas as pd

from functions.data.data_augmentation import data_augmentation
from functions.data.data_manager import DataManager
from functions.load_forecasting.PSLP import PersonalizedStandardizedLoadProfile_variable_length
from functions.scaling.scaling import Scaler
from functions.timeseriesdata.forecasting_functions import round_up
from functions.utils.parser import time_to_str

from functions.Configuration.configuration_class import Configuration


class LoadForecasting:
    def __init__(self, folder):
        self.dm = DataManager(name='load')
        self.folder = folder
        self.pslp_short = None
        self.pslp_long = None
        self.dataset_short_term_forecast = None
        self.dataset_long_term_forecast = None
        self.scaler_load_short = None
        self.scaler_long_term = None
        self.load_all_data()

    def recombine_forecast(self, prediction):
        load_prediction = prediction.get('prediction')
        pslp = prediction.get('optional_data')
        pslp = pslp.values.reshape(pslp.shape[0] * pslp.shape[1], 1)
        load_prediction['power'] = load_prediction['power'] + pslp[:, 0]
        return load_prediction

    def load_all_data(self):
        """
        Function

            loads all needed data for load forecasting (Scaler, PSLP and Load Measurements)

        Parameter

            pslp_var : bool
                True : variable length PSLP is used
                False: PSLP with all data is used

        Return

            None

        """
        self.__init_data_manager()
        self.pslp_short = self.__init_pslp('short', '15Min')
        self.pslp_long = self.__init_pslp('long', '1h')
        self.scaler_load_short = Scaler(
            self.folder, name='lf_short', forecasting_mode=True)
        self.scaler_long_term = Scaler(
            self.folder, name='lf_long', forecasting_mode=True)

    def __init_data_manager(self):
        self.dm.read_config_from_file('lf', self.folder)
        self.dm.allow_updates(from_config=True, config_section='dataset_lf')
        self.dm.load_cached_file()

    def __init_pslp(self, part, data_frequency):
        pslp = PersonalizedStandardizedLoadProfile_variable_length(
            self.dm.target_columns, part, data_frequency)
        if not self.dm.data is None:
            if not self.dm.data.empty:
                pslp.preprocess_new_data(
                    incoming_data=self.dm.data.resample(data_frequency).mean())
        return pslp

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
        self.dm.update_data(time_now)
        self.dm.standardize_new_data()
        self.dm.data_processing_cache['absorbed_energy_since_last'] = self.dm.data_processing_cache[
            'absorbed_energy_since_last'] + 1000
        self.dm.delete_outliers_data_preprocessing_cache(max=9000)
        self.dm.add_data(change_resolution_to='15Min')
        self.dm.resample_data_to_raw_frequency()

    def to_current_date(self, time_now):
        """
        Function

            augments data to current date if measurements are not coming in

        Parameter

            time_now : datetime
                current timestamp

        Return

            Data without missing values up to current timestamp
        """
        data = self.dm.data.copy(deep=True)
        if data.last_valid_index() <= time_now - pd.to_timedelta(self.dm.data_frequency):
            missing_rest_dates = pd.date_range(data.last_valid_index() + pd.to_timedelta(self.dm.data_frequency),
                                               time_now -
                                               pd.to_timedelta(
                                                   self.dm.data_frequency),
                                               freq=self.dm.data_frequency)
            data = pd.concat([data, pd.DataFrame(index=missing_rest_dates)])
            data = data_augmentation(
                data, frequency=self.dm.data_frequency, column=data.columns[0])
            data = data.groupby(data.index).mean()
        return data

    def preprocess_data(self):
        """
        Function

            pre-process new data

        Principle

            Preprocess data
                1. Outlier removal
                2. missing values
                3. save
                4. save filled dataset
                5. ready up pslp

        Parameter

            None

        Return

            Data is stored in class

        """
        self.dm.delete_outliers(max=6000, min=0)
        if not len(self.dm.data) < 20:  # Prevents load of errors
            self.dm.show_missing_values()
            self.dm.missing_values_augmentation()
            self.dm.interpolate_data()
            self.dm.save_cached_data()
        else:
            self.dm.data = pd.DataFrame(index=(
                pd.date_range(start=self.dm.data.last_valid_index() - pd.to_timedelta('10D'),
                              end=self.dm.data.last_valid_index(), freq='15Min')))
            self.dm.data['absorbed_energy_since_last'] = 0
            self.dm.data = self.dm.data.asfreq('1s').resample('15Min').mean()
        self.pslp_short.preprocess_new_data(self.dm.data)
        self.pslp_long.preprocess_new_data(self.dm.data.resample('1H').mean())

    def __calc_fast_PSLP(self, data, pslp_window):
        pslp = np.zeros((data.shape[0], data.shape[1]))
        # pslp_std = np.zeros((data.shape[0], data.shape[1]))
        pslp[0, :] = data[0, :]
        for i in range(1, data.shape[0]):
            if i < pslp_window:
                pslp[i, :] = np.mean(data[:i, :], axis=0)
            else:
                pslp[i, :] = np.mean(data[i - pslp_window:i, :], axis=0)
            # pslp_std[i-pslp_window,:]= np.std(data[i-pslp_window:i,:,4], axis=0)

        return pslp  # , pslp_std

    def persistency_forecast(self, time_now):
        """
        Function

            calculate persistency forecast

        Parameter

            time_now : datetime
                current time

        Return

            data with persistency forecast

        """
        data = self.dm.data.loc[self.dm.data.index >=
                                time_now - pd.to_timedelta('2D')].copy(deep=True)
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

                data with persistency forecast

        """
        data = self.dm.data.copy(deep=True)
        data = data.resample('1H').mean()
        data = data.loc[data.index >= time_now -
                        pd.to_timedelta('90D')].copy(deep=True)
        data.index = data.index + pd.to_timedelta('90D')
        return data[self.dm.target_columns]

    def save_prediction(self, time_now, data, part):
        """
        Function

            saves prediction

        Parameter

            time_now : datetime
                current time
            data : pd.DataFrame
                data to save
            part : str
                either LF, weather or PV

        Return

            None
        """
        if not os.path.isdir(self.folder + f'/resources/03_predictions/{part}/'):
            os.makedirs(self.folder + f'/resources/03_predictions/{part}/')
        data.to_csv(
            self.folder + f'/resources/03_predictions/{part}/{time_to_str(time_now)}.csv')

    def add_statistical_forecasts_short(self, ai_prediction, time_now):
        """
        Function

            adding statistical forecasts like PSLP or persistency & save prediction

        Parameter

            ai_prediction : pd.DataFrame
                prediction done by AI
            time_now : datetime
                current time

        Return

            combined_forecast : pd.DataFrame
                output of all short term prediction methods in one dataframe
        """
        combined_forecast = ai_prediction.copy(deep=True)
        pslp_forecast = self.pslp_short.forecast(
            time_now, forecast_horizon='2D', historical_data=self.dm.data)
        combined_forecast['pslp'] = pslp_forecast['forecast']
        combined_forecast['persistency'] = self.persistency_forecast(
            round_up(time_now, '15Min'))
        combined_forecast = combined_forecast.fillna(0)
        self.save_prediction(time_now, combined_forecast, 'lf_short')
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
        combined_forecast = ai_prediction.copy(deep=True)
        pslp_forecast = self.pslp_long.forecast(
            time_now, forecast_horizon='90D', historical_data=self.dm.data.resample('1H').mean())
        combined_forecast['pslp'] = pslp_forecast['forecast']
        combined_forecast['persistency'] = self.persistency_forecast_long(
            round_up(time_now, '15Min'))
        combined_forecast = combined_forecast.fillna(0)
        self.save_prediction(time_now, combined_forecast, 'lf_long')
        return combined_forecast
