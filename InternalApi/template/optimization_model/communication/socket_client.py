import socket
import logging
import json
import os
from logger.logger import Logger


class SocketClient:

    def __init__(self):
        self.host = '127.0.0.1'
        self.port = 9968
        self.timeout = 10

    def send_data(self, data, format='JSON'):
        if format == 'JSON':
            formatted_data = json.dumps(data)
        # elif format == 'XML':
        #    pass
        else:
            raise ValueError('unknown format')
        try:
            socket_client = self._connect()
        except Exception as exception:
            Logger.error('Socket connection failed ' + str(exception))
            return
        try:
            socket_client.sendall(bytes(formatted_data, encoding="utf-8"))
        except Exception as exception:
            Logger.error("error while sending data", str(exception))

        self._close_connection(socket_client)

    def _init_socket(self):
        socket_client = socket.socket()
        socket_client.settimeout(self.timeout)
        return socket_client

    def _connect(self):
        try:
            socket_client = self._init_socket()
        except Exception as exception:
            Logger.error(
                'Problem by initializing the socket  ' + str(exception))
            raise Exception('Socket init failed')
        socket_client.connect((self.host, self.port))
        return socket_client

    def _close_connection(self, socket_client):
        try:
            socket_client.close()
        except Exception as exception:
            Logger.error(
                'Problem by closing the socket connection ' + str(exception))
