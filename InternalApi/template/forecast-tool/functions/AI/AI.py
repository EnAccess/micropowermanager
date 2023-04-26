from functions.AI.models import *
from functions.AI.dataset_preparation_ai import *
from functions.scaling.scaling import Scaler
from functions.AI.routines import train_multi_input, train_test_loss_graph
from tensorflow.keras import backend as K
import tensorflow as tf

def train_var():
    """
    Function

        additional variables needed for training

    Return

        train_vars : dict
            containing batch size, epochs and learning rate
    """
    train_vars = {}
    train_vars['pv_short'] = {'bs': 32, 'lr': 0.001}
    train_vars['pv_long'] = {'bs': 256, 'lr': 0.0002}
    train_vars['lf_short'] = {'bs': 512, 'lr': 0.001}
    train_vars['lf_long'] = {'bs': 8, 'lr': 0.0005}
    return train_vars


def train_val_split(train_dataset_x, train_dataset_y, split=0.9):
    """
    Function

        Splitting tensors into train and val dataset

    Parameter

        train_dataset_x : tf.tensor
            data of features for training

        train_dataset_y : tf.tensor
            data of target value for training

        split : float [0,1]
            determines share of data in each category

    Return

        train_dataset_part : tf.tensor
            train dataset

        val_dataset_part : tf.tensor
            validation dataset

    """
    train_dataset_part = []
    val_dataset_part = []
    dataset = train_dataset_x
    train_dataset_part.append(dataset[:int(len(dataset) * split)])
    val_dataset_part.append(dataset[int(len(dataset) * split):])
    train_data_y = train_dataset_y[:int(len(train_dataset_y) * split)]
    train_data_y = train_data_y.reshape(
        1, train_data_y.shape[0], train_data_y.shape[1])
    train_dataset_part.append(train_data_y)
    val_data_y = train_dataset_y[int(len(dataset) * split):]
    val_data_y = val_data_y.reshape(val_data_y.shape[0], val_data_y.shape[1])
    val_dataset_part.append(val_data_y)
    train_dataset_part = tf.data.Dataset.from_tensor_slices(
        tuple(train_dataset_part))
    val_dataset_part = tf.data.Dataset.from_tensor_slices(
        tuple(val_dataset_part))
    return train_dataset_part, val_dataset_part


class AI:
    """
    Function

        All methods needed to train the AI and predict with the AI

    Methods

        reset_predictions
        __load_saved_model
        __load_model_train
        prepare_model
        train
        forecast

    Attributes

        models : dict
            dict with all needed model
            s
        untrained_models : dict of bool
            dictionary to keep track of which model is already trained

        forecast_mode : bool
            selection if forecast mode is on

        predictions : dict of dataframes
            recent predictions
    """

    def __init__(self, forecast_mode, path, part):
        """
        Attributes

            models : dict
                dict with all needed

            untrained_models : dict of bool
                dictionary to keep track of which model is already trained

            forecast_mode : bool
                selection if forecast mode is on

            predictions : dict of dataframes
                recent predictions

        """
        self.path_pre = path
        self.model = None
        self.untrained_model = None
        self.forecast_mode = forecast_mode
        self.prepare_model(part)
        self.train_vars = train_var()
        self.scaler = Scaler(path, name=part, forecasting_mode=True)

    def create_dataset(self, data, target_column, time_now, forecasting_mode, part, opt_args=None):
        """
        Function

            creates train test forecasting data for every part and trains/loads scaler


        Parameter

            data : pd.DataFrame
                measurements from Mini-Grid for forecasting part (Load or PV)

            target_columns : str
                target column to use as target for training / forecasting

            time_now : str / pd.datetime
                datetime at which prediction is done

            forecasting_mode : bool
                Mode in which programm is currently running (True: No Training Step)

            part : str
                part which is under investigation (lf_short, lf_long, pv_short or pv_long)

            opt_args : None / Any
                optional arguments to be used

        Return

            created_data : dict
                created data to use for training and forecasting and also some optional data
        """
        if part == 'pv_short':
            created_data, self.scaler = function_dataset_short_term_pv_forecast(data, target_column, time_now,
                                                                                self.scaler,
                                                                                forecast_mode=forecasting_mode,
                                                                                split=0.9)
        elif part == 'pv_long':
            created_data, self.scaler = function_dataset_long_term_pv_forecast(data, target_column, time_now,
                                                                               self.scaler,
                                                                               forecast_mode=forecasting_mode,
                                                                               split=0.9, opt_args=opt_args)
        elif part == 'lf_short':
            created_data, self.scaler = function_dataset_short_term_load_forecast(data, target_column, time_now,
                                                                                  self.scaler,
                                                                                  forecast_mode=forecasting_mode,
                                                                                  split=0.9,
                                                                                  opt_args=opt_args)
        elif part == 'lf_long':
            created_data, self.scaler = function_dataset_long_term_load_forecast(data, target_column, time_now,
                                                                                 self.scaler,
                                                                                 forecast_mode=forecasting_mode,
                                                                                 split=0.9,
                                                                                 opt_args=opt_args)

        return created_data

    def reset_predictions(self):
        """
        Function

            sets predictions to None

        Parameter

            None

        Return

            None
        """
        self.predictions = {'pv_short': None,
                            'pv_long': None, 'lf_short': None, 'lf_long': None}

    def __load_saved_model(self, part):
        """
        Function

            loads pretrained and saved models

        Parameter

            part : str
                Which model to load: lf_long, lf_short, pv_long or pv_short

        Return

            keras.model
        """
        try:
            saved_model_path = f'{self.path_pre}/resources/02_models/' + \
                part + '/ml_model/'
            return tf.keras.models.load_model(saved_model_path)
        except Exception as e:
            raise e

    def __load_model_train(self, part):
        """
        Function

            compiles models

        Parameter

            part : str
                Which model to load: lf_long, lf_short, pv_long or pv_short

        Return

            keras.model
        """

        try:
            return self.__load_saved_model(part)
        except Exception as e:
            self.untrained_model = True
            if part == 'pv_short':
                return model_short_term_pv_forecasting(5)
            elif part == 'pv_long':
                return model_long_term_pv_forecasting()
            elif part == 'lf_short':
                return model_short_term_load_forecasting(192, 192)
            elif part == 'lf_long':
                return model_long_term_load_forecasting()

    def prepare_model(self, part):
        """
        Function

            laods model or compiles it

        Parameter

            None

        Return

            stored in class

        """

        if self.forecast_mode is True:
            try:
                self.model = self.__load_saved_model(part)
            except Exception as e:
                if part == 'pv_short':
                    self.model = model_short_term_pv_forecasting(5)
                elif part == 'pv_long':
                    self.model = model_long_term_pv_forecasting()
                elif part == 'lf_short':
                    self.model = model_short_term_load_forecasting(192, 192)
                elif part == 'lf_long':
                    self.model = model_long_term_load_forecasting()
        else:
            self.model = self.__load_model_train(part)

    def train(self, recommendation, part, data, time_now):
        """
        Function

            Train the AI

        Parameter

            recommendation : dict
                recommendation if retrain or new model

            part : str
                Which model to load: lf_long, lf_short, pv_long or pv_short

            data : dict with tensors
                train val data

        Return

            None

        """
        train_var_part = self.train_vars.get(part)
        saved_model_path = f'{self.path_pre}/resources/02_models/' + \
            part + '/ml_model/'
        if recommendation.get('reset') is True:
            model = self.__load_model_train(part)
            training_needed = True
        else:
            model = self.model
            training_needed = recommendation.get('retrain')
        if training_needed is True or self.untrained_model is True:
            earlystopping = tf.keras.callbacks.EarlyStopping(monitor='val_loss', restore_best_weights=True, mode='auto',
                                                             patience=20, min_delta=0.001)  # setup the callback
            reducelr = tf.keras.callbacks.ReduceLROnPlateau(monitor='val_loss', factor=0.5, patience=5, verbose=0,
                                                            min_delta=0.001, cooldown=1)
            callback = [earlystopping, reducelr]
            loss_fn = tf.keras.losses.MeanSquaredError()
            optimizer = tf.keras.optimizers.Adam(
                learning_rate=train_var_part.get('lr'))
            train_acc_metric = tf.keras.metrics.MeanSquaredError()
            val_acc_metric = tf.keras.metrics.MeanSquaredError()
            K.set_value(model.optimizer.learning_rate,
                        train_var_part.get('lr'))
            train_dataset_part, val_dataset_part = data.get(
                'train'), data.get('val')
            if len(train_dataset_part) != 0:
                train_dataset_part = train_dataset_part.batch(
                    train_var_part.get('bs'))
                val_dataset_part = val_dataset_part.batch(
                    len(val_dataset_part))
                # model.fit(train_dataset_part[0], train_dataset_part[1] , epochs=20, batch_size=3, validation_split=0.1)
                model, train_loss, test_loss = train_multi_input(model, train_dataset_part, val_dataset_part,
                                                                 train_acc_metric=train_acc_metric,
                                                                 optimizer=optimizer, loss_fn=loss_fn,
                                                                 val_acc_metric=val_acc_metric, patience=50, epochs=500)
                train_test_loss_graph(
                    train_loss, test_loss, self.path_pre, part, time_now)
            model.save(saved_model_path)
            tf.keras.backend.clear_session()
            self.model = model
            self.untrained_model = False

    def forecast(self, part, data):
        """
        Function

            Forecast with AI Model

        Parameter

            part : str
                Which model to load: lf_long, lf_short, pv_long or pv_short

            data : dict with tensors
                train, val, forecast data

            scaler : class scaler
                scaler trained with part data

        Return

            prediction : pd.DataFrame
                AI prediction

        """
        model = self.model
        prediction = model.predict(data.get('forecast'))
        return self.scaler.inverse_transform_prediction(prediction)
