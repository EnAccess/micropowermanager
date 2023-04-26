import logging
import multiprocessing as mp

from functions.Configuration.configuration_class import Configuration
from functions.evaluation.evaluation import ForecastEvaluation
from functions.load_forecasting.load_forecaster import LoadForecasting
from functions.pv_forecasting.pv_forecaster import PVForecasting
from functions.utils.parser import string_to_bool
from functions.weather_data.weather_data import Weather

module_logger = logging.getLogger('Forecasting-Tool.forecasting_module')


def mp_function(queue, part, forecasting_mode, current_path, time_now, args):
    """
     Function

        In this function all parts for forecasting with the AI are combined.

    Principle

        It is necessary to move AI parts into a different process in order to have fast computing of neural network training.
        This is because Tensorflow does not allow training of multiple neural networks in one session and to have a
        clean cut between sessions these are calculated in different processes. This also leads to lower memory usage
        because memory is freed after ever session leaving no used dataset and neural networks in RAM after process has finished.
        To do so also dataset creation has to be done in the same process because otherwise tensorflow does not allow
        training (because these were then created in another session).


    Parameter

        queue : mp.Queue
            queue to send data back to main process

        part : str
            part which gets AI training/prediction

        forecasting_mode : bool
            True : no training is done
            False: AI gets trained

        current_path : str
            current folder

        time_now : pd.datetime
            time of current prediction

        args : dict/list...
            optional arguments

    """
    # try:
    from functions.AI.AI import AI
    ai = AI(forecasting_mode, current_path, part)
    created_data = ai.create_dataset(args.get('data'), args.get('target_column'), time_now, forecasting_mode, part,
                                     args.get('optional_args'))
    print("Data set created..")
    if created_data.get('enough_data') is True:
        print('enough data: ', created_data.get('enough_data'))
        ai.train(args.get('eval_rec'), part, created_data.get('datasets_train_forecast'),
                 time_now)
        prediction = ai.forecast(
            part, created_data.get('datasets_train_forecast'))
    else:
        prediction = created_data.get('datasets_train_forecast')
    queue.put({'prediction': prediction,
              'optional_data': created_data.get('optional_out')})
    # except Exception as e:
    #    module_logger.critical(e, exc_info=True)  # log exception info at CRITICAL log level


class ForecastingModule:
    """
    Function

        ForecastingModule(FM) combines all important functions to forecast Power Generation and Load.
        This is the main function which should be called instead of utilizing the different functions
        themselves. This makes using all needed functions easy and straight forward. Respective methods
        of the utilized classes are further described in their specific parts.

    Order & Methods:

        As described in documentation file order of methods used is as follows:
        1. update_data: gets new data from server and standardizes data for following steps
        2. eval_recent_forecasts: evaluates all recent predictions
        3. prepare_datasets: prepares datasets concerning missing values, double values,
            features needed, shape needed
        4. train: trains algorithm if specified by eval_recemt_forecasts method
        5. ai_forecast: predicts with ML algorithms
        6. add_statistical_forecasts: Adding statistical forecasts
        7. forecasts_to_excel: writes the forecasts to excel file of optimizer

    Attributes

        forecasting_mode : bool
             variable to enable or disable training. On devices like nvidia Jetson True is
             recommended else False

        weather : class
             which updates and prepares weather data from server

        pv : class
             for preparing pv data

        load : class
             for preparing load data

        ai : class
             which has all ai models, training and prediction methods

        eval : class
            which evaluates recent forecasts and recommends training and model to use

    """

    def __init__(self, current_path):
        """
        Function

            Initializes all needed modules for: WeatherData, PV-Forecasting, Load-Forecasting, AI,
            Evaluation


        Attributes

            forecasting_mode : bool
                 variable to enable or disable training. On devices like nvidia Jetson True is recommended
                 else False

            weather : class
                 which updates and prepares weather data from server

            pv : class
                 for preparing pv data

            load : class
                 for preparing load data

            ai : class
                 which has all ai models, training and prediction methods

            eval : class
                evaluates recent forecasts and recommends training and model to use

        """
        self.current_path = current_path
        module_logger.info('Initializing Modules')
        # read in selected mode from config
        self.forecasting_mode = string_to_bool(
            Configuration.getConfigValue('general', 'forecasting_mode'))
        module_logger.info(
            f'Programm is running in forecasting mode: {str(self.forecasting_mode)}')
        # init all needed modules
        self.weather = Weather(current_path)
        sun_times = self.weather.set_up_sun_times()
        self.pv = PVForecasting(sun_times, current_path)
        self.load = LoadForecasting(current_path)
        self.eval = ForecastEvaluation(current_path)
        self.predictions = {'pv_short': None,
                            'pv_long': None, 'lf_short': None, 'lf_long': None}
        module_logger.info('Modules initialized.')

    def update_data(self, time_now):
        """
        Function

            First step of forecasting is to update all data. This invokes for PV, Load and WeatherModule
            the responsible methods to download new data, preprocess them and add these new data to already
            available datasets.

        Parameters

            time_now: pd.datetime
                current datetime

        Returns

            No returns needed as data is stored in class itself

        """
        module_logger.info('Start updating data.')
        self.load.get_data_from_server(time_now)
        self.load.preprocess_data()
        weather_links = self.pv.get_data_from_server(time_now)
        self.pv.preprocess_data()
        self.weather.get_data_from_nasa(time_now)
        self.weather.get_historic_data_from_server(weather_links)
        self.weather.preprocess_data()
        self.weather.get_forecast_data_from_server(weather_links)
        self.current_predictions = {}
        module_logger.info('Data updated.')

    def __mp_constructor(self, part, forecasting_mode, current_path, time_now, args):
        queue = mp.Queue()
        process = mp.Process(target=mp_function, args=(
            queue, part, forecasting_mode, current_path, time_now, args))
        process.start()
        return process, queue

    def prepare_train_predict(self, time_now):
        """
        Function

            starts process to prepare dataset, train AI (optional) and predict

        Parameter

            time_now : pd.datetime
                current time for forecasting

        Return

            None
        """
        pv_short_args = {
            'data': {'pv_measurements': self.pv.filled_dataset, 'historical_weather_data': self.weather.dm.data,
                     'weather_forecast_data': self.weather.dm.weather_forecast_data},
            'eval_rec': self.eval.recommendation.get('pv_short'), 'target_column': self.pv.dm.target_columns,
            'optional_args': None}
        pv_long_args = {'data': {'pv_measurements': self.pv.filled_dataset,
                                 'long_tern_historical_weather_data': self.weather.dm.long_term_weather_data},
                        'eval_rec': self.eval.recommendation.get('pv_long'), 'target_column': self.pv.dm.target_columns,
                        'optional_args': self.pv.trick}
        lf_short_args = {'data': {'load_measurements': self.load.dm.data},
                         'eval_rec': self.eval.recommendation.get('lf_short'),
                         'target_column': self.load.dm.target_columns,
                         'optional_args': {'data_frequency': self.load.dm.data_frequency}}
        lf_long_args = {'data': {'load_measurements': self.load.dm.data},
                        'eval_rec': self.eval.recommendation.get('lf_long'),
                        'target_column': self.load.dm.target_columns,
                        'optional_args': {'data_frequency': self.load.dm.data_frequency}}
        args = {'pv_short': pv_short_args, 'pv_long': pv_long_args,
                'lf_short': lf_short_args, 'lf_long': lf_long_args}
        for part in ['lf_long', 'pv_long', 'pv_short', 'lf_short']:
            module_logger.info(f'Starting AI Session for: {part}')
            process, queue = self.__mp_constructor(part, self.forecasting_mode, self.current_path, time_now,
                                                   args.get(part))
            self.predictions[part] = queue.get()
            module_logger.info(
                f'Results retireved closing AI Session for: {part}')
            process.join()
            module_logger.info(f"Session closed for: {part}")
        self.pv.pv_power_max_short = self.predictions.get(
            'pv_short').get('optional_data')
        # self.predictions['lf_long']= {'prediction': self.load.recombine_forecast(self.predictions.get('lf_long'))}
        module_logger.info('Retrieved optional data for: pv_short')

    def prepare_datasets(self, time_now):
        """
        DEPRECATED
        Function

            combines all functions from the subclasses to prepare datasets for training and forecasting.

        Principle

            There are in total four commands utilizing preparations for long and short term dataset
            preparation for PV and Load respectively.

        Parameters

            time_now:  pd.datetime
                current datetime

        Returns

            No returns needed as data is stored in class itself

        """
        module_logger.info('Preparing datasets.')
        self.load.features_short_term_forecast(time_now)
        self.load.features_long_term_forecast(time_now)
        self.pv.create_dataset_short_term_forecast(time_now, self.weather.dm.data,
                                                   self.weather.dm.weather_forecast_data,
                                                   forecast_mode=self.forecasting_mode)
        self.pv.create_dataset_long_term_forecast(time_now, self.weather.dm.long_term_weather_data,
                                                  self.forecasting_mode)
        module_logger.info('Datasets prepared successfully.')

    def train(self, time_now):
        """
        DEPRECATED
        Function

            trains all needed models if needed.

        Principle

            The decision when to train a network is done in eval-class (please see evaluation class
            for further information on that). Within eval-class a dictionary contains recommendations
            for training or deleting a model in order to do a clean start (self.eval.recommendation).
            Data for training are directly extracted for PV and Load class.

        Parameters

            time_now:  pd.datetime
                current datetime

        See also

            - LoadForecasting
            - PVForecasting
            - EvaluationModule
            - AI

        Returns

            No returns needed as data is stored in class itself
        """
        module_logger.info('Training stage.')
        self.ai.train(self.eval.recommendation.get('lf_long'), 'lf_long', self.load.dataset_long_term_forecast,
                      time_now)
        self.ai.train(self.eval.recommendation.get('lf_short'), 'lf_short', self.load.dataset_short_term_forecast,
                      time_now)
        self.ai.train(self.eval.recommendation.get('pv_short'), 'pv_short', self.pv.dataset_short_term_forecast,
                      time_now)
        self.ai.train(self.eval.recommendation.get('pv_long'),
                      'pv_long', self.pv.dataset_long_term_forecast, time_now)
        module_logger.info('Algorithms trained if needed.')

    def ai_forecast(self):
        """
        DEPRECATED
        Function

            Predicts with neural networks

        See also

            - LoadForecasting
            - PVForecasting
            - Scaler
            - AI

        Returns

            No returns needed as data is stored in class itself
        """
        module_logger.info('Forecasting stage.')
        self.ai.forecast(
            'pv_short', self.pv.dataset_short_term_forecast, self.pv.scaler_pv_short)
        self.ai.forecast(
            'pv_long', self.pv.dataset_long_term_forecast, self.pv.scaler_pv_long)
        self.ai.forecast(
            'lf_short', self.load.dataset_short_term_forecast, self.load.scaler_load_short)
        self.ai.forecast(
            'lf_long', self.load.dataset_long_term_forecast, self.load.scaler_long_term)
        module_logger.info('Forecating stage completed successfully.')

    def add_statistical_forecasts(self, time_now):
        """
        Function

            Predicts with statistical approaches

        Parameters

            time_now: pd.datetime
                current datetime


        See also

            - LoadForecasting
            - PVForecasting
            - AI

        Returns

            No returns needed as data is stored in class itself

        """
        module_logger.info('Adding statistical forecasts.')
        self.current_predictions['pv_short'] = self.pv.add_statistical_forecasts_short(
            self.predictions.get('pv_short').get('prediction'), time_now)
        self.current_predictions['lf_short'] = self.load.add_statistical_forecasts_short(
            self.predictions.get('lf_short').get('prediction'), time_now)
        self.current_predictions['lf_long'] = self.load.add_statistical_forecasts_long(
            self.predictions.get('lf_long').get('prediction'), time_now)
        self.current_predictions['pv_long'] = self.pv.add_statistical_forecasts_long(
            self.predictions.get('pv_long').get('prediction'), time_now)
        module_logger.info('Statistical forecasts added successfully.')

    def eval_recent_forecasts(self, time_now):
        """
        Function

            Evaluate the forecasts of previous predictions and recommends training or resetting models

        Principle

            Latest data is used to do evaluation of recent forecasts

        Parameters

            time_now: pd.datetime
                current datetime

        See also

            - LoadForecasting
            - PVForecasting

        Returns

            No returns needed as data is stored in class itself

        """
        module_logger.info('Beginning evaluation.')
        self.eval.evaluate_recent_forecasts(
            time_now, 'pv_short', self.pv.dm.data)
        self.eval.evaluate_recent_forecasts(
            time_now, 'pv_long', self.pv.dm.data)
        self.eval.evaluate_recent_forecasts(
            time_now, 'lf_short', self.load.dm.data)
        self.eval.evaluate_recent_forecasts(
            time_now, 'lf_long', self.load.dm.data)
        self.eval.recommend()
        module_logger.info('Evaluation finished successfully.')

    def forecasts_to_excel(self, time_now):
        """
        Function

            Output predictions to excel and csv file for optimizer

        Principle

            Excel-file provide data directly for linear optimizer whereas csv is used internally for evaluation

        Parameters

            time_now: pd.datetime
                current datetime

        Returns

            No returns needed as data is stored in class itself

        """
        module_logger.info('Beginning output to Excel.')
        self.eval.write_to_csv(self.current_predictions, time_now)
        self.eval.write_to_excel(self.current_predictions, time_now)
        self.current_predictions = {}
        module_logger.info('Output to Excel completed successfully.')
