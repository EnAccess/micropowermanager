import logging
import os

import joblib
import pandas as pd
from sklearn.preprocessing import MinMaxScaler

module_logger = logging.getLogger('Forecasting-Tool.Scaler')


class Scaler:
    def __init__(self, folder, name='load', forecasting_mode=False):
        """
        Attribute

             name: str
                load or pv as name

             folder: str
                foldername

            forecasting_mode: Bool
                Only Forecasting should be done (Scaler is not refit)

        """
        self.folder = folder
        self.scaler_x = None
        self.scaler_y = None
        self.name = name
        self.current_data_index = None
        if forecasting_mode is True:
            self.check_scaler_available()

    def check_scaler_available(self):
        """
        Function

            check if already fitted scaler are available and load them if available

        Return

            None

        """
        list = [self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x',
                self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_y']
        list_check = [os.path.isfile(self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x'),
                      os.path.isfile(self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_y')]
        if any(list_check):
            module_logger.info('Saved scaler will be loaded for: ' + self.name)
            for i in range(0, len(list)):
                if i == 0 and list_check[0]:
                    self.scaler_x = joblib.load(list[0])
                elif i == 1 and list_check[1]:
                    self.scaler_y = joblib.load(list[1])
                else:
                    pass
        else:
            pass

    def scaler_fit(self, dataset_x, dataset_y, save=False):
        """
        Function

            fit scaler to data

        Parameter

            dataset_x: pd.DataFrame
                dataset for descriptive features

            dataset_y: pd.DataFrame
                dataset for target features

            save: bool
                save fitted scaler


        Return

            trained scaler for x and y

        """
        dataset_y = dataset_y.values.reshape(-1, 1)
        self.scaler_x = MinMaxScaler().fit(dataset_x)
        self.scaler_y = MinMaxScaler().fit(dataset_y)
        os.makedirs(self.folder + '/resources/02_models/' + self.name + '/scaler/', exist_ok=True)
        if save == True:
            joblib.dump(self.scaler_x, self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x')
            joblib.dump(self.scaler_y, self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_y')

    def scaler_transform(self, dataset_x, dataset_y):
        """
        Function

            scale dataset

        Parameter

            dataset_x: pd.DataFrame
                dataset for descriptive features

            dataset_y: pd.DataFrame
                dataset for target features

        Return

            scaled datasets
        """
        dataset_x_index = dataset_x.index
        dataset_x_columns = dataset_x.columns
        dataset_y_index = dataset_y.index
        dataset_y_columns = dataset_y.columns
        dataset_y = dataset_y.values.reshape(-1, 1)
        dataset_x = pd.DataFrame(self.scaler_x.transform(dataset_x), index=dataset_x_index, columns=dataset_x_columns)
        dataset_y = pd.DataFrame(self.scaler_y.transform(dataset_y), index=dataset_y_index, columns=dataset_y_columns)
        return dataset_x, dataset_y

    def inverse_transform_prediction(self, prediction):
        """
        Function

            rescale prediction

        Parameter

            prediction: array
                AI forecasts

        Return

            rescaled prediction
        """
        prediction = prediction.reshape(-1, 1)
        prediction = pd.DataFrame(self.scaler_y.inverse_transform(prediction), columns=['power'])
        prediction.index = self.current_data_index
        return prediction

    def scaler_transform_for_prediction(self, dataset_x):
        """
        Function

            transforms dataset for AI prediction

        Parameter

            dataset_x : array
                dataset with features for forecasting stored

        Return

            scaled dataset for forecasting
        """
        self.current_data_index = dataset_x.index
        dataset_x = self.scaler_x.transform(dataset_x)
        return dataset_x

    def set_index(self, index):
        """
        Function

            store index

        Parameter

            index: pd.Series
                Series containing original index

        Return

            None
        """
        self.current_data_index = index


class Scaler_pv_long:
    def __init__(self, name='load', forecasting_mode=False):
        """
        Function

            PV Long has special scaling needs most parts are close to normal scaler but with
            some special extensions

        Attribute

            name: str
                load or pv as name

            forecasting_mode: Bool
                Only Forecasting should be done (Scaler is not refit)
        """
        self.scaler_x = None
        self.scaler_y = None
        self.scaler_long = None
        self.name = name
        self.current_data_index = None
        if forecasting_mode is True:
            self.check_scaler_available()

    def check_scaler_available(self):
        """
        Function

            check if already fitted scaler are available and load them if available

        Return

            None

        """
        list = [self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x',
                self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_y',
                self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x_long']
        list_check = [os.path.isfile(self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x'),
                      os.path.isfile(self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_y'),
                      os.path.isfile(self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x_long')]
        if any(list_check):
            module_logger.info('Saved scaler will be loaded for: ' + self.name)
            for i in range(0, len(list)):
                if i == 0 and list_check[0]:
                    self.scaler_x = joblib.load(list[0])
                elif i == 1 and list_check[1]:
                    self.scaler_y = joblib.load(list[1])
                elif i == 2 and list_check[2]:
                    self.scaler_long = joblib.load(list[2])
                else:
                    pass
        else:
            pass

    def scaler_fit(self, dataset_x, dataset_y, dataset_long, save=False):
        """
        Function

            fit scaler to data

        Parameter

            dataset_x: pd.DataFrame
                dataset for descriptive features

            dataset_y: pd.DataFrame
                dataset for target features

            dataset_long: pd.DataFrame
                dataset containing special long term variables

            save: bool
                save fitted scaler


        Return

            trained scaler for x and y

        """
        dataset_y = dataset_y.values.reshape(-1, 1)
        self.scaler_long = MinMaxScaler().fit(dataset_long.values)
        self.scaler_x = MinMaxScaler().fit(dataset_x)
        self.scaler_y = MinMaxScaler().fit(dataset_y)
        os.makedirs(self.folder + '/resources/02_models/' + self.name + '/scaler/', exist_ok=True)
        if save == True:
            joblib.dump(self.scaler_x, self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x')
            joblib.dump(self.scaler_y, self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_y')
            joblib.dump(self.scaler_x, self.folder + '/resources/02_models/' + self.name + '/scaler/scaler_x_long')

    def scaler_transform(self, dataset_x, dataset_y, dataset_long):
        """
        Function

            scale dataset

        Parameter

            dataset_x: pd.DataFrame
                dataset for descriptive features

            dataset_y: pd.DataFrame
                dataset for target features

            dataset_long: pd.DataFrame
                dataset containing special long term variables


        Return

            scaled datasets
        """
        dataset_y = dataset_y.values.reshape(-1, 1)
        dataset_x = self.scaler_x.transform(dataset_x)
        dataset_long = self.scaler_long.transform(dataset_long)
        dataset_y = self.scaler_y.transform(dataset_y)
        return dataset_x, dataset_y, dataset_long

    def inverse_transform_prediction(self, prediction):
        """
        Function

            rescale prediction

        Parameter

            prediction: array
                AI forecasts

        Return

            rescaled prediction
        """
        prediction = prediction.reshape(-1, 1)
        prediction = pd.DataFrame(self.scaler_y.inverse_transform(prediction), columns=['power'])
        prediction.index = self.current_data_index
        return prediction

    def scaler_transform_for_prediction(self, dataset_x, dataset_long):
        """
        Function

            transforms dataset for AI prediction

        Parameter

            dataset_x : array
                dataset with features for forecasting stored

            dataset_long: pd.DataFrame
                dataset containing special long term variables

        Return

            scaled dataset for forecasting
        """
        self.current_data_index = dataset_x.index
        dataset_x = self.scaler_x.transform(dataset_x)
        dataset_long = self.scaler_long.transform(dataset_long)
        return dataset_x, dataset_long
