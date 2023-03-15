import socket
import logging
import json
import os
from functions.Configuration.configuration_class import Configuration

module_logger = logging.getLogger('Forecasting-Tool.socket_client')


class SocketClient:

    def __init__(self):

        self.host = Configuration.getConfigValue('socket_connection', 'host')
        self.port = int(Configuration.getConfigValue(
            'socket_connection', 'port'))
        self.timeout = int(Configuration.getConfigValue(
            'socket_connection', 'timeout'))

    def send_data(self, data, format='JSON'):
        if format == 'JSON':
            formatted_data = json.dumps(data)
        # elif format == 'XML':
        #    pass
        else:
            raise ValueError('unknown format')
        try:
            module_logger.info('Connecting to socket server')
            socket_client = self._connect()
        except Exception as exception:
            module_logger.error('Socket connection failed ' + str(exception))
            return
        try:
            module_logger.info('Sending data to socket server')
            socket_client.sendall(bytes(formatted_data, encoding="utf-8"))
        except Exception as exception:
            module_logger.error("error while sending data", str(exception))

        module_logger.info('Closing socket connection')
        self._close_connection(socket_client)

    def _init_socket(self):
        socket_client = socket.socket()
        socket_client.settimeout(self.timeout)
        return socket_client

    def _connect(self):
        try:
            socket_client = self._init_socket()
        except Exception as exception:
            logging.ERROR(
                'Problem by initializing the socket  ' + str(exception))
            raise Exception('Socket init failed')
        socket_client.connect((self.host, self.port))
        return socket_client

    def _close_connection(self, socket_client):
        try:
            socket_client.close()
        except Exception as exception:
            logging.ERROR(
                'Problem by closing the socket connection ' + str(exception))
