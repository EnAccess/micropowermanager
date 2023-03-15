import configparser

class Configuration:

    def __init__(self, config_path):
        #read config file and assign to class level var
        self.config = configparser.ConfigParser()
        self.config.read(config_path)
        

    def get_section(self, section_name):
        if section_name in self.config:
            return self.config[section_name]
        raise Exception("section {} not found in config file ".format(section_name))
        

    def field_from_section(self, field, section_name):
        section = self.get_section(section_name)
        if field in section:
            return section[field]
        raise Exception("field {} not found in section {}".format(field, section_name))