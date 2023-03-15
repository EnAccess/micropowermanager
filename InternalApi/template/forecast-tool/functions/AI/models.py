import tensorflow as tf


def TransformerEncoderLayer(input=None, input_shape=(None, 25), nhead=6, key_dim=1, dim_feedforward=2048, dropout=0.3,
                            activation_feedforward='relu', normalize=False):
    """
    Function

        Combines all layers needed to form one layer of the TransformerEncoder. One of these layers
        consists of:

        Input -> MultiHeadAttention -> Dropout-> Normalization (optional) -> Fully-Connected Dense
         -> Dropout -> Normalization (optional)

    Parameter

        input : tensorflow layer output
            Input to layer

        input_shape : tuple
            Shape of Input (Usually None, Features)

        nhead : int
            number of heads of MultiHeadAttention layer

        key_dim : int
            Dimension of data

        dim_feedforward : int
            Number of Neurons of Dense Layer

        dropout : float
            fraction of dropout

        activation_feedforward : str
            Keras activation function for dense layer

        normalize : bool
            Use normalization inside layer

    Return

        input_encoder : tensorflow layer input
            Input to layer

        concat_fully_mha : tensorflow layer output
            Output of layer


    """
    # encoder
    if input is None:
        input_encoder = tf.keras.layers.Input(shape=input_shape)
    else:
        input_encoder = input
    self_mha = tf.keras.layers.MultiHeadAttention(num_heads=nhead, key_dim=key_dim)
    fully_connected = tf.keras.layers.Dense(dim_feedforward, activation=activation_feedforward)
    dropout_mha = tf.keras.layers.Dropout(dropout)
    dropout_fc = tf.keras.layers.Dropout(dropout)
    normLayer_1 = tf.keras.layers.LayerNormalization()
    normLayer_2 = tf.keras.layers.LayerNormalization()

    self_mha_out = self_mha(input_encoder, input_encoder)
    dropout_mha = dropout_mha(self_mha_out)
    concat_mha_in = tf.keras.layers.Concatenate(-1)([dropout_mha, input_encoder])
    if normalize is True:
        concat_mha_in = normLayer_1(concat_mha_in)

    fully_out = fully_connected(concat_mha_in)
    dropout_fc_out = dropout_fc(fully_out)
    concat_fully_mha = tf.keras.layers.Concatenate(-1)([dropout_fc_out, concat_mha_in])
    if normalize is True:
        concat_fully_mha = normLayer_2(concat_fully_mha)
    return input_encoder, concat_fully_mha


def TransformerDecoderLayer(encoder_output, input_decoder=None, decoder_input_shape=(None, None, 25), nhead=6,
                            key_dim=1, dim_feedforward=2048, dropout=0.3, activation_feedforward='relu',
                            normalize=False):
    """
        Function

            Combines all layers needed to form one layer of the TransformerDecoder. One of these layers
            consists of:

            Output of encoder -> MultiHeadAttention -> Dropout-> Concatenation with decoder input ->
            Normalization (optional) ->  MultiHeadAttention -> Dropout-> Normalization (optional) ->
            Fully-Connected Dense-> Dropout -> Normalization (optional)


        Parameter

            input : tensorflow layer output
                Input to layer

            input_shape : tuple
                Shape of Input (Usually None, Features)

            nhead : int
                number of heads of MultiHeadAttention layer

            key_dim : int
                Dimension of data

            dim_feedforward : int
                Number of Neurons of Dense Layer

            dropout : float
                fraction of dropout

            activation_feedforward : str
                Keras activation function for dense layer

            normalize : bool
                Use normalization inside layer

        Return

            input_encoder : tensorflow layer input
                Input to layer

            concat_fully_mha : tensorflow layer output
                Output of layer


        """
    # decoder
    if input_decoder is None:
        input_decoder = tf.keras.layers.Input(shape=decoder_input_shape)
    input_encoder = encoder_output
    self_mha = tf.keras.layers.MultiHeadAttention(num_heads=nhead, key_dim=key_dim)
    mha = tf.keras.layers.MultiHeadAttention(num_heads=nhead, key_dim=key_dim)
    fully_connected = tf.keras.layers.Dense(dim_feedforward, activation=activation_feedforward)
    dropout_mha = tf.keras.layers.Dropout(dropout)
    dropout_fc = tf.keras.layers.Dropout(dropout)
    dropout_concat = tf.keras.layers.Dropout(dropout)
    normLayer_1 = tf.keras.layers.LayerNormalization()
    normLayer_2 = tf.keras.layers.LayerNormalization()
    normLayer_3 = tf.keras.layers.LayerNormalization()

    # first attention
    self_mha_out = self_mha(input_decoder, input_decoder)
    dropout_mha_out = dropout_mha(self_mha_out)
    concat_self_mha = tf.keras.layers.Concatenate()([input_decoder, dropout_mha_out])
    if normalize is True:
        concat_self_mha = normLayer_1(concat_self_mha)

    # pre_concat second attention
    # pre_concat_second_out = tf.keras.layers.Concatenate()([normLayer_1_out, input_encoder])
    mha_out = mha(concat_self_mha, input_encoder)
    dropout_concat_out = dropout_concat(mha_out)
    concat_mha = tf.keras.layers.Concatenate()([dropout_concat_out, concat_self_mha])
    if normalize is True:
        concat_mha = normLayer_2(concat_mha)

    # Fully connected
    fully_connected_out = fully_connected(concat_mha)
    post_fully_concat = tf.keras.layers.Concatenate()([concat_mha, fully_connected_out])
    dropout_fc_out = dropout_fc(post_fully_concat)
    if normalize is True:
        dropout_fc_out = normLayer_3(dropout_fc_out)

    return input_decoder, dropout_fc_out


def model_short_term_pv_forecasting(number_of_features):
    """
    Function

        Model for short term pv forecasting

    Parameter

        number_of_features : int
            number of features

    Return

        model : tf.keras.model
            AI model for short term pv forecasting
    """

    input = tf.keras.Input(shape=(1, number_of_features))
    dense_1_out = tf.keras.layers.LSTM(11, activation='linear', return_sequences=False)(input)
    dropout_1 = tf.keras.layers.Dropout(0.289930744868913)(dense_1_out)
    dense_2_out = tf.keras.layers.Dense(22, activation='tanh')(dropout_1)
    dropout_2 = tf.keras.layers.Dropout(0.172155949131269)(dense_2_out)
    dense_3_out = tf.keras.layers.Dense(24, activation='relu')(dropout_2)
    dropout_3 = tf.keras.layers.Dropout(0.0523870149946583)(dense_3_out)
    dense_4_out = tf.keras.layers.Dense(33, activation='sigmoid')(dropout_3)
    dropout_4 = tf.keras.layers.Dropout(0.152156592272035)(dense_4_out)
    out = tf.keras.layers.Dense(1, activation='relu')(dropout_4)
    optimizer = tf.keras.optimizers.Adam(0.001)
    model = tf.keras.Model([input], [out])
    model.compile(loss='mean_squared_error', metrics=['mean_absolute_error'], optimizer=optimizer)
    model.summary()
    return model


def model_long_term_pv_forecasting():
    """
    Function

        Model for long term pv forecasting

    Parameter

        None

    Return

        model : keras.model
            AI model for long term pv forecasting
    """
    learning_rate = 0.001
    optimiser = tf.keras.optimizers.Adam(
        lr=learning_rate)  # Other possible optimiser "sgd" (Stochastic Gradient Descent)
    input_shape_2 = (None, 87)
    input_lstm_2 = tf.keras.layers.Input(shape=input_shape_2, name='lstm_input_2')
    cnn = tf.keras.layers.LSTM(32, activation='linear', return_sequences=True)(input_lstm_2)
    cnn_1 = tf.keras.layers.LSTM(16, activation='linear', return_sequences=True)(cnn)

    dense_1 = tf.keras.layers.LSTM(64, activation='relu', return_sequences=True)(cnn_1)
    dense_output = tf.keras.layers.Dense(1, activation='relu', name='output')(dense_1)
    model = tf.keras.Model([input_lstm_2], [dense_output])
    model.compile(loss='mean_squared_error', metrics=['mean_absolute_error'], optimizer=optimiser)
    return model


def model_short_term_load_forecasting(historical_timesteps, forecast_timesteps):
    """
    Function

        Model for sort term load forecasting

    Parameter

        historical_timesteps : int
            length of historical

        forecast_timestep : int
            length of prediction sequence

    Return

        model : keras.model
            AI model for short term load forecasting
    """

    input_shape = (historical_timesteps, 1)
    input_cnn = tf.keras.layers.Input(shape=input_shape, name='CNN_input')

    # Convolution_1
    conv_l1 = tf.keras.layers.Conv1D(32, 1, activation='relu', padding='same')
    pooling_l1 = tf.keras.layers.MaxPooling1D(2, strides=2, padding='same')
    # batch_norm_l1_cnn = tf.keras.layers.BatchNormalization()

    # Convolution_2
    conv_l2 = tf.keras.layers.Conv1D(32, 7, activation='relu', padding='same')
    pooling_l2 = tf.keras.layers.MaxPooling1D(2, strides=2, padding='same')
    # batch_norm_l2_cnn = tf.keras.layers.BatchNormalization()

    # convolution_3
    conv_l3 = tf.keras.layers.Conv1D(32, 3, activation='relu', padding='same')
    pooling_l3 = tf.keras.layers.MaxPooling1D(2, strides=2, padding='same')
    # batch_norm_l3_cnn = tf.keras.layers.BatchNormalization()

    # build cnn network
    conv_l1_out = conv_l1(input_cnn)
    pooling_l1_out = pooling_l1(conv_l1_out)
    # batch_norm_l1_cnn_out = batch_norm_l1_cnn(pooling_l1_out)

    conv_l2_out = conv_l2(input_cnn)
    pooling_l2_out = pooling_l2(conv_l2_out)
    # batch_norm_l2_cnn_out = batch_norm_l2_cnn(pooling_l2_out)

    conv_l3_out = conv_l3(input_cnn)
    pooling_l3_out = pooling_l3(conv_l3_out)
    # batch_norm_l3_cnn_out = batch_norm_l3_cnn(pooling_l3_out)

    concat_out = tf.keras.layers.Concatenate(axis=-1)(
        [pooling_l1_out, pooling_l2_out, pooling_l3_out])

    # CNN/RNN
    lstm_1 = tf.keras.layers.LSTM(32, activation='tanh')

    lstm_1_out = lstm_1(concat_out)
    hist_dropout = tf.keras.layers.Dropout(0.2)(lstm_1_out)

    # Dense
    dense_concat_network_1 = tf.keras.layers.Dense(32, activation='relu')
    dense_concat_network_2 = tf.keras.layers.Dense(64, activation='relu')
    dense_output = tf.keras.layers.Dense(forecast_timesteps, activation=None, name='output')

    # dense connection
    dense_output_concat = dense_concat_network_1(hist_dropout)
    dense_concat_network_2_out = dense_concat_network_2(dense_output_concat)
    dense_output = dense_output(dense_concat_network_2_out)

    model = tf.keras.Model([input_cnn], [dense_output])
    optimizer = tf.keras.optimizers.Adam(learning_rate=0.001)
    model.compile(optimizer=optimizer, loss='mean_squared_error')
    return model


def model_long_term_load_forecasting():
    """
    Function

        Model for long term load forecasting

    Parameter

        historical_timesteps : int
            length of historical input

        forecast_timestep : int
            length of prediction sequence

    Return

        model : keras.model
            AI model for long term load forecasting
    """
    loss = "mse"
    learning_rate = 0.001
    optimiser = tf.keras.optimizers.Adam(
        lr=learning_rate)
    input_encoder, output_encoder = TransformerEncoderLayer(input_shape=(None, 25), dim_feedforward=16, nhead=5)
    _, output_encoder = TransformerEncoderLayer(input=output_encoder, dim_feedforward=16, nhead=5)
    _, output_encoder = TransformerEncoderLayer(input=output_encoder, dim_feedforward=16, nhead=5)
    input_decoder, output_decoder = TransformerDecoderLayer(output_encoder, decoder_input_shape=(None, 25),
                                                            dim_feedforward=16, nhead=5)
    _, output_decoder = TransformerDecoderLayer(output_encoder, input_decoder=output_decoder,
                                                dim_feedforward=16, nhead=5)
    _, output_decoder = TransformerDecoderLayer(output_encoder, input_decoder=output_decoder,
                                                            dim_feedforward=16, nhead=5)
    output = tf.keras.layers.Dense(24, activation=None)(output_decoder)
    model = tf.keras.models.Model(inputs=[input_encoder, input_decoder], outputs=output)
    model.compile(optimizer=optimiser, loss=loss)
    return model
