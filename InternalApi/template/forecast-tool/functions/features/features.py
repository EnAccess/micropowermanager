import numpy as np
import pandas as pd


def time_to_seconds(time_data):
    """
    Function

        converts timestamps to time as seconds on different time scales (seconds to year)


    Parameters

        time_data : pd.DataFrame
            data with column (named: 'time') to calculate to seconds

    Return

        time_data : DataFrame
            data with time columns given in seconds
    """
    # retrieve seconds
    time_data['second'] = time_data['time'].dt.second
    # minutes to seconds
    time_data['minute'] = time_data['time'].dt.minute * 60
    # hours to seconds
    time_data['hour'] = time_data['time'].dt.hour * 60 * 60
    # weeks to seconds
    time_data['week'] = time_data['time'].dt.dayofweek * 24 * 60 * 60
    # month to seconds
    time_data['month'] = time_data['time'].dt.day * 24 * 60 * 60
    # year to seconds
    time_data['year'] = time_data['time'].dt.dayofyear * 24 * 60 * 60
    return time_data


def cyclic_time(dataset, week=False, day=False, hour=False, year=False, minute=False):
    """
    Function

        Time representation as sine or cosine function

    Principle

        This is a positional encoding technique for algorithms showing when measurement was taken and when in forecast
        horizon the prediction should be

    Parameters

        dataset : pd.DataFrame or pd.Series
            DataFrame with time data as index

        week : bool
            sine or cosine over a week

        day : bool
            sine or cosine over a day

        hour : bool
            sine or cosine over a hour

        year : bool
            sine or cosine over a year

        minute : bool
            sine or cosine over a minute

    Return

        dataset : pd.DataFrame
            data with selected sine and cosine representation
    """
    # check if given dataset is a Series or a DataFrame
    if isinstance(dataset, pd.Series):
        dataset = dataset.to_frame()
    # extract time data
    time_data = pd.DataFrame(columns=['time'], index=dataset.index)
    time_data.loc[:, 'time'] = time_data.index
    # Calculate seconds from time data
    time_data = time_to_seconds(time_data)
    name_of_times_to_choose = ['minute', 'hour', 'day', 'week', 'year']
    length_of_times_in_seconds = {'second': 1, 'minute': 60, 'hour': 60 * 60, 'day': 24 * 60 * 60,
                                  'week': 7 * 24 * 60 * 60, 'year': 365 * 24 * 60 * 60}
    name_of_time_columns = ['second', 'minute', 'hour', 'day', 'week', 'year']
    element_dict = {'minute': [0], 'hour': [0, 1], 'day': [0, 1, 2], 'week': [0, 1, 2, 4], 'year': [0, 1, 2, 5]}
    chosen_times = [minute, hour, day, week, year]
    i = 0
    # for every chosen time calculate sine and cosine
    for time in chosen_times:
        if time is True:
            chosen_time_name = name_of_times_to_choose[i]
            list_of_columns_to_add = element_dict.get(chosen_time_name)
            time_for_calculation = []
            for element in list_of_columns_to_add:
                time_for_calculation.append(name_of_time_columns[element])
            sum_of_columns = time_data.loc[:, time_for_calculation].sum(axis=1)
            factor = 2 * np.pi / length_of_times_in_seconds.get(chosen_time_name)
            dataset.loc[:, 'sine_' + chosen_time_name] = np.sin(sum_of_columns * factor)
            dataset.loc[:, 'cos_' + chosen_time_name] = np.cos(sum_of_columns * factor)
        else:
            pass
        i = i + 1
    return dataset


def last_days_max(dataset, target_column, days=7, fill_na=False):
    """
    Function

        Derives maximum values of recent days to form a feature for ML-Algorithms

    Parameters

        dataset : pd.DataFrame
            data with target column

        target_column : str
            name of target column

        days : int
            period to look for highest values

        fill_na : bool
            activates filling na with 0

    Return

        dataset : initial pd.DataFrame
            data with added features


    """
    # copy data to secure source data
    copy_of_data = dataset[target_column].copy(deep=True).to_frame()
    # shift data
    for i in range(1, days + 2):
        copy_of_data['shift_' + str(i)] = copy_of_data[target_column].shift(i, 'D')
    copy_of_data.drop(columns=[target_column], inplace=True)
    copy_of_data['max'] = copy_of_data.max(axis=1)
    # add generated data to initial data frame
    dataset['last_days_max'] = copy_of_data['max']
    # fill not available data
    if fill_na is True:
        dataset.fillna(0, inplace=True)
    return dataset


def add_weather_data(data, historic, forecast):
    """
    Function

        adds weather prediction or historic data to given dataframe


    Parameters

        data : pd.DataFrame
            data to which weather data should be added

        historic : pd.DataFrame
            datawith historical weather data

        forecast : pd.DataFrame
            data with weather prediction

    Return

        data : pd.DataFrame
            data with added weather data

    """

    data_end_historic_start_forecast = pd.concat([historic.iloc[-10:, :-1], forecast])
    # get rid of double values
    data_end_historic_start_forecast = data_end_historic_start_forecast[~data_end_historic_start_forecast.index.duplicated(keep='first')]
    data_end_historic_start_forecast['diff'] = data_end_historic_start_forecast.index
    a = data_end_historic_start_forecast['diff'].diff()
    # change frequency if needed and fill missing values
    data_end_historic_start_forecast = data_end_historic_start_forecast.iloc[:,:-1].asfreq('15Min')
    preprocessed_data = data_end_historic_start_forecast.iloc[:,:].interpolate()
    preprocessed_data['diff'] = a.resample('15Min').bfill()
    # combine historical and forecast weather data
    weather_data_total = pd.concat([historic, preprocessed_data.iloc[10:, :]])
    weather_data_total = weather_data_total[~weather_data_total.index.duplicated(keep='first')]
    # add weather data to dataset
    weather_data_total = weather_data_total[weather_data_total['diff']<=pd.to_timedelta('4H 15Min')]
    weather_data_total.drop(columns=['diff'], inplace= True)
    for column in weather_data_total.columns:
        data[column] = weather_data_total[column]
    return data


def prepare_long_term_weather(long_term_weather_data):
    """
    Function

        shifts weather data several times to have recent 10 years of data per row

    Parameters

        long_term_weather_data : pd.DataFrame
            data with NASA weather data

    Return

        data : pd.DataFrame
            data with shifted weather data
    """
    # make secure copy of data from NASA
    data = long_term_weather_data.copy(deep=True) # we need to skip that point if there is no nasa weather data
    # Shift data 10 times (Everytime it is shifted by 1 year)
    for i in range(1, 10):
        weather_data_auto_1 = long_term_weather_data.copy(deep=True)
        weather_data_auto_1.index = weather_data_auto_1.index + pd.to_timedelta('365D') * i
        for col in long_term_weather_data.columns:
            data[col + '_' + str(i)] = weather_data_auto_1[col].copy()
            data = data.copy()
    # Shift again by one to meet correct index of DataFrame data should be added to
    data.index = data.index + pd.to_timedelta('365D')
    return data
