import numpy as np
import pandas as pd
from tensorflow import data as data_tensor

from functions.data.data_augmentation import data_augmentation
from functions.data.data_preparation import check_for_days_with_large_amount_of_missing_values
from functions.features.features import last_days_max, add_weather_data, prepare_long_term_weather, cyclic_time
from functions.load_forecasting.PSLP import calc_fast_PSLP
from functions.timeseriesdata.forecasting_functions import prepare_dataset_for_forecasting

import matplotlib.pyplot as plt


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
    train_y = train_data  # .iloc[:, :1]
    # preprocessed_long_weather.index = preprocessed_long_weather.index.dt.tz_convert('Africa/Dar_es_Salaam')
    preprocessed_long_weather[preprocessed_long_weather <= -900] = 0
    if forecast_mode is False:
        scaler.scaler_fit(preprocessed_long_weather, train_y, save=True)
    scaler.set_index(dataset_with_horizon.iloc[-90 * 24:, :1].index)
    weather_data_array = []
    train_true = []
    preprocessed_long_weather_scaled, train_y_scaled = scaler.scaler_transform(
        preprocessed_long_weather, train_y)
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
        try:
            if np.isnan(hist).any() or len(weather) < 2160 or len(true) < 2160:
                pass
            else:
                weather_data_array.append(weather)
                train_true.append(true)
        except:
            pass
    index = np.where(preprocessed_long_weather_scaled.index >=
                     time_now)[0][0]-1
    weather_data_forecast = np.array(preprocessed_long_weather_scaled.iloc[
                                     index:index + 90 * 24].values).reshape(1, 90 * 24, 87)
    tensors = data_tensor.Dataset.from_tensor_slices(
        tuple([np.array(weather_data_array), np.array(train_true)])).shuffle(192)
    part = int(len(weather_data_array) * split)
    train = tensors.take(part)
    val = tensors.skip(part)
    forecast = weather_data_forecast.reshape(1, 2160, 87)
    return {'train': train, 'val': val, 'forecast': forecast}


def split_short_term_dataset_pv(dataset):
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
    forecast_x = dataset.iloc[-96 * 2:, 1:].fillna(0)
    return train_x, train_y, forecast_x


def function_dataset_short_term_pv_forecast(data, target_column, time_now, scaler, forecast_mode=False, split=0.9):
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
    pv_data = data.get('pv_measurements').copy(
        deep=True).resample('15Min').mean()
    historical_weather_data = data.get('historical_weather_data')
    forecast_data = data.get('weather_forecast_data')
    minimum_length = (1 + 2) * int(
        pd.to_timedelta('24H') / pd.to_timedelta('15Min'))
    data_length_pre_check_result = data_length_pre_check(
        data.get('pv_measurements'), minimum_length)
    if data_length_pre_check_result is True:
        dataset_with_horizon = prepare_dataset_for_forecasting(pv_data, '48H', '15Min',
                                                               time_now=time_now, repair_missing_values=False)
        historical_weather_data['diff'] = historical_weather_data.index
        historical_weather_data = historical_weather_data.dropna()
        a = historical_weather_data['diff'].diff()
        # historical_weather_data['diff'] = historical_weather_data['diff'].replace({pd.NaT: np.nan})
        historical_weather_data = historical_weather_data.resample(
            '15Min').asfreq()
        historical_weather_data.iloc[:, :-1] = historical_weather_data.iloc[:,
                                                                            :-1].resample('15Min').interpolate(method='polynomial', order=2)
        historical_weather_data['diff'] = a.resample('15Min').bfill().dropna()
        historical_weather_data = historical_weather_data[historical_weather_data['diff'] <= pd.to_timedelta(
            '2H 15Min')]
        # historical_weather_data['diff'].resample('15Min').bfill()
        # forecast_data = forecast_data.resample('15Min').interpolate(method='polynomial', order=2)
        # weather_data = weather_data[dataset_with_horizon.index]

        # generate additional feature
        pv_data_with_last_days_max = last_days_max(dataset_with_horizon, target_column=target_column, days=5,
                                                   fill_na=False)
        pv_power_max_short = pv_data_with_last_days_max.iloc[-96 * 2:, 1:]
        dataset_short_term_forecast = add_weather_data(
            pv_data_with_last_days_max, historical_weather_data, forecast_data)
        # dataset_short_term_forecast = cyclic_time(dataset_short_term_forecast, day=True, year=True)
        # split data
        train_x, train_y, forecast_x = split_short_term_dataset_pv(
            dataset_short_term_forecast)
        if forecast_mode is False:
            scaler.scaler_fit(dataset_y=train_y, dataset_x=train_x, save=True)
        train_x_scaled, train_y_scaled = scaler.scaler_transform(
            dataset_y=train_y, dataset_x=train_x)
        forecast_x_scaled = scaler.scaler_transform_for_prediction(forecast_x)
        part = int(len(train_y_scaled) * split)
        # array to tensor with specific shapes [samples = variable, timestep = 1, features = fixed value]
        tensors = data_tensor.Dataset.from_tensor_slices(
            tuple([np.array(train_x_scaled.values.reshape(train_x_scaled.shape[0], 1, train_x_scaled.shape[1])),
                   np.array(train_y_scaled.values)]))
        train = tensors.take(part)
        val = tensors.skip(part)
        # forecast = forecast_x_scaled.reshape(forecast_x_scaled.shape[0], 1, forecast_x_scaled.shape[1])
        forecast = forecast_x_scaled.reshape(
            forecast_x_scaled.shape[0], 1, forecast_x_scaled.shape[1])

        dataset_short_term_forecast = {
            'train': train, 'val': val, 'forecast': forecast}
    else:
        dataset_with_horizon = prepare_dataset_for_forecasting(pv_data, '48H', '15Min',
                                                               time_now=time_now, repair_missing_values=False)
        dataset_short_term_forecast = dataset_with_horizon.iloc[-196:, :].fillna(
            0)
        pv_power_max_short = pd.DataFrame(
            data=dataset_short_term_forecast.values, index=dataset_short_term_forecast.index, columns=['last_days_max'])
    return {'datasets_train_forecast': dataset_short_term_forecast, 'optional_out': pv_power_max_short, 'enough_data': data_length_pre_check_result}, scaler


def function_dataset_long_term_pv_forecast(data, target_column, time_now, scaler, forecast_mode, split=0.9,
                                           opt_args=None):
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
    trick = opt_args
    historical_window_length = 14
    forecast_window_length = 90
    minimum_length = (historical_window_length + forecast_window_length) * \
        int(pd.to_timedelta('24H')/pd.to_timedelta('15Min'))
    data_length_pre_check_result = data_length_pre_check(
        data.get('pv_measurements'), minimum_length)
    if data_length_pre_check_result is True:
        pv_data = data.get('pv_measurements').copy(
            deep=True).resample('1H').mean()
        long_term_weather_data = data.get('long_tern_historical_weather_data')
        dataset_with_horizon = prepare_dataset_for_forecasting(pv_data, '90D', '1H',
                                                               time_now=time_now, repair_missing_values=False)
        preprocessed_long_weather = prepare_long_term_weather(
            long_term_weather_data)
        preprocessed_long_weather = preprocessed_long_weather.copy()
        if trick is True:
            dataset_with_horizon = prepare_dataset_for_forecasting(pv_data.fillna(0), '90D', '1H',
                                                                   time_now=time_now, repair_missing_values=False)
            preprocessed_long_weather = prepare_long_term_weather(
                long_term_weather_data)
            preprocessed_long_weather = preprocessed_long_weather.copy()
        else:
            dataset_with_horizon = prepare_dataset_for_forecasting(pv_data, '90D', '1H',
                                                                   time_now=time_now, repair_missing_values=False)
            preprocessed_long_weather = prepare_long_term_weather(
                long_term_weather_data)
            preprocessed_long_weather = preprocessed_long_weather.copy()
        preprocessed_long_weather = cyclic_time(
            preprocessed_long_weather, day=True)
        preprocessed_long_weather = preprocessed_long_weather[['sine_day', 'cos_day', 'ALLSKY_SFC_SW_DWN_1',
                                                               'CLRSKY_SFC_SW_DWN_1', 'ALLSKY_KT_1', 'ALLSKY_SRF_ALB_1',
                                                               'SZA_1',
                                                               'ALLSKY_SFC_PAR_TOT_1', 'CLRSKY_SFC_PAR_TOT_1',
                                                               'ALLSKY_SFC_UVA_1',
                                                               'ALLSKY_SFC_UVB_1', 'ALLSKY_SFC_SW_DWN_2',
                                                               'CLRSKY_SFC_SW_DWN_2',
                                                               'ALLSKY_KT_2', 'ALLSKY_SRF_ALB_2', 'SZA_2',
                                                               'ALLSKY_SFC_PAR_TOT_2',
                                                               'CLRSKY_SFC_PAR_TOT_2', 'ALLSKY_SFC_UVA_2',
                                                               'ALLSKY_SFC_UVB_2',
                                                               'ALLSKY_SFC_UV_INDEX_2', 'ALLSKY_SFC_SW_DWN_3',
                                                               'CLRSKY_SFC_SW_DWN_3',
                                                               'ALLSKY_KT_3', 'ALLSKY_SRF_ALB_3', 'SZA_3',
                                                               'ALLSKY_SFC_PAR_TOT_3',
                                                               'CLRSKY_SFC_PAR_TOT_3', 'ALLSKY_SFC_UVA_3',
                                                               'ALLSKY_SFC_UVB_3',
                                                               'ALLSKY_SFC_SW_DWN_4', 'CLRSKY_SFC_SW_DWN_4', 'ALLSKY_KT_4',
                                                               'ALLSKY_SRF_ALB_4', 'SZA_4', 'ALLSKY_SFC_PAR_TOT_4',
                                                               'CLRSKY_SFC_PAR_TOT_4', 'ALLSKY_SFC_UVA_4',
                                                               'ALLSKY_SFC_UVB_4',
                                                               'ALLSKY_SFC_UV_INDEX_4', 'ALLSKY_SFC_SW_DWN_5',
                                                               'CLRSKY_SFC_SW_DWN_5',
                                                               'ALLSKY_KT_5', 'ALLSKY_SRF_ALB_5', 'SZA_5',
                                                               'ALLSKY_SFC_PAR_TOT_5',
                                                               'CLRSKY_SFC_PAR_TOT_5', 'ALLSKY_SFC_UVA_5',
                                                               'ALLSKY_SFC_UVB_5',
                                                               'ALLSKY_SFC_SW_DWN_6', 'CLRSKY_SFC_SW_DWN_6', 'ALLSKY_KT_6',
                                                               'ALLSKY_SRF_ALB_6', 'SZA_6', 'ALLSKY_SFC_PAR_TOT_6',
                                                               'CLRSKY_SFC_PAR_TOT_6', 'ALLSKY_SFC_UVA_6',
                                                               'ALLSKY_SFC_UVB_6',
                                                               'ALLSKY_SFC_SW_DWN_7', 'CLRSKY_SFC_SW_DWN_7', 'ALLSKY_KT_7',
                                                               'ALLSKY_SRF_ALB_7', 'SZA_7', 'ALLSKY_SFC_PAR_TOT_7',
                                                               'CLRSKY_SFC_PAR_TOT_7', 'ALLSKY_SFC_UVA_7',
                                                               'ALLSKY_SFC_UVB_7',
                                                               'ALLSKY_SFC_UV_INDEX_7', 'ALLSKY_SFC_SW_DWN_8',
                                                               'CLRSKY_SFC_SW_DWN_8',
                                                               'ALLSKY_KT_8', 'ALLSKY_SRF_ALB_8', 'SZA_8',
                                                               'ALLSKY_SFC_PAR_TOT_8',
                                                               'CLRSKY_SFC_PAR_TOT_8', 'ALLSKY_SFC_UVA_8',
                                                               'ALLSKY_SFC_UVB_8',
                                                               'ALLSKY_SFC_UV_INDEX_8', 'ALLSKY_SFC_SW_DWN_9',
                                                               'CLRSKY_SFC_SW_DWN_9',
                                                               'ALLSKY_KT_9', 'ALLSKY_SRF_ALB_9', 'SZA_9',
                                                               'ALLSKY_SFC_PAR_TOT_9',
                                                               'CLRSKY_SFC_PAR_TOT_9', 'ALLSKY_SFC_UVA_9',
                                                               'ALLSKY_SFC_UVB_9']]
        test = preprocessed_long_weather.copy()
        test['pv_data'] = dataset_with_horizon.iloc[:, 0]
        test['pv_data'] = test['pv_data'].fillna(0)
        dataset_with_horizon = test['pv_data'].loc[dataset_with_horizon.index[0]:dataset_with_horizon.index[-1]].to_frame()
        preprocessed_long_weather = test.loc[dataset_with_horizon.first_valid_index(
        ):dataset_with_horizon.last_valid_index(), :]
        preprocessed_long_weather = preprocessed_long_weather.drop(columns=[
            'pv_data'])
        dataset_long_term_forecast = supervised_data_pv_long_term(preprocessed_long_weather, dataset_with_horizon,
                                                                  forecast_mode, scaler, time_now)
    else:
        pv_data = data.get('pv_measurements').copy(
            deep=True).resample('1H').mean()
        dataset_with_horizon = prepare_dataset_for_forecasting(pv_data, '90D', '1H',
                                                               time_now=time_now, repair_missing_values=False)
        dataset_long_term_forecast = dataset_with_horizon.iloc[-96*24:, :].fillna(
            0)
    return {'datasets_train_forecast': dataset_long_term_forecast, 'optional_out': None, 'enough_data': data_length_pre_check_result}, scaler


def supervised_data_load_short_term(train, split=0.9):
    """
    train data to supervised

    """
    array_train_hist = []
    array_train_pred = []
    array_train_true = []
    for i in range(192, len(train) - 192):
        array_train_hist.append(train.iloc[i - 192: i, -1:].values)
        array_train_pred.append(train.iloc[i: i + 192, :-1].values)
        array_train_true.append(train.iloc[i: i + 192, -1:].values)
    forecast_hist = np.array(train.iloc[-192:, -1:].values).reshape(1, 192, 1)
    forecast_index = train.iloc[-192:, -1:].index
    part = int(len(array_train_true) * split)
    tensors = data_tensor.Dataset.from_tensor_slices(
        tuple([np.array(array_train_hist), np.array(array_train_true)])).shuffle(192)
    train = tensors.take(part)
    val = tensors.skip(part)
    forecast = forecast_hist
    return {'train': train, 'val': val, 'forecast': forecast}, forecast_index


def split_short_term_dataset(dataset):
    """
    Function

        Split data into train and forecast dataset

    Parameter

        dataset : pd.DataFrame
            Load data to split in train and forecast datasets

    Return

        train_x : pd.DataFrame
            training dataset with features
        train_y : pd.DataFrame or pd.Series
            training dataset containing measurements
        forecast_x : pd.DataFrame
            forecast dataset with features
    """
    dataset_train = dataset.iloc[:-96 * 2, :].dropna()
    train_x, train_y = dataset_train.iloc[:, 1:], dataset_train.iloc[:, :1]
    forecast_x = dataset.iloc[-96 * 2:, 1:]
    return train_x, train_y, forecast_x


def split_long_term_dataset(dataset):
    """
    Function

        splitting data into train and forecast

    Parameter

        dataset : pd.DataFrame
            dataset to split

    Return

        x : pd.DataFrame
            all features
        y : pd.DataFrame
            measurements
    """
    # dataset_without_na = dataset.dropna()
    x, y = dataset.iloc[:, 1:], dataset.iloc[:, :1]
    return x, y


def to_current_date(time_now, data, optional_args):
    """
    Function

        augments data to current date if measurements are not coming in

    Parameter

        time_now : datetime
            current timestamp

    Return

        Data without missing values up to current timestamp
    """
    data = data.get('load_measurements').copy(deep=True)
    data_frequency = optional_args.get('data_frequency')
    if data.last_valid_index() <= time_now - pd.to_timedelta(data_frequency):
        missing_rest_dates = pd.date_range(data.last_valid_index() + pd.to_timedelta(data_frequency),
                                           time_now -
                                           pd.to_timedelta(data_frequency),
                                           freq=data_frequency)
        data = pd.concat([data, pd.DataFrame(index=missing_rest_dates)])
        data = data_augmentation(
            data, frequency=data_frequency, column=data.columns[0])
        data = data.groupby(data.index).mean()
    return data


def function_dataset_short_term_load_forecast(data, target_column, time_now, scaler, forecast_mode, split=0.9,
                                              opt_args=None):
    """
    Function

        calculate features for short term forecast

    Parameter

        time_now : datetime
            current timestamp
        forecast_mode : bool
            activate train

    Return

        dataset_short_term_forecast : dict
            all data needed for train and forecast
    """

    dataset_short_term_forecast = to_current_date(time_now, data, opt_args)
    minimum_length = (1 + 2) * int(
        pd.to_timedelta('24H') / pd.to_timedelta('15Min'))
    data_length_pre_check_result = data_length_pre_check(
        data.get('load_measurements'), minimum_length)
    if data_length_pre_check_result is True:
        dataset_with_horizon = prepare_dataset_for_forecasting(dataset_short_term_forecast, '48H', '15Min',
                                                               time_now=time_now)
        dataset_with_cyclic_time = cyclic_time(
            dataset_with_horizon, week=True, day=True, year=True)
        dataset_with_max_days = last_days_max(dataset_with_cyclic_time, target_column=target_column, days=5,
                                              fill_na=False)
        train_x, train_y, forecast_x = split_short_term_dataset(
            dataset_with_max_days)
        if forecast_mode is False:
            scaler.scaler_fit(dataset_y=train_y, dataset_x=train_x, save=True)
        train_x_scaled, train_y_scaled = scaler.scaler_transform(
            dataset_y=train_y, dataset_x=train_x)
        train_x_scaled[train_y_scaled.columns] = train_y_scaled
        dataset_short_term_forecast, index = supervised_data_load_short_term(
            train_x_scaled, split=0.9)
        scaler.current_data_index = forecast_x.index
    else:
        dataset_with_horizon = prepare_dataset_for_forecasting(dataset_short_term_forecast, '48H', '15Min',
                                                               time_now=time_now)
        dataset_short_term_forecast = dataset_with_horizon.iloc[-196:, :].fillna(
            0)
    return {'datasets_train_forecast': dataset_short_term_forecast, 'optional_out': None, 'enough_data': data_length_pre_check_result}, scaler


def dataset_to_supervised(data, regrouped_data, pslp, positional_encoding_in, positional_encoding_out,
                          hist_sequence_length='7D', forecast_sequence_length='10D',
                          split=0.9):
    """
    supervised for long term
    """
    data = data.values
    residuum_list = []
    pslp_decoder = []
    output = []
    for i in range(hist_sequence_length, int((len(data) / 24 - forecast_sequence_length)-1)):
        x = i
        profile = np.tile(pslp[i], forecast_sequence_length).reshape(90, 24)
        full_profile = np.hstack(
            (profile, positional_encoding_out.reshape(90, 1)))
        pslp_decoder.append(full_profile)
        residuum_list.append(
            np.hstack((regrouped_data[x - hist_sequence_length:x] - np.tile(pslp[i], hist_sequence_length).reshape(
                hist_sequence_length, 24), positional_encoding_in.reshape(hist_sequence_length, 1))))
        output.append(
            data[x * 24:x * 24 + forecast_sequence_length * 24].reshape(90, 24))
    input_tensors = data_tensor.Dataset.from_tensor_slices(
        tuple([np.array(residuum_list), np.array(pslp_decoder)]))
    output_tensor = data_tensor.Dataset.from_tensor_slices(np.array(output))
    training_data = data_tensor.Dataset.zip(
        (input_tensors, output_tensor)).shuffle(192)
    part = int(len(output) * split)
    train = training_data.take(part)
    val = training_data.skip(part)
    profile_forecast = np.tile(
        pslp[-1], forecast_sequence_length).reshape(90, 24)
    input_decoder_forecast = np.hstack(
        (profile_forecast, positional_encoding_out.reshape(90, 1)))
    a = regrouped_data[-hist_sequence_length:].reshape(hist_sequence_length, 24) - np.tile(
        pslp[-1], hist_sequence_length).reshape(hist_sequence_length, 24)
    input_encoder_forecast = np.hstack(
        (a, positional_encoding_in.reshape(hist_sequence_length, 1)))
    forecast_x = [input_encoder_forecast.reshape((1, input_encoder_forecast.shape[0], input_encoder_forecast.shape[1])),
                  input_decoder_forecast.reshape((1, input_decoder_forecast.shape[0], input_decoder_forecast.shape[1]))]

    return {'train': train, 'val': val,
            'forecast': forecast_x}, profile_forecast


def data_length_pre_check(data, minimum_length):
    if len(data) < minimum_length:
        return False
    else:
        return True


def function_dataset_long_term_load_forecast(data, target_column, time_now, scaler, forecast_mode, split=0.9,
                                             opt_args=None):
    """
    Function

        calculate features for long term forecast

    Parameter

        time_now : datetime
            current timestamp
        forecast_mode : bool
            activate train

    Return

        dataset_short_term_forecast : dict
            all data needed for train and forecast
    """
    historical_window_length = 14
    forecast_window_length = 90
    minimum_length = (historical_window_length + forecast_window_length) * \
        int(pd.to_timedelta('24H')/pd.to_timedelta('15Min'))
    data_length_pre_check_result = data_length_pre_check(
        data.get('load_measurements'), minimum_length)
    if data_length_pre_check_result is True:
        dataset_long_term_forecast = to_current_date(time_now, data, opt_args)
        resampled_data = dataset_long_term_forecast.resample('1H').mean()
        if forecast_mode is False:
            scaler.scaler_fit(dataset_y=resampled_data,
                              dataset_x=resampled_data, save=True)
        resampled_data, _ = scaler.scaler_transform(
            dataset_y=resampled_data, dataset_x=resampled_data)
        resampled_data['day'] = resampled_data.index.day
        resampled_data['year'] = resampled_data.index.year
        resampled_data['month'] = resampled_data.index.month
        grouped = resampled_data.groupby(["year", "month", "day"])
        stack = []
        for d in grouped.groups.keys():
            day_data = np.array(grouped.get_group(
                d)[[target_column]]).squeeze()
            if day_data.size == 24:
                stack.append(np.array(grouped.get_group(d)
                                      [[target_column]]).squeeze())
        regrouped_data = np.stack(stack, axis=0)
        pslp = calc_fast_PSLP(regrouped_data, 7)
        positional_encoding_in = np.true_divide(np.arange(1, historical_window_length + 1),
                                                historical_window_length)
        positional_encoding_out = np.true_divide(np.arange(1, forecast_window_length + 1),
                                                 forecast_window_length)
        # forecast_pslp = self.pslp.forecast(resampled_data.first_valid_index(), duration)
        dataset_with_horizon = prepare_dataset_for_forecasting(resampled_data.drop(columns=['year', 'month', 'day']),
                                                               '90D', '1H',
                                                               time_now=time_now)
        dataset_with_horizon = dataset_with_horizon[target_column]
        dataset_long_term_forecast, profile_forecast = dataset_to_supervised(dataset_with_horizon, regrouped_data, pslp,
                                                                             positional_encoding_in,
                                                                             positional_encoding_out,
                                                                             hist_sequence_length=historical_window_length,
                                                                             forecast_sequence_length=forecast_window_length)
        scaler.current_data_index = dataset_with_horizon.iloc[-forecast_window_length * 24:].index
    else:
        dataset_long_term_forecast = to_current_date(time_now, data, opt_args)
        resampled_data = dataset_long_term_forecast.resample('1H').mean()
        resampled_data['day'] = resampled_data.index.day
        resampled_data['year'] = resampled_data.index.year
        resampled_data['month'] = resampled_data.index.month
        dataset_with_horizon = prepare_dataset_for_forecasting(resampled_data.drop(columns=['year', 'month', 'day']),
                                                               '90D', '1H',
                                                               time_now=time_now)
        dataset_long_term_forecast = dataset_with_horizon.iloc[-96*24:, :].fillna(
            0)

    return {'datasets_train_forecast': dataset_long_term_forecast, 'optional_out': None, 'enough_data': data_length_pre_check_result}, scaler
