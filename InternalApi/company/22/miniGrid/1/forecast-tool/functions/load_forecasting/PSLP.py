import logging
from functions.utils.parser import string_to_bool
import numpy as np
import pandas as pd
from functions.Configuration.configuration_class import Configuration
from sklearn.metrics import mean_squared_error

module_logger = logging.getLogger('Forecasting-Tool.PSLP')


def calc_fast_PSLP(data, pslp_window):
    """
    Function

        Fast PSLP for Load Forecasting AI

    Parameter

        data : pd.DataFrame
            measured data

        pslp_window : int
            number of days to look back


        Return

            pslp

    """
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


class PersonalizedStandardizedLoadProfile_variable_length:
    """
     Function

         A class to calculate the Personalized Standardized Load Profile.

     Principle

         Personalized Standardized Load Profiles or short PSLP is one simple statistical approach to
         forecast future loads based on historical loads. The approach is a further development of the
         standardized load profiles which were derived by “Verband der Elektrizitätswirtschaft e.V.”
         (short: VDEW). The original profiles were derived by using measurements of 1209 different buildings
         but with the uprising implementation of smart meters the basic rules were adopted and measured
         data is used to derive suitable profiles for forecasting.

         In the basic approach there are 11 profiles derived from the measured data. These are divided by
         type of day (weekdays, Saturday, Sunday/vacation) and by season (transition, winter and summer).

         Usually public holidays are treated as a Sunday because load patterns are more equal to that then
         to a usual business day. Christmas and New Year’s Day are an exception from that rule because these
         days are treated as a Saturday if they are not on a Sunday.

         During preparation of the profiles measured data are sorted into the aforementioned categories.
         Then the profiles are calculated using the mean value of every point of time of time in the
         profiles.

         For the use in Mini-Grids the basic idea is used to generate a profile out of the measured data but
         the approach is further adapted to the findings in the data of the Mini-Grid. In the data no weekly
         seasonality was found and every day looked more like the previous day. Therefore, this
         differentiation was omitted. Also, no clear annual effects of lower and higher loads were
         determined. Because of that the differentiation between seasons was also dropped. This leaves the
         idea of averaging measurements of recent days to form a load profile behind. This, by any means,
         does not mean that this approach is suitable for all Mini-Grids as this were the results of
         research conducted by now.

         This class has an extension compared to the normal PSLP which is that it tries to estimate best
         length of window size (starting at 1 Day going back to 31Days). This can increase forecast
         accuracy when days are more or less fluctuating without a specific pattern.

     Attributes

         target_column : str
             name of column to predict

         holiday_times : list
             can be filled then it is checked if a date is holiday. This is currently not implemented
             as vacations were not found in measurements.

         data_frequency : str
             Frequency in which prediction should be done and incoming data is

         cache_dict : dict
             storage for classified data

         last_processed_timestep : pd.datetime
             last timestep classified

     Methods

         __init_dicts()
             inits storage dicts

         __vacation_dates()
             get vacation dates

         get_season_info()
             info on which season data is in

         check_for_christmas_new_year()
             checks if date is christmas (currently set to always false)

         check_if_vacation()
             checks if date is vacation (currently set to always false)

         get_day_info()
             if day is weekday or Saturday or Sunday (currently set to always week)

         classification_day()
             gather all info to classify day
             uses get_season_info() and get_day_info()

         sort_data_into_profile_cache()
             sorts data into cache storages

         preprocess_new_data()
             preprocesses new incoming data into classes

         round_up()
             rounds up to next timestep

         get_previous_profile()
             if correct profile is not available it uses previous one

         forecast()
             forecasts future loads



    """

    def __init__(self, target_column, part='short',  data_frequency='15Min'):

        self.target_column = target_column
        self.holiday_times = []
        self.data_frequency = data_frequency
        self.steps_per_day = int(pd.to_timedelta(
            '1D') / pd.to_timedelta(self.data_frequency))
        self.config_part = f'PSLP_{part}'
        self.seasonal = string_to_bool(
            Configuration.getConfigValue(self.config_part, 'seasonal'))
        self.weekly = string_to_bool(
            Configuration.getConfigValue(self.config_part, 'weekly'))
        self.set_fixed_recency = Configuration.getConfigValue(
            self.config_part, 'recency')
        self.mode = Configuration.getConfigValue(self.config_part, 'mode')
        self.daytypes_to_compute = None
        self.recency = None
        self.cache_dict = self.__init_dicts()
        self.last_processed_timestep = None
        self.testing_length = None

    def __init_dicts(self):
        # initializes data storages
        times = pd.date_range('00:00:00', '23:59:59',
                              freq=self.data_frequency).time
        dict_with_empty_profiles = dict()
        daytypes_to_compute = None
        if self.weekly is True and self.seasonal is True:
            self.daytypes_to_compute = [
                'ww', 'wsa', 'wsu', 'tw', 'tsa', 'tsu', 'sw', 'ssa', 'ssu']
        elif self.weekly is False and self.seasonal is True:
            self.daytypes_to_compute = ['ww', 'tw', 'sw']
        elif self.weekly is True and self.seasonal is False:
            self.daytypes_to_compute = ['sw', 'ssa', 'ssu']
        else:
            self.daytypes_to_compute = ['sw']
        for element in self.daytypes_to_compute:
            dict_with_empty_profiles[element] = pd.DataFrame(
                index=times)  # init all daytype profiles
        return dict_with_empty_profiles

    def __init_recency_test_dicts(self):
        # initializes data storages
        times = pd.date_range('00:00:00', '23:59:59',
                              freq=self.data_frequency).time
        dict_with_empty_profiles = dict()
        daytypes_to_compute = None
        if self.weekly is True and self.seasonal is True:
            self.daytypes_to_compute = [
                'ww', 'wsa', 'wsu', 'tw', 'tsa', 'tsu', 'sw', 'ssa', 'ssu']
        elif self.weekly is False and self.seasonal is True:
            self.daytypes_to_compute = ['ww', 'tw', 'su']
        elif self.weekly is True and self.seasonal is False:
            self.daytypes_to_compute = ['sw', 'ssa', 'ssu']
        else:
            self.daytypes_to_compute = ['sw']
        for element in self.daytypes_to_compute:
            dict_with_empty_profiles[element] = 0  # init all daytype profiles
        return dict_with_empty_profiles

    def get_season_info(self, date, year):
        """
        Function

            extract seasonal info from timestamp

        Parameter

            date : pd.datetime
                timestamp of measurement to be classified

            year : str
                year information of timestamp

        Return

            season in which timestamp is
        """
        if self.seasonal is False:
            return 's'
        elif self.seasonal is True:
            if pd.to_datetime(year + '-01-01').date() <= date <= pd.to_datetime(
                year + '-03-20').date() or pd.to_datetime(year + '-11-01').date() <= date <= pd.to_datetime(
                    year + '-12-31').date():
                season = 'w'  # winter
            elif pd.to_datetime(year + '-03-21').date() <= date <= pd.to_datetime(
                year + '-05-14').date() or pd.to_datetime(
                    year + '-09-15').date() <= date <= pd.to_datetime(year + '-10-31').date():
                season = 't'  # transition
            elif pd.to_datetime(year + '-05-15').date() <= date <= pd.to_datetime(
                    year + '-09-14').date():
                season = 's'  # summer
            else:
                raise ValueError('Undefined Date')
            return season

    def check_if_vacation(self, date):
        # check if date is a vacation date
        if date in self.holiday_times:
            return 1
        else:
            return 0

    def get_day_info(self, year, date):
        """
        Function

            classify if day is weekday (w), saturday (sa) or sunday (su)

        Parameter

            date : pd.datetime
                timestamp of measurement to be classified

            year : str
                year information of timestamp


        Return

            deactivated -> returns always w
        """
        if self.weekly is False:
            day_info = 'w'
        elif self.weekly is True:
            day_of_week = pd.to_datetime(date).dayofweek
            vacation = self.check_if_vacation(date)
            if day_of_week <= 4 and vacation == 0:
                day_info = 'w'
            elif day_of_week <= 4 and vacation == 1:
                day_info = 'su'
            elif day_of_week == 5 and vacation == 0:
                day_info = 'sa'
            elif day_of_week == 5 and vacation == 1:
                day_info = 'su'
            elif day_of_week == 6:
                day_info = 'su'
            else:
                raise ValueError('day type could not be interpreted')
        return day_info

    def set_up_fixed_recency(self):
        self.recency = {}
        for element in self.daytypes_to_compute:
            self.recency[element] = self.set_fixed_recency

    def classification_day(self, date):
        """
        Function

            find season and day type

        Parameter

            date : pd.datetime
                timestamp of measurement to be classified


        Return

            season and day_info : str
        """
        year = str(date.year)
        season = self.get_season_info(date, year)
        day_info = self.get_day_info(year, date)
        return season, day_info

    def sort_data_into_profile_cache(self, profile, data_on_date, date):
        """
        Function

            sort day into cache to be able to make profiles

        Parameter

            profile : pd.DataFrame
                profile to sort data in

            data_on_date : pd.DataFrame
                data measured on date

            date : pd.datetime
                timestamp of measurement to be classified


        Return

            Return is stored in class
        """
        # sort day into cache to be able to make profiles
        profile_data_cache = self.cache_dict.get(profile)
        data_on_date.index = data_on_date['time']
        profile_data_cache.loc[data_on_date.index,
                               date] = data_on_date[self.target_column]

    def preprocess_new_data(self, incoming_data):
        """
        Function

            process new data into data cache

        Parameter

            incoming_data : pd.DataFrame
                new measured data


        Return

            Return is stored in class
        """
        # process new data into data cache
        last_timestamp = incoming_data.last_valid_index()
        if self.last_processed_timestep is None:
            self.last_processed_timestep = last_timestamp
            data = incoming_data.copy(deep=True)
        else:
            data = incoming_data.loc[incoming_data.index >
                                     self.last_processed_timestep]
            self.last_processed_timestep = last_timestamp
        data['date'] = pd.to_datetime(data.index).date
        data['time'] = pd.to_datetime(data.index).time
        unique_dates = data['date'].unique()
        for date in unique_dates:
            data_on_date = data.loc[data['date'] == date]
            season, day_info = self.classification_day(date)
            day_character = season + day_info
            self.sort_data_into_profile_cache(
                day_character, data_on_date, date)

    def round_up(self, time_now):
        """
        Function

            find next timestep which is regular (e.g. 16:56:59 -> 17:00:00)

        Parameter

            time_now : pd.datetime
                current timestamp


        Return

            rounded time
        """
        # find next timestep which is regular (e.g. 16:56:59 -> 17:00:00)
        time_now_round = time_now.round(self.data_frequency)
        if time_now_round < time_now:
            time_now_round = time_now_round + \
                pd.to_timedelta(self.data_frequency)
        return time_now_round

    def get_previous_profile(self, season, date):
        """
        Function

            if there is no data in the profile for current season, use values of the last season as a guess

        Parameter

            season : str
                classified season

            date : str
                date of measurement


        Return

            name of previous profile
        """
        year = date.year
        if season == 'w' or season == 's':
            return 't'
        elif season == 't':
            if pd.to_datetime(str(year) + '-05-15').date() <= date <= pd.to_datetime(str(year) + '-09-14').date():
                return 's'
            else:
                return 'w'
        else:
            raise ValueError('Previous Season not found!')

    def forecast_standard(self, time_now, forecast_horizon):
        """
        Function

            Forecasting

        Parameter

            time_now : pd.datetime
                current timestamp

            forecast_horizon : str
                horizon length on which predictions should be made


        Return

            forecast as pd.DataFrame
        """
        # load forecasting part
        time_now_rounded_up = self.round_up(time_now)
        forecast_index = pd.date_range(start=time_now_rounded_up,
                                       end=pd.to_datetime(
                                           time_now_rounded_up) + pd.to_timedelta(forecast_horizon),
                                       freq=self.data_frequency)
        forecast_data = pd.DataFrame(index=forecast_index)
        forecast_data['time'] = forecast_data.index.time
        forecast_data['date'] = forecast_data.index.date
        unique_dates = np.unique(forecast_data.index.date)
        for date in unique_dates:
            season, day_info = self.classification_day(date)
            day_character = season + day_info
            cached_data = self.cache_dict.get(day_character)
            profile = cached_data.mean(axis=1)
            if profile.empty:
                season = self.get_previous_profile(season, date)
                day_character = season + day_info
                cached_data = self.cache_dict.get(day_character)
                profile = cached_data.mean(axis=1)
            data_for_day = forecast_data.loc[forecast_data['date'] == date].copy(
            )
            data_for_day.loc[:, 'index'] = data_for_day.index
            data_for_day.index = data_for_day['time']
            data_for_day.loc[:, 'forecast'] = profile.loc[data_for_day.index]
            data_for_day.index = data_for_day['index']
            forecast_data.loc[data_for_day.index,
                              'forecast'] = data_for_day['forecast']
        return forecast_data.drop(columns=['time', 'date'])

    def forecast_fixed_recency(self, time_now, forecast_horizon):
        """
        Function

            Forecasting with a pre set recency

        Parameter

            time_now : pd.datetime
                current timestamp

            forecast_horizon : str
                horizon length on which predictions should be made

            fixed_recency : int
                days of data used for calculating PSLP


        Return

            forecast as pd.DataFrame
        """
        if self.recency is None:
            raise ValueError('Specify Recency by using set_up_fixed_recency')
        # load forecasting part
        time_now_rounded_up = self.round_up(time_now)
        forecast_index = pd.date_range(start=time_now_rounded_up,
                                       end=pd.to_datetime(
                                           time_now_rounded_up) + pd.to_timedelta(forecast_horizon),
                                       freq=self.data_frequency)
        forecast_data = pd.DataFrame(index=forecast_index)
        forecast_data['time'] = forecast_data.index.time
        forecast_data['date'] = forecast_data.index.date
        unique_dates = np.unique(forecast_data.index.date)
        for date in unique_dates:
            season, day_info = self.classification_day(date)
            day_character = season + day_info
            cached_data = self.cache_dict.get(day_character)
            used_recency = self.recency.get(day_character)
            profile = cached_data.iloc[:, -int(used_recency):].mean(axis=1)
            if profile.empty:
                season = self.get_previous_profile(season, date)
                day_character = season + day_info
                cached_data = self.cache_dict.get(day_character)
                profile = cached_data.iloc[:, -int(used_recency):].mean(axis=1)
            data_for_day = forecast_data.loc[forecast_data['date'] == date].copy(
            )
            data_for_day.loc[:, 'index'] = data_for_day.index
            data_for_day.index = data_for_day['time']
            data_for_day.loc[:, 'forecast'] = profile.loc[data_for_day.index]
            data_for_day.index = data_for_day['index']
            forecast_data.loc[data_for_day.index,
                              'forecast'] = data_for_day['forecast']
        return forecast_data.drop(columns=['time', 'date'])

    def recency_reset(self):
        self.recency_set = {}
        for element in self.daytypes_to_compute:
            self.recency_set[element] = False

    def set_up_variable_recency(self, testing_length):
        self.set_up_fixed_recency()
        self.testing_length = testing_length

    def check_dates_in_data(self, historical_data):
        copy_hist_data = historical_data.copy()
        historical_data['time'] = historical_data.index.time
        historical_data['date'] = historical_data.index.date
        unique_dates = np.unique(historical_data.index.date)
        daytypes_in_hist_data = self.__init_recency_test_dicts()
        copy_hist_data['day_type'] = copy_hist_data.index.date
        for date in unique_dates:
            season, day_info = self.classification_day(date)
            day_character = season + day_info
            k = daytypes_in_hist_data.get(day_character)
            daytypes_in_hist_data[day_character] = daytypes_in_hist_data.get(
                day_character) + 1
            copy_hist_data['day_type'].replace(
                to_replace=date, value=day_character, inplace=True)
        return daytypes_in_hist_data, copy_hist_data

    def preset_unused_day_types(self, daytype_dataset, day_character):
        recencies_to_set = []
        count = daytype_dataset.get(day_character)
        sorted_data = self.cache_dict.get(day_character).shape[1]
        if not count >= 1 and not sorted_data >= 1:
            self.recency[day_character] = self.set_fixed_recency
        else:
            recencies_to_set.append(day_character)
        return recencies_to_set

    def __test_prediction(self, hist_data, profile):
        hist_data['prediction'] = hist_data.index.time
        dictionary = profile.to_dict()
        hist_data.replace({'prediction': dictionary}, inplace=True)
        return hist_data

    def test_recency(self, historical_data, day_character):
        copy_hist_data = historical_data.copy()
        daytypes_in_hist_data, classified_data = self.check_dates_in_data(
            copy_hist_data)
        recencies_to_set = self.preset_unused_day_types(
            daytypes_in_hist_data, day_character)
        recency_set = self.recency_set.get(day_character)
        if recency_set is False:
            for daytype in recencies_to_set:
                best_recency = None
                best_error = None
                cached_data = self.cache_dict.get(daytype)
                for recency in range(2, self.testing_length):
                    profile = cached_data.iloc[:, -recency:].mean(axis=1)
                    if cached_data.shape[1] < recency:
                        break
                    daytype_hist_data = classified_data[classified_data['day_type'] == daytype].copy(
                    )
                    daytype_hist_data = daytype_hist_data.iloc[-7 *
                                                               self.steps_per_day:, :]
                    prediction = self.__test_prediction(
                        daytype_hist_data, profile)
                    mape = mean_squared_error(
                        prediction[self.target_column], prediction['prediction'])
                    if best_recency is None:
                        best_recency = recency
                    if best_error is None:
                        best_error = mape
                    if best_error > mape:
                        best_recency = recency
                        best_error = mape
                self.recency[daytype] = best_recency
                self.recency_set[day_character] = True

    def forecast_infer_recency(self, time_now, historical_data, forecast_horizon):
        """
        Function

            Forecasting with variable length recency

        Parameter

            time_now : pd.datetime
                current timestamp

            forecast_horizon : str
                horizon length on which predictions should be made

            testing_length : int
                sets maximum days to test to find best recency

        Return

            forecast as pd.DataFrame
        """
        if self.testing_length is None:
            raise ValueError(
                'Set testing length by using set_up_variable_recency method')
        # load forecasting part
        time_now_rounded_up = self.round_up(time_now)
        forecast_index = pd.date_range(start=time_now_rounded_up,
                                       end=pd.to_datetime(
                                           time_now_rounded_up) + pd.to_timedelta(forecast_horizon),
                                       freq=self.data_frequency)
        forecast_data = pd.DataFrame(index=forecast_index)
        forecast_data['time'] = forecast_data.index.time
        forecast_data['date'] = forecast_data.index.date
        unique_dates = np.unique(forecast_data.index.date)
        number_of_days = int((len(historical_data) / self.steps_per_day)) - 1
        self.recency_reset()
        for date in unique_dates:
            season, day_info = self.classification_day(date)
            day_character = season + day_info
            if number_of_days < 8:
                recency = number_of_days
            else:
                self.test_recency(historical_data, day_character)
            cached_data = self.cache_dict.get(day_character)
            used_recency = self.recency.get(day_character)
            if used_recency is not None:
                profile = cached_data.iloc[:, -int(used_recency):].mean(axis=1)
            else:
                season = self.get_previous_profile(season, date)
                day_character = season + day_info
                if number_of_days < 8:
                    recency = number_of_days
                else:
                    self.test_recency(historical_data, day_character)
                used_recency = self.recency.get(day_character)
                cached_data = self.cache_dict.get(day_character)
                profile = cached_data.iloc[:, -int(used_recency):].mean(axis=1)
            if profile.empty or profile.dropna().shape[0] < self.steps_per_day:
                season = self.get_previous_profile(season, date)
                day_character = season + day_info
                if number_of_days < 8:
                    recency = number_of_days
                else:
                    self.test_recency(historical_data, day_character)
                used_recency = self.recency.get(day_character)
                cached_data = self.cache_dict.get(day_character)
                profile = cached_data.iloc[:, -int(used_recency):].mean(axis=1)
            data_for_day = forecast_data.loc[forecast_data['date'] == date].copy(
            )
            data_for_day.loc[:, 'index'] = data_for_day.index
            data_for_day.index = data_for_day['time']
            data_for_day.loc[:, 'forecast'] = profile.loc[data_for_day.index]
            data_for_day.index = data_for_day['index']
            forecast_data.loc[data_for_day.index,
                              'forecast'] = data_for_day['forecast']
        module_logger.info(f'PSLP: recency set to: {self.recency}')
        return forecast_data.drop(columns=['time', 'date'])

    def forecast(self, time_now, forecast_horizon, historical_data):
        if self.mode == 'all':
            return self.forecast_standard(time_now, forecast_horizon)
        elif self.mode == 'fixed_recency':
            if self.recency is None:
                self.set_up_fixed_recency()
            return self.forecast_fixed_recency(time_now, forecast_horizon)
        elif self.mode == 'infer_recency':
            if self.recency is None:
                self.set_up_variable_recency(testing_length=int(
                    Configuration.getConfigValue(self.config_part, 'testing_length')))
            return self.forecast_infer_recency(time_now, historical_data, forecast_horizon)
        else:
            raise ValueError(
                f'Mode has to be set to: all, fixed_recency or infer_recency and not {self.mode}.')
