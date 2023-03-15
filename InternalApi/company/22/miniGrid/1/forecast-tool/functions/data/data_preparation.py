import numpy as np
import pandas as pd
from astral.sun import sun


def split_data_monthly(dataset, target):
    """
    Function

        Split data into monthly clusters and calculate statistics

    Parameter

        dataset : pd.DataFrame
            dataset to split

        target : str
            name of target column

    Return

        monthly_data : dict of pd.DataFrames
            data sorted into month

        monthly_statistics
            total statistics of dataset
    """
    data_copy = dataset.copy(deep=True)
    data_copy['date'] = data_copy.index.date
    data_copy['hour'] = data_copy.index.time
    data_copy['month'] = data_copy.index.month
    monthly_data = dict()
    for month in data_copy['month'].unique():
        monthly_statistics = pd.DataFrame(
            index=pd.date_range('1970-01-01 00:00', '1970-01-01 23:45', freq='15Min').time)
        data_of_month = data_copy[data_copy['month'] == month]
        sorted_monthly_data = pd.DataFrame()
        for date in data_of_month['date'].unique():
            data_of_day = data_of_month[data_of_month['date'] == date]
            data_of_day.index = data_of_day['hour']
            sorted_monthly_data[date] = data_of_day[target]
        monthly_statistics['upper_quantil'] = sorted_monthly_data.quantile(0.75, axis=1)
        monthly_data[month] = monthly_statistics
    return monthly_data, monthly_statistics


def sunshine_filter(dataset, target):
    monthly_data, monthly_statistics = split_data_monthly(dataset, target)
    approx_start = None
    approx_end = None
    for key in monthly_data.keys():
        month = monthly_data.get(key)
        month = month[(month['upper_quantil'] > 0)]
        start = month.first_valid_index()
        end = month.last_valid_index()
        if start > pd.to_datetime('1970-01-01 06:00').time():
            pass
        else:
            if approx_start is None:
                approx_start = start
            else:
                if approx_start > start or approx_start is not None:
                    approx_start = start
                else:
                    pass
        if end > pd.to_datetime('1970-01-01 20:00').time():
            pass
        else:
            if approx_end is None:
                approx_end = end
            else:
                if approx_end < end:
                    approx_end = end
                else:
                    pass
    return approx_start, approx_end


def check_if_missing_values_are_in_line(day_time_data, target, frequency):
    """
    Function

        check for missing values following each other

    Principle

        Checking if one or two datapoints are missing or more following each other. This is done by
        checking the frequency which the data should have and the gap of missing values

    Parameters

        day_time_data : pd.DataFrame
            measurements during daytimes

        target : str
            target column to check for inconsistencies

        frequency : str
            Frequency of raw data

    Returns

        boolean value if over 3 values are missing in a row

    Notes

        Smaller chunks of missing values can be reestimated by using linear interpolation or something
        else. Then you have to be sure that no random dips or rises can be avaialble. Therefor the limit
        of missing values is set to a lower value: 3
    """
    diff = day_time_data.copy(deep=True)
    diff['difference'] = diff.index
    # filter out nan values
    diff = diff[day_time_data[target] != day_time_data[target]]
    diff['difference'] = diff['difference'].diff(1)
    freq = pd.to_timedelta(frequency)
    counter = 0
    for index, row in diff.iterrows():
        difference = diff.loc[index, 'difference']
        if difference > freq:
            if counter >= 3:
                return True
            else:
                counter == 0
        else:
            counter += 1
    return False


def fill_day_time_data(day_time_data, target, frequency):
    """
    Function

        Fill missing values during daytimes. This is mostly for PV data

    Parameters

        day_time_data : pd.DataFrame
            measurements during daytimes

        target : str
            target column to check for inconsistencies

        frequency : str
            Frequency of raw data

    Returns

        day_time_data : pd.DataFrame
            repaired or unrepaired data

        unrepairable : bool
            decision if data was repairable

    Notes

        data is filled polynomial because linear would create a sharp edge
    """
    before = len(day_time_data)
    after = len(day_time_data.dropna())
    if before == 0:
        unrepairable = True
    else:
        if after / before <= 0.5:
            unrepairable = True
        else:
            unrepairable = check_if_missing_values_are_in_line(day_time_data, target, frequency)
            if unrepairable is False:
                day_time_data[target] = day_time_data[target].interpolate('polynomial', order=2)
                if day_time_data[target].min() < 0:
                    day_time_data[day_time_data[target] < 0] = np.nan
                    day_time_data[target] = day_time_data[target].interpolate()
    return day_time_data, unrepairable


def check_for_days_with_large_amount_of_missing_values(maximum_percentage, data, frequency):
    """
    Function

        General check up of datasets to get total amount of missing values per day

    Parameter

        maximum_percentage : float
            share of datapoints missing on a day

        data : pd.DataFrame
            data to check for missing values

        frequency : str
            frequency the data should have

    Return

        days_to_drop : list of datetime.date
            dates exceeding share of missing vales

        missing_value_frame : pd.DataFrame
            statistical DataFrame showing on which day how many values are missing and the share

    """
    data = data.resample(frequency).asfreq()
    data['date'] = data.index.date
    unique_dates = data['date'].unique()
    data = data.dropna()
    amount_of_measurements_per_day = pd.to_timedelta('1D') / pd.to_timedelta(frequency)
    missing_value_frame = pd.DataFrame(index=unique_dates,
                                       columns=['no. missing values', 'percentage missing values'])
    days_to_drop = []
    for date in unique_dates:
        test_date = data.loc[data['date'] == date]
        measurements_on_day = len(test_date)
        number_of_missing_values = amount_of_measurements_per_day - measurements_on_day
        percentage_missing = number_of_missing_values / amount_of_measurements_per_day
        missing_value_frame.loc[date, 'no. missing values'] = number_of_missing_values
        missing_value_frame.loc[date, 'percentage_missing values'] = percentage_missing
        if percentage_missing > maximum_percentage:
            days_to_drop.append(date)
        else:
            pass
    if not days_to_drop == []:
        days_to_drop.pop(-1)
    return days_to_drop, missing_value_frame


def fill_night_data(night_time_data, target):
    """
    Function

        During nighttime value of target column is set to 0

    Parameters

        night_time_data : pd.DataFrame
            Measurements during nighttime

        target : str
            name of target columns

    Returns

        night_time_data : pd.DataFrame
            DataFrame with target column set to 0

    """
    night_time_data[target] = 0
    return night_time_data


def fill_mv_potentialy(data, sun_times, frequency, target):
    """
    Function

        Vaguely try to fill missing values in PV data

    Principle

        Filling Missing Value in datasets. Data during nighttime is set to 0 for PV generation
        and daytime is checked if it is repairable and then repaired with polynomial interpolation

    Parameters

        data : pd.DataFrame
            data to fill missing vales

        sun_times : astral.location
            sunrise and sunset from astral library

        frequency : str
            frequency in which data should be

        target : str
            name of target column in dataset

    Returns

        data : pd.DataFrame
            data with some of the missing values filled.

    """
    days_with_many_missing_values, frame = check_for_days_with_large_amount_of_missing_values(0.1, data, frequency)
    data = data.asfreq(frequency)
    data['date'] = data.index.date

    for date in frame.index:
        s = sun(sun_times.observer, date=date)
        data_of_day = data[data['date'] == date].copy(deep=True)
        day_time_data = data_of_day[(data_of_day.index >= s['sunrise']) & (
                data_of_day.index <= s['sunset'])]
        night_time_data = data_of_day[(data_of_day.index <= s['sunrise']) | (
                data_of_day.index >= s['sunset'])]
        night_time_data = fill_night_data(night_time_data, target)
        day_time_data, unrepairable = fill_day_time_data(day_time_data, target, frequency)
        corrected_data = pd.concat([day_time_data, night_time_data]).sort_index()
        if unrepairable is False:
            corrected_data = corrected_data.fillna(0)
        data[data['date'] == date] = corrected_data
    data = data.drop(columns=['date'])
    return data
