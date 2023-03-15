import pandas as pd

from functions.data.data_augmentation import data_augmentation


def round_up(time_now, data_frequency):
    """
    Function

        find next timestep which is regular (e.g. 16:56:59 -> 17:00:00)

    Parameters

        time_now:  datetime
            time to round up

        data_frequency : str
            to which frequency should be rounded

    Return

        time_now_round : datetime
            rounded datetime
    """
    time_now_round = time_now.round(data_frequency)
    if time_now_round < time_now:
        time_now_round = time_now_round + pd.to_timedelta(data_frequency)
    return time_now_round


def check_for_missing_values(data, time_now, frequency, augment=False):
    """
    Function

        check dataset for missing values

    Parameter

        data : pd.DataFrame
            data to evaluate

        time_now : datetime
            last timestamp or current time

        frequency : str
            frequency of data

        augment : bool
            fill na by auugmentation

    Return

        filled data
    """
    last_timestep_in_data = data.last_valid_index()
    if not time_now - pd.to_timedelta(frequency) < last_timestep_in_data:
        last_time_should = round_up(time_now - pd.to_timedelta(frequency), frequency)
        missing_index = pd.date_range(last_timestep_in_data + pd.to_timedelta(frequency), last_time_should,
                                      freq=frequency)
        missing_data_frame = pd.DataFrame(index=missing_index)
        dataset_with_full_index = data.append(missing_data_frame)
        if augment is True:
            augmented_data = data_augmentation(dataset_with_full_index, frequency, data.columns[0])
        augmented_data = augmented_data.interpolate()
        return augmented_data
    else:
        return data


def prepare_dataset_for_forecasting(data, forecast_horizon, frequency, time_now, repair_missing_values=True,
                                    augment=True):
    """
    Function

        prepare dataset for forecasting by extending index until end of forecast_horizon

    Parameters

        data : pd.DataFrame
            data to extend

        forecast_horizon : str
            forecast horizon

        frequency : str
            frequency of data

        time_now : str
            current timestep

        repair_missing_values : bool
            repairs missing values with either augmentation or interpolation

        augment : bool
            augment data or interpolate

    Return

        data : pd.DataFrame
            prolonged dataset

    """
    if repair_missing_values is True:
        data = check_for_missing_values(data, time_now, frequency, augment)
    else:
        pass
    time_now_rounded = round_up(time_now, frequency)
    if time_now_rounded == data.last_valid_index():
        time_now_rounded = time_now_rounded + pd.to_timedelta(frequency)
    new_index_to_add = pd.date_range(time_now_rounded,
                                     time_now_rounded + pd.to_timedelta(forecast_horizon) - pd.to_timedelta(frequency),
                                     freq=frequency)
    new_data_frame = pd.DataFrame(index=new_index_to_add)
    data = data.append(new_data_frame)
    return data
