from datetime import datetime
import os
import logging

PATH = '/home/inensus/ZIM-Docker/backend/logs/'


class Logger:

    @staticmethod
    def log(message):

        if not os.path.isdir(PATH):
            os.mkdir(PATH)

        log_file = PATH + "optimization-model-" + \
            str(datetime.today().strftime('%Y-%m-%d')) + "-log.txt"
        logging.basicConfig(filename=log_file, level=logging.WARNING,
                            format='%(levelname)s: %(asctime)s %(message)s', datefmt='%H:%M:%S ')
        logging.warning(message)

    @staticmethod
    def error(message):

        if not os.path.isdir(PATH):
            os.mkdir(PATH)

        log_file = PATH + "optimization-model-" + \
            str(datetime.today().strftime('%Y-%m-%d')) + "error-log.txt"
        logging.basicConfig(filename=log_file, level=logging.ERROR,
                            format='%(levelname)s: %(asctime)s %(message)s', datefmt='%H:%M:%S ')
        logging.error(message)
