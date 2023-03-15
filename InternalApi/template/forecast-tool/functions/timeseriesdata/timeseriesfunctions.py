from functions.data.data_augmentation import data_augmentation
from functions.utils.path_utils import check_file_cache
import warnings
from statsmodels.tsa.seasonal import seasonal_decompose
import logging
import os
from collections.abc import Iterable

import matplotlib.pyplot as plt
import pandas as pd

pd.options.mode.chained_assignment = None


module_logger = logging.getLogger('Forecasting-Tool.TimeSeriesDataFrame')


class TimeSeriesDataFrame:
    """
    Function

        A class which combines several basic functions for time series data

    Attributes

        data : pd.DataFrame
            DataFrame with time series

        tz_info : str
            str resembling timezone of time series

        time_column_name : str
            str which is name of time column

        time_column_is_index : bool
            bool to check if timecolumn is set as index

        last_timestamp : datetime
            datetime of last timestep in the dataset

        data_frequency : str
            str frequency the data should have

        times_with_many_missing_values : list
            list of missing values [start ,end]

    """

    def __init__(self):
        """
        Attributes

            data : pd.DataFrame
                DataFrame with time series

            tz_info : str
                str resembling timezone of time series

            time_column_name : str
                str which is name of time column

            time_column_is_index : bool
                bool to check if timecolumn is set as index

            last_timestamp : datetime
                datetime of last timestep in the dataset

            data_frequency : str
                str frequency the data should have

            times_with_many_missing_values : list
                list of missing values [start ,end]

        """
        # set up internal variables
        self.data = None
        self.tz_info = None
        self.time_column_name = None
        self.time_column_is_index = False
        self.last_timestamp = None
        self.data_frequency = None
        self.times_with_many_missing_values = None

    def __try_to_get_timezone_information(self, series_with_timezone: pd.Series, timezone: str):
        """
        Function

            Try to get timezone information out of time data in dataset

        Parameter

            series_with_timezone : pd.Series or pd.DataFrame
                series with timedata
            timezone : None or str
                if None timezone is extracted from series or will be set to None

        Return

            None

        """
        series_with_timezone = series_with_timezone.iloc[0]
        if series_with_timezone.tzinfo is None and timezone is None:
            pass
        elif series_with_timezone.tzinfo is not None and timezone is None:
            self.tz_info = series_with_timezone.tzinfo
        elif series_with_timezone.tzinfo is None and timezone is not None:
            self.tz_info = timezone

    def __check_date_time_index(self):
        """
        Function

            Check if time is set as index

        Parameter

            None

        Return

            True if index is datetime else False

        """
        index = self.data.index
        if isinstance(index, pd.DatetimeIndex):
            return True
        else:
            return False

    def __get_date_information(self):
        """
        Function

            Return timestamp from data

        Parameter

            None

        Return

            time_data : pd.Series
                Timestamps

        """
        if self.time_column_is_index is True:
            time_data = self.index
        elif self.time_column_is_index is False:
            time_data = self.data[self.time_column_name]
        return time_data

    def delete_outliers(self, min, max):
        """
        Function

            Deleting values between a range of min and max.

        Parameter

            min: float
                lower limit

            max: float
                upper limit

        Returns:

            data: pd.DataFrame
                data with values in the range of min and max
        """
        data = self.data.copy(deep=True)
        data = data[data[self.target_columns] >= min]
        data = data[data[self.target_columns] <= max]
        self.data = data
        return data

    def check_for_double_values(self):
        """
        Function

            Check for indexes which are double in self.data

        Parameter

            None

        Return

            None

        """
        if self.data is not None:
            data = self.data.copy(deep=True)
            data = data.groupby(data.index).mean()
            if len(data) < len(self.data):
                warnings.warn(
                    'Double Values in data found. Values were cleaned and set as data')
                self.data = data

    def set_dataset_frequency(self, frequency, reindex=False):
        """
        Function

            changes attribute data_frequency and reindex data if wanted to specified frequency

        Parameter

            frequency : str
                frequency which the data should have or has

            reindex : bool
                reindex timeseries to specified frequency


        Return

            None

        """
        if frequency is not None:
            self.data_frequency = frequency
            if self.data is not None and reindex is True:
                self.check_for_double_values()
                self.data = self.data.resample(frequency).asfreq()

    def load_data_from_csv(self, path: str, sep: str = ',', names=None, index_col=None, header='infer',
                           delimiter=None, decimal='.'):
        """
        Function

            load data from a csv file and set as self.data

        Parameter

            path : str
                path to csv file

            sep : str
                sign for value separation in file

            names : list of str
                names of columns

            index_col : str
                column name which should be set as index

            header : str
                header information

            delimiter : str
                Alias for sep

            decimal : str
                sign for differentiation between decimal and full number

        Return

            None

        """
        self.data = pd.read_csv(filepath_or_buffer=path, sep=sep, names=names, index_col=index_col, header=header,
                                delimiter=delimiter, decimal=decimal)

    def update_last_timestamp(self):
        """
        Function

            update attribute last_timestamp manually

        Parameter

            None


        Return

            None

        """
        self.last_timestamp = self.data.last_valid_index()

    def combine_two_str_columns(self, column_names: list, name_new_column: str):
        """
        Function

            add two string columns and add them as a new column

        Parameter

            column_names : list of str
                column names to combine

            name_new_column : str
                column name to put aggregated columns in

        Return

            None

        Errors

            TypeError for column_names if not iterable

        """
        if isinstance(column_names, Iterable):
            self.data[name_new_column] = self.data[column_names[0]] + \
                self.data[column_names[1]]
        else:
            raise TypeError('column_names has to be iterable!')

    def format_time_column(self, column_name: str, timezone=None, frequency=None, external_date_data: pd.Series = None,
                           format: str = None):
        """
        Function

            Converts time column to pd.datetime including timezone information

        Parameter

            column_name : str
                name of time column

            timezone : str
                timezone of time series

            frequency : str
                raw frequency of time series

            external_date_data : pd.Series
                external date data if not in data

            format : str
                format of datetime

        Return

            None

        """
        self.set_dataset_frequency(frequency)
        if external_date_data is not None:
            date_data = external_date_data
        else:
            date_data = self.data[column_name]
        transformed_data = pd.to_datetime(date_data, format=format)
        self.__try_to_get_timezone_information(transformed_data, timezone)
        if timezone is not None and transformed_data.iloc[0].tzinfo is None:
            transformed_data = transformed_data.dt.tz_localize(timezone)
        elif timezone is not None and transformed_data.iloc[0].tzinfo is not None:
            transformed_data = transformed_data.dt.tz_convert(timezone)
        else:
            pass
        transformed_data = transformed_data.drop(columns=[column_name])
        if column_name in self.data.columns:
            self.data = self.data.drop(columns=[column_name])
            self.data = self.data.sort_index()
        self.time_column_is_index = True
        self.data.index = transformed_data
        self.data = self.data.sort_index()
        self.last_timestamp = self.data.last_valid_index()
        self.time_column_name = column_name
        self.tz_info = timezone

    def localize_time_column(self, timezone, column: str = None, set_index=False, external_date_data: pd.Series = None,
                             keep_time_column=True):
        """
        Function

            Localize time column and optionally set it as index

        Parameter

            timezone : str
                timezone to localize time series

            column : str
                column of timestamp

            set_index : bool
                if time should be set as index

            external_date_data : pd.Series
                external date data if not in data

            keep_time_column : bool
                keep time column as seperate column if set as index

        Return

            None

        """
        time_data = None
        if external_date_data is not None:
            time_data = external_date_data
        elif external_date_data is None:
            if column is None and self.time_column_name is None:
                if self.__check_date_time_index() is False:
                    raise ValueError(
                        'No column was given and data index is not a datetime index. Please specify datetime column')
                else:
                    time_data = self.data.index
            elif column is not None or self.time_column_name is not None:
                if column is not None:
                    time_data = self.data[column]
                elif self.time_column_name is not None:
                    if self.time_column_is_index is False:
                        column = self.time_column_name
                        time_data = self.data[self.time_column_name]
                    else:
                        time_data = self.data.index
            else:
                raise ValueError('No time column was given or found!')
        else:
            raise ValueError('Error in localize_time_column unknown failure!')
        if time_data is None:
            raise ValueError(
                'internal Error in localize time column: time_data is None')
        else:
            if not isinstance(time_data, (pd.DatetimeIndex, pd.TimedeltaIndex, str)):
                raise TypeError(
                    'Datetime has wrong Type. Has to be TimedeltaIndex, Datetimeindex or str')
            else:
                time_data = pd.to_datetime(time_data).tz_localize(timezone)
        if set_index is True:
            self.data.index = time_data
            if keep_time_column is False:
                self.data.drop(columns=[column])
        else:
            if column is None:
                new_name = 'time_converted'
            else:
                new_name = column
            self.data[new_name] = time_data
        self.tz_info = timezone
        self.data = self.data.sort_index()
        self.last_timestamp = self.data.loc[self.data.last_valid_index(
        ), column]

    def convert_timezone(self, timezone, column: str = None, set_index=False):
        """
        Function

            Convert specified datetime column to specified timezone. Optionally: set as index

        Parameter

            timezone : str
                timezone to convert to

            column : str
                column name with time data

            set_index : bool
                set converted timeline to index


        Return

            None

        """
        if self.tz_info is None:
            raise AttributeError(
                'No timezone found until now. Please localize the timedata first')
        else:
            if column is None:
                if self.time_column_is_index is True:
                    self.data.index = self.data.index.tz_convert(timezone)
                else:
                    if set_index is True:
                        self.data.index = self.data[self.time_column_name].dt.tz_convert(
                            timezone)
                        self.data = self.data.sort_index()
                        self.last_timestamp = self.data.last_valid_index()
                    else:
                        self.data[self.time_column_name] = self.data[self.time_column_name].tz_convert(
                            timezone)
                        self.data = self.data.sort_index()
                    self.last_timestamp = self.data.loc[self.data.last_valid_index(
                    ), self.time_column_name]
            else:
                if set_index is True:
                    self.data.index = self.data[column].dt.tz_convert(timezone)
                else:
                    self.data[column] = self.data[column].tz_convert(timezone)
                self.data = self.data.sort_index()
                self.last_timestamp = self.data.loc[self.data.last_valid_index(
                ), column]
            self.tz_info = timezone

    def column_to_index(self, column):
        """
        Function

            set column to index

        Parameter

            column : str
                column_name

        Return

            None

        """
        self.data.index = self.data[column]

    def __check_if_frequency_is_set(self):
        """
        Function

            check if frequency was specified

        Parameter

            None

        Return

            None
        """
        if self.data_frequency is None:
            raise ValueError('No Frequency set! Please set frequency first!')

    def resample_data(self, frequency=None):
        """
        Function

            resample data to specified frequency

        Parameter

            frequency : str
                frequency to set data to


        Return

            None

        """
        if frequency is None:
            frequency = self.data_frequency
        else:
            self.data_frequency = frequency
        self.data = self.data.resample(frequency).mean()
        return self.data

    def save_data(self, path, filename):
        """
        Function

            save_data to path/filename

        Parameter

            path : str
                path to directory

            filename : str
                filename of savefile

        Return

            None

        """
        module_logger.info('Saving data to: ' + path)
        data_to_save = self.data.copy(deep=True)
        data_to_save[self.time_column_name] = data_to_save.index
        check_file_cache(path, filename)
        data_to_save.to_csv(path + filename, index=False)
        module_logger.info('Data saved!')

    def __check_timezone(self, time):
        """
        Function

            check if timezone is set

        Parameter

            time : str or datetime
                timestamp to be checked


        Return

            None

        """
        if time is not None:
            if isinstance(time, str):
                time = pd.to_datetime(time)
            else:
                pass
            time_tz = time.tzinfo
            if time_tz == self.tz_info:
                return time
            elif not time_tz == self.tz_info and time_tz is None:
                return time.tz_localize(self.tz_info)
            elif not time_tz == self.tz_info and time_tz is not None:
                return time.tz_convert(self.tz_info)
            else:
                raise ValueError(
                    'Timezone is not consistent between TimeSeries and given Time')

    def data_in_period(self, start=None, end=None):
        """
        Function

            returns data from dataset between start and end

        Parameter

            start : datetime
                start timestamp

            end : datetime
                end timestamp

        Return

            data : pd.DataFrame
                data in period

        """
        end = self.__check_timezone(end)
        start = self.__check_timezone(start)
        data = self.data.copy(deep=True)
        if self.time_column_is_index is False:
            data.index = data[self.time_column_name]
        if start is not None:
            data = data.loc[start:, :]
        if end is not None:
            data = data.loc[:end, :]
        if self.time_column_is_index is False:
            data = data.reset_index
        return data

    def make_dir(self, path):
        """
        Function

            makes directory

        Parameter

            path : str
                path of directory to be made


        Return

            None

        """
        os.makedirs(path, exist_ok=True)

    def acf_plotter(self, columns: list = [], save_plot=False, show_plot=True,
                    save_path='', nlags=None, fft=True,
                    alpha=0.05):
        """
        plotts autocorrelationfunction

        Parameter
        ---------


        Return
        ------

        """
        import matplotlib.pyplot as plt
        from statsmodels.graphics.tsaplots import plot_acf
        data = self.data.copy(deep=True)
        data = data.interpolate()
        if columns == []:
            columns = data.columns
        for column in columns:
            series = data[column]
            series.index = series.index.tz_convert('UTC')
            plot_acf(x=series, lags=nlags, fft=fft, alpha=alpha)
            if save_plot is True:
                self.make_dir(save_path)
                path = save_path + 'acf_plot_' + str(column) + '.png'
                plt.save_fig(path)
            if show_plot is True:
                plt.show()

    def pacf_plotter(self, columns: list = [], save_plot=False, show_plot=True,
                     save_path='', nlags=None, method='ywadjusted', alpha=None):
        """
        plots partial autocorrelation function

        Parameter
        ---------


        Return
        ------

        """
        import matplotlib.pyplot as plt
        from statsmodels.graphics.tsaplots import plot_pacf
        data = self.data.copy(deep=True)
        self.__make_dir(save_path)
        for column in columns:
            series = data[column]
            plot_pacf(series, nlags, method, alpha)
            if save_plot is True:
                path = save_path + 'pacf_plot_' + str(column) + '.png'
                plt.save_fig(path)
            if show_plot is True:
                plt.show()

    def calculate_correlation(self, method='pearson', min_periods=1, plot=False, save=False,
                              save_path=''):
        """
        Function

            calculates correlation of data columns

        Parameter

            method : str
                method to be used to evaluate correlation

            min_periods : int

            plot : bool
                if heatmap should be plotted

            save : bool
                save result as csv

            save_path : str
                path to save results



        Return

            plot

        """
        import matplotlib.pyplot as plt
        import seaborn
        data = self.data.copy(deep=True)
        corr = data.corr(method, min_periods)
        if save is True:
            self.__make_dir(save_path)
            corr.to_csv(save_path + 'correlation.csv')
        if plot is True:
            self.__make_dir(save_path)
            g = seaborn.heatmap(corr, annot=True, cmap='RdYlGn')
            plt.savefig(save_path + 'correlation.png')
            plt.show()

    def downscale_time(self, frequency):
        """
        Function

            downscales time to another frequency e.g. 15 min to 1H

        Parameter

            frequency : str
                frequency to switch timeseries to


        Return

            None

        """
        data = self.data
        if not pd.to_timedelta(frequency) <= pd.to_timedelta(self.data_frequency):
            data = data.resample(frequency).mean()
            self.data_frequency = frequency
            self.data = data
        else:
            if pd.to_timedelta(frequency) < pd.to_timedelta(self.data_frequency):
                raise ValueError(
                    'Given Frequency is higher than before! Please use upscale!')
            elif pd.to_timedelta(frequency) == pd.to_timedelta(self.data_frequency):
                pass

    def upscale_time(self, frequency, interpolation_method='linear'):
        """
        Function

            upscales data using specified interpolation_method e.g. from 1H to 15 min

        Parameter

            frequency : str
                frequency to switch timeseries to

            interpolation_method : str
                method to interpolate between data points

        Return

            None
        """
        data = self.data
        if not pd.to_timedelta(frequency) >= pd.to_timedelta(self.data_frequency):
            data = data.resample(frequency).asfreq()
            data = data.interpolate(method=interpolation_method)
            self.data_frequency = frequency
            self.data = data
        else:
            if pd.to_timedelta(frequency) > pd.to_timedelta(self.data_frequency):
                raise ValueError(
                    'Given Frequency is lower than before! Please use downscale!')
            elif pd.to_timedelta(frequency) == pd.to_timedelta(self.data_frequency):
                pass

    def return_data_in_period(self, current_time=None, start_date=None):
        return self.data_in_period(start_date, current_time)

    def show_missing_values(self, external_data=None, frequency_raw_data='1s'):
        """
        Function

            checks for missing value and gives out statistics

        Parameter

            external_data : pd.DataFrame
                data to check

            frequency_raw_data : str
                frequency the data should have

        Return

            diff : int
                missing value count

        """
        if external_data is None:
            data = self.data.copy(deep=True)
            frequency_raw_data = self.data_frequency
        else:
            data = external_data
        data = data.dropna()
        data_frequency_repaired = data.resample(frequency_raw_data).asfreq()
        if len(data) == len(data_frequency_repaired):
            module_logger.info('No missing values detected')
            diff = 0
        elif len(data) < len(data_frequency_repaired):
            diff = len(data_frequency_repaired) - len(data)
            module_logger.info(
                'Missing Values detected! Number of missing values: ' + str(diff))
            module_logger.info('Preprocessing missing values necessary!')
        else:
            raise ValueError(
                'length of original dataset longer than repaired dataset was not expected!')
        return diff

    def check_for_days_with_large_amount_of_missing_values(self, maximum_percentage, data=None, frequency=None):
        """
        Function

            checks dataset for missing values bigger than a specified value

        Parameter

            maximum_percentage : float
                value between 0 and 1 as percentage when large amount of missing values is reached

            data : pd.DataFrame
                data to evaluate

            frequency : str
                frequency data should have


        Return

            list of days with high share of missing values

        """
        if data is None:
            data = self.data.copy(deep=True)
        if frequency is None:
            frequency = self.data_frequency
        data = data.resample(frequency).asfreq()
        data['date'] = data.index.date
        unique_dates = data['date'].unique()
        data = data.dropna()
        amount_of_measurements_per_day = pd.to_timedelta(
            '1D') / pd.to_timedelta(frequency)
        missing_value_frame = pd.DataFrame(index=unique_dates,
                                           columns=['no. missing values', 'percentage missing values'])
        days_to_drop = []
        for date in unique_dates:
            test_date = data.loc[data['date'] == date]
            measurements_on_day = len(test_date)
            number_of_missing_values = amount_of_measurements_per_day - measurements_on_day
            percentage_missing = number_of_missing_values / amount_of_measurements_per_day
            missing_value_frame.loc[date,
                                    'no. missing values'] = number_of_missing_values
            missing_value_frame.loc[date,
                                    'percentage_missing values'] = percentage_missing
            if percentage_missing > maximum_percentage:
                days_to_drop.append(date)
            else:
                pass
        if not days_to_drop == []:
            days_to_drop.pop(-1)
        return days_to_drop

    def missing_values_augmentation(self, column=None, drop_data_under_value_of=0.62):
        """
        Function

            more advanced missing value fill method using mean profile and random noise (should be used when many values are missing)

        Parameter

            column : str
                column to augment data

            drop_data_under_value : float
                if data lower then threshold data of day will be dropped


        Return

            dataset : pd.DataFrame
                filled dataset

        """
        if self.data_frequency is None:
            raise ValueError('Set frequency first!')
        if column is None:
            column = self.target_columns
        frequency = self.data_frequency
        dataset = self.data.copy(deep=True)
        dataset = dataset[dataset[column] >=
                          drop_data_under_value_of].copy(deep=True)
        dataset = dataset.resample(frequency).mean()
        dataset = data_augmentation(dataset, frequency, column)
        self.data = dataset
        return dataset

    def interpolate_data(self, columns=None, dropna=False, method='linear'):
        """
        Function

            interpolates missing values by using interpolation (should be used when there are some missing values)

        Parameter

            columns : list of str
                name of columns to interpolate

            dropna : bool
                if na values should be dropped

            method : str
                method of interpolation

        Return

            dataset : pd.DataFrame
                filled dataset

        """
        dataset = self.data.copy(deep=True)
        if columns is None:
            if isinstance(dataset, pd.DataFrame):
                columns = [dataset.columns]
            elif isinstance(dataset, pd.Series):
                dataset = dataset.to_frame()
                columns = [dataset.columns[0]]
            else:
                pass
        else:
            pass
        if dropna is True:
            dataset = dataset.dropna()
        else:
            pass
        dataset = dataset.resample(self.data_frequency).mean()
        for column in columns:
            dataset[column] = dataset[column].interpolate(method)
        self.data = dataset
        return dataset

    def rename_column(self, column_name, new_column_name):
        """
        Function

            rename specific column

        Parameter

            column_name : str
                column to be renamed

            new_column_name : str
                new name for column


        Return

            None

        """
        self.data[new_column_name] = self.data[column_name]
        self.data = self.data.drop(columns=[column_name])

    def sort_index(self):
        """
        Function

            sort the index

        Parameter

            None

        Return

            None

        """
        self.data = self.data.sort_index()

    def seasonal_decomposition(self, start=None, end=None, model='additive', column=None, period=7):
        """
        Function

            decomposes the data into trend, seasonal and residual parts

        Parameter

            start : datetime or str
                start date of dataset for investigation

            end : datetime or str
                end date of dataset for investigation

            model : str
                additive or multiplicative

            column : str
                which column to use

            period : int
                period in data

        Return

            plot

        """
        if start is not None or end is not None:
            data = self.data_in_period(start, end)
        else:
            data = self.data
        if column is None:
            column = data.columns
        data = data.interpolate()
        result = seasonal_decompose(data[column], model=model, period=period)
        result.plot()
        plt.show()

    def __check_if_dataframe_or_series(self, variable_to_check):
        """
        Function

            checks if variable is dataset or series

        Parameter

            variable_to_check : variable
                variable to check if dataframe or Series


        Return

            variable_to_check

        """
        if isinstance(variable_to_check, pd.DataFrame):
            pass
        elif isinstance(variable_to_check, pd.Series):
            variable_to_check = variable_to_check.to_frame()

        else:
            raise ValueError('data is not pd.Series or pd.DataFrame')
        return variable_to_check

    def __check_if_frequency_is_set(self):
        if self.data_frequency is None:
            raise ValueError('No Frequency set! Please set frequency first!')

    def add_data(self, data, time_column=None, time_data=None, time_is_index=False, timezone=None):
        """
        Function

            add new data to data in the class

        Parameter

            data : pd.DataFrame
                data to add

            time_column: str
                name of time column

            time_data : pd.Series or pd.DataFrame
                if time_data not in data

            time_is_index : bool
                if time is index

            timezone : str
                timezone of time series if not already specified


        Return

            Nothing to return

        """
        # extract time data
        data = self.__check_if_dataframe_or_series(data)
        if time_data is not None:
            time_data = self.__check_if_dataframe_or_series(time_data)
        if time_data is None and time_column is None and time_is_index is True:
            test_time = data.first_valid_index()
        elif time_data is not None and time_column is None and time_is_index is False:
            test_time = time_data.iloc[0, ]
            data.index = pd.to_datetime(time_data)
        elif time_data is None and time_column is not None and time_is_index is False:
            test_time = time_data.loc[time_data.first_valid_index(
            ), time_column]
            data.index = pd.to_datetime(data[time_column])
        extracted_timezone = pd.to_datetime(test_time).tzinfo
        if timezone is not None:
            if not extracted_timezone == timezone:
                if extracted_timezone is None:
                    data.index = data.index.tz_localize(timezone)
                else:
                    data.index = data.index.tz_convert(timezone)
        elif timezone is None:
            timezone = extracted_timezone
        if not timezone == self.tz_info:
            data.index = data.index.tz_convert(self.tz_info)
        self.data = pd.concat([self.data, data])
        self.data = self.data.sort_index()
        self.last_timestamp = self.data.last_valid_index()


def test():
    df = TimeSeriesDataFrame()
    df.load_data_from_folder(
        path='D:/EMMMS/Forecasting Tool/resources/01_measurements/load_seperate', sep=';')
    df.format_time_column(column_name='Time', timezone='Africa/Dar_es_Salaam')
    df.data = df.data.groupby(df.data.index).mean()
    df.set_dataset_frequency('15Min')
    df.show_missing_values(frequency_raw_data='15Min')
    # df.missing_values_augmentation(frequency='15Min')
    # df.acf_plotter(show_plot=True, nlags=96*28)
    # df.add_data(data=augmented_data['power'], time_is_index=True, timezone='Africa/Dar_es_Salaam')
    df.plot_data_in_period()
    print(1)


if __name__ == '__main__':
    test()
