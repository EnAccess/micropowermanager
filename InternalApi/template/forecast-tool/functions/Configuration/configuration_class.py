import configparser
from pathlib import Path


class Configuration(object):
    CONFIG_FILE_PATH = "Config.txt"

    def checkConfigFile():
        """Check whether the model file is accessible
        """
        file = Path(Configuration.getConfigFilePath())
        return file.is_file()

    def getConfigFilePath():
        return Configuration.CONFIG_FILE_PATH

    def setConfigFilePath(filePath):
        Configuration.CONFIG_FILE_PATH = filePath
        if not Configuration.checkConfigFile():
            raise FileNotFoundError('okay')
        return

    def getConfigValue(sectionName, key):
        file = Path(Configuration.getConfigFilePath())
        if not file.is_file():
            raise FileNotFoundError("Config file was not found.")
        parser = configparser.ConfigParser()
        parser.read(Configuration.getConfigFilePath())
        result = parser[sectionName][key]
        del parser
        return result

    def setConfigValue(sectionName, key, value):
        file = Path(Configuration.getConfigFilePath())
        if not file.is_file():
            raise FileNotFoundError("Config file was not found.")
        parser = configparser.ConfigParser()
        parser.read(Configuration.getConfigFilePath())
        parser.set(sectionName, key, str(value))
        with open('Config.txt', 'w') as f:
            print(f)
            parser.write(f)
        print(Configuration.getConfigValue(sectionName, key))
        del parser

    def getConfigValue_cache(sectionName, key):
        file = Path('Cache.txt')
        if not file.is_file():
            raise FileNotFoundError("Config file was not found.")
        parser = configparser.ConfigParser()
        parser.read(file)
        result = parser[sectionName][key]
        del parser
        return result

    def setConfigValue_cache(sectionName, key, value):
        file = Path('Cache.txt')
        if not file.is_file():
            raise FileNotFoundError("Config file was not found.")
        parser = configparser.ConfigParser()
        parser.read(file)
        parser.set(sectionName, key, str(value))
        with open('Cache.txt', 'w') as f:
            parser.write(f)
        del parser
