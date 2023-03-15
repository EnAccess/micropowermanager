import numpy as np
import pandas as pd
import scipy.stats as stats


def __split_date_range(first, last, frequency, date_ranges):
    dates = pd.date_range(start=first.date(),
                          end=last.date(), freq='1D', tz=first.tzinfo)
    for i in range(0, len(dates)):
        if i == 0:
            start = first
            end = pd.to_datetime(dates[1] - pd.to_timedelta(frequency))
        elif i == len(dates) - 1:
            start = dates[i]
            end = last
        else:
            start = dates[i]
            end = dates[i + 1] - pd.to_timedelta(frequency)
        date_ranges.append([start, end])
    return date_ranges


def __find_date_ranges(data):
    """
    Function

        Date ranges of nan values e.g. no measurements were available from 10:00 till 11:00

    Parameter

        data : pd.DataFrame
            data to check for missing values

    Return

        date_ranges : list of datetimes
            list of date ranges [start, end]

    """
    nan_rows = data[data.isna().any(axis=1)].copy()
    nan_rows = nan_rows.drop(columns=['absorbed_energy_since_last'])
    nan_rows['date'] = nan_rows.index.date
    nan_rows['time'] = nan_rows.index.time
    last = nan_rows.groupby(by='date').last()
    first = nan_rows.groupby(by='date').first()
    first['last'] = pd.to_datetime(first.index.astype(
        str) + ' ' + last['time'].astype(str))
    first['time'] = pd.to_datetime(first.index.astype(
        str) + ' ' + first['time'].astype(str))
    first['last'] = pd.to_datetime(
        first['last']).dt.tz_localize('Africa/Dar_es_Salaam')
    first['time'] = pd.to_datetime(
        first['time']).dt.tz_localize('Africa/Dar_es_Salaam')
    first = first.values.tolist()
    return first


def mean_profile(frequency, column, data, look_back='28D', date='', nbins=48):
    """
    Function

        Calculate mean profile of measurements within the recent x days

    Parameter


        data : pd.DataFrame
            data for calulating mean profile

        frequency : str
            frequency the data should have

        column : str
            target column to calculate mean profile for

        look_back : str
            days to consider for calculating profile

        date : str
            date to start mean profile calculating from


    Return

        profile : pd.DataFrame
            mean profile of data

        std : float
           standard deviation
    """
    end = pd.to_datetime(date)
    start = pd.to_datetime(date) - pd.to_timedelta(look_back)
    profile_set = data.copy(deep=True)
    profile_set = profile_set.resample(frequency).asfreq()
    profile_set = profile_set.loc[start:end, :]
    # extrahieren der Zeit Daten (Datum und Uhrzeit)
    profile_set['time'] = profile_set.index.time
    profile_set['date'] = profile_set.index.date
    # Frame zum reinsortieren
    sorted_frame = pd.DataFrame(
        index=profile_set['time'].unique(), columns=profile_set['date'].unique())
    # Pro Tag bzw. jedes Datum in meiner Zeitreihe
    for date in profile_set['date'].unique():
        try:
            # Filter nach dem Tag
            data_date = profile_set[profile_set['date'] == date]
            # Sortieren des Tages der Uhrzeit
            data_date = data_date.sort_values(by='time', ignore_index=True)
            # Uhrzeit als Index
            data_date.index = data_date['time']
            # Tag als Spalte im DatafFrame für die Sortierung einfügen
            sorted_frame.loc[data_date.index, date] = data_date[column]
        except:
            pass
    # std = sorted_frame.std(axis=1).mean(axis=0)
    profile = sorted_frame.mean(axis=1).to_frame()
    test = sorted_frame.T.values[:-1, :]
    prob = np.zeros((test.shape[1], nbins))
    values = np.zeros((test.shape[1], nbins))
    for i in range(test.shape[1]):
        qwedasd = np.array(test[:, i], dtype=np.float64)
        qwedasd = qwedasd[~np.isnan(qwedasd)]
        tmp = np.histogram(qwedasd, bins=nbins)
        if sum(tmp[0]) == 0:
            k = 1
            prob[i, :] = tmp[0] / k
        else:
            prob[i, :] = tmp[0] / sum(tmp[0])
        values[i, :] = tmp[1][1:]
    return profile, prob, values


def generate_noise(length, std):
    """
    Function

        Generate some random noise based on standard deviation of data

    Parameter

        length : int
            length of sequence with random noise

        std : int
            standard deviation for random generator

    Return

        samples : numpy.array
            array of random generated noise
    """
    # lower, upper, mu, and sigma are four parameters
    lower, upper = 0.95, 1.35
    mu, sigma = 1, std

    # instantiate an object X using the above four parameters,
    X = stats.truncnorm((lower - mu) / sigma,
                        (upper - mu) / sigma, loc=mu, scale=sigma)

    # generate 1000 sample data
    samples = X.rvs(length)
    return samples


def SampleFromDistribution(prob, values):
    size = prob.shape[0]
    random_idx = np.random.rand(size)
    sample = np.zeros(size)
    for idx in range(size):
        cumprob = np.cumsum(prob[idx, :])
        for i in range(prob.shape[1]):
            if cumprob[i] > random_idx[idx]:
                sample[idx] = values[idx, i - 1]
                break
    return sample


def augmentation(data, start_time, end_time, column, frequency, look_back='28D'):
    """
    Function

        Reconstruct missing values

    Parameter

        data : pd.DataFrame
            data to fill in missing values

        start_time : str or datetime
            start of time range of missing values

        end_time : str or datetime
            end of time range of missing values

        column : str
            column to augment the data

        frequency : str
            frequency the data should have

        look_back : str
            look_back to calculate a mean profile as basis for augmentation


    Return

        augmented_data : pd.DataFrame
            data with missing values filled
    """
    range_data = pd.date_range(start=start_time, end=end_time, freq=frequency)
    time = pd.to_datetime(start_time) - pd.to_timedelta(frequency)
    day_0 = pd.to_datetime(str(start_time.date()) +
                           ' 00:00').tz_localize(pd.to_datetime(start_time).tzinfo)
    number_start = int((start_time - day_0) / pd.to_timedelta(frequency))
    number_end = int((end_time - day_0) / pd.to_timedelta(frequency)) + 1
    profile, prob, values = mean_profile(frequency=frequency, look_back=look_back, date=time, column=column,
                                         data=data)
    E = np.sum(prob * values, axis=1)
    samp = SampleFromDistribution(prob, values)
    noise_full = samp - E
    noise = noise_full[number_start:number_end]
    if not noise.size:
        if len(profile) < number_end - number_start:
            augmented_data = pd.DataFrame(index=range_data, columns=['augmented_data'],
                                          data=np.full((number_end - number_start, 1),
                                                       0))  # possibility: use the previous values
        else:
            augmented_data = pd.DataFrame(
                index=range_data, columns=['augmented_data'], data=profile.values)
    else:
        augmented_data = pd.DataFrame(
            index=range_data, columns=['augmented_data'], data=noise)
        augmented_data['time'] = augmented_data.index.time
        for index, row in augmented_data.iterrows():
            time = augmented_data.loc[index, 'time']
            augmented_data.loc[index, 'augmented_data'] = (
                    augmented_data.loc[index, 'augmented_data'] + profile.loc[time, 0])
    return augmented_data


def data_augmentation(dataset, frequency, column):
    """
    Function

        Main function of the data augmentation which is used to fill missing values.

    Idea

        The idea is to calculate a mean profile based on recent x-days of measurement (currently 28 days).
        In the current datasets there are no daily changes e.g. between weekday or weekend. Because of
        that it is possible to simply use all data within this period. On this profile random noise is
        applied to avoid the algorithm of learning the exact same profile over and over again. By using
        random noise the algorithm has to filter the underlaying structure itself

    Parameter

        dataset : pd.DataFrame
            data to fill in missing values

        frequency : str
            frequency the data should have

        column : str
            column to fill missing values

    Return

        dataset : pd.DataFrame
            dataset with filled missing values

    Notes

        If data set shows some sign of weekday seasonalities e.g. changes between weekend and weekday
        the calculation of the mean profile has to be changed. An example of how this should be changed
        is shown in the PSLP.py which seperates the data concerning: weekday, weekend, holiday. If there
        are seasonal repetitions these have to be considered as well.

    """
    dataset = dataset.groupby(by=dataset.index).mean()
    dataset = dataset.resample(frequency).asfreq()
    nan_ranges = __find_date_ranges(dataset)
    augmented_dataset = dataset.copy(deep=True)
    for nan in nan_ranges:
        start = nan[0]
        end = nan[1]
        restored_data = augmentation(
            augmented_dataset, start, end, column=column, frequency=frequency)
        augmented_dataset.loc[start:end,
        column] = restored_data['augmented_data']
    return augmented_dataset
