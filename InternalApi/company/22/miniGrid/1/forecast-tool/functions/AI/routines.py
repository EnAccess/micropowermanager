import gc
import os
import time

import matplotlib.pyplot as plt
import numpy as np
import tensorflow as tf
import tensorflow.keras.backend as K


def predict(model, forecast_x):
    """
    Function

        Predicts future values

    Parameter

        model : keras.model
            trained ml model

        forecast_x : tensor
            features needed for forecast

    Return

        prediction : np.array
            prediction of future values

    """
    prediction = model.predict_on_batch([tf.convert_to_tensor(forecast_x, dtype=tf.float64)])
    _ = gc.collect()
    return prediction


@tf.function
def train_step(model, x_batch_train, y_batch_train, optimizer, loss_fn, train_acc_metric):
    """
    Function

        Training on one batch

    Parameter

        model : keras.model
            ai model for forecasting

        x_batch_train : tensor.batch
            batched tensor containing training feature data

        y_batch_train : tensor.batch
            batched tensor containing training measurement data

        optimizer : keras.optimizer
            Optimizer used for training

        loss_fn : keras.loss_function
            loss function to calculate accuracy
        train_acc_metric : keras.loss_function

            accuracy metric

    Return

        loss_value : keras.loss
            loss value
    """
    with tf.GradientTape() as tape:
        logits = model(x_batch_train, training=True)  # Logits for this minibatch
        loss_value = loss_fn(y_batch_train, logits)
    grads = tape.gradient(loss_value, model.trainable_weights)
    optimizer.apply_gradients(zip(grads, model.trainable_weights))
    train_acc_metric.update_state(y_batch_train, logits)
    _ = gc.collect()
    return loss_value


@tf.function
def test_step(model, x_batch_val, y_batch_val, val_acc_metric):
    """
    Function

        Testing on one batch


    Parameter

        model : keras.model
            ai model for forecasting

        x_batch_val : tensor.batch
            batched tensor containing training feature data

        y_batch_val : tensor.batch
            batched tensor containing training measurement data

        val_acc_metric : keras.loss_function
            accuracy metric

    Return

        None
    """
    val_logits = model((x_batch_val), training=False)
    val_acc_metric.update_state(y_batch_val, val_logits)
    val_acc = val_acc_metric.result()
    _ = gc.collect()


def train_test_loss_graph(train_loss, test_loss, path_pre, part, time_now):
    """

    Function

        draws train test loss picture

    Parameter

        train_loss : list
            list of train_losses

        test_loss
            list of test_losses

        path_pre : str
            path to folder to save to

        part : str
            which algorithm is trained (e.g. PV-Long)

        time_now : pd.datetime
            current time

    Return

        None / Written to file

    """
    path_folder = f'{path_pre}/resources/02_models/{part}/training_results/'
    os.makedirs(path_folder, exist_ok=True)
    full_path = f'{path_pre}/resources/02_models/{part}/training_results/{str(time_now).replace(":", "_")}.png'
    train_loss = np.array(train_loss)
    test_loss = np.array(test_loss)
    plt.plot(train_loss)
    plt.plot(test_loss)
    plt.savefig(full_path)


def train_multi_input(model, train_dataset, val_dataset, train_acc_metric, optimizer, loss_fn, val_acc_metric, patience,
                      epochs=1):
    """
    Function

        Training routine for AI models

    Parameter

        model : keras.model
            AI - Model to train

        train_dataset : dict
            Data to train Algorithm with

        val_dataset : dict
            Data to evaluate Algorithm with

        train_acc_metric : tf.keras.Metric
            Metric applied to quantify training

        val_acc_metric : tf.keras.Metric
            Metric applied to quantify validation

        optimizer : tf.keras.Optimizer
            Optimizer used to train algorithm

        loss_fn : tf.keras.Loss
            loss function used by optimizer for training

        patience : int
            Epochs to wait before stopping training

        epochs : int
            Epochs to train Network at maximum


    Return

        trained model

    """
    best_loss = None
    wait = 0
    best_val_acc = None
    # for cb in callback:
    #    cb.on_train_begin()
    l = 0
    train_loss = []
    test_loss = []
    for epoch in range(epochs):
        start_time = time.time()

        # Iterate over the batches of the dataset.
        for train_data in enumerate(train_dataset):
            step = train_data[0]
            y_batch_train = train_data[1][-1]
            x_batch_train = train_data[1][:-1]
            loss_value = train_step(model, x_batch_train, y_batch_train, optimizer, loss_fn, train_acc_metric)

        # Display metrics at the end of each epoch.
        train_acc = train_acc_metric.result()

        # Reset training metrics at the end of each epoch
        train_acc_metric.reset_states()

        # Run a validation loop at the end of each epoch.
        for val_data in enumerate(val_dataset):
            step = val_data[0]
            y_batch_val = val_data[1][-1]
            x_batch_val = val_data[1][:-1]
            test_step(model, x_batch_val, y_batch_val, val_acc_metric)

        val_acc = val_acc_metric.result()
        train_loss.append(float(train_acc))
        test_loss.append(float(val_acc))

        val_acc_metric.reset_states()
        if best_loss is None:
            best_loss = val_acc
            best_weights = model.get_weights()
            wait = 0
            best_val_acc = [0, val_acc]
        elif best_loss > val_acc:
            best_weights = model.get_weights()
            if val_acc >= best_loss - 0.00005:
                wait += 1
            else:
                wait = 0
            best_val_acc = [epoch, val_acc]
            best_loss = val_acc
        else:
            wait += 1
            # print(wait)
            if wait == 20 or wait == 40 or wait == 45:
                print('New LR: ', K.eval(model.optimizer.lr) * 0.2)
                # print(wait)
                K.set_value(model.optimizer.learning_rate, K.eval(model.optimizer.lr) * 0.2)
            if wait >= patience:
                model.set_weights(best_weights)
                break
        print(
            f"Epoch {epoch}: training loss: {float(train_acc)}, validation loss: {float(val_acc)}, time elapsed: {time.time() - start_time}, wait: {wait}")
    model.set_weights(best_weights)
    tf.keras.backend.clear_session()
    _ = gc.collect()

    return model, train_loss, test_loss
