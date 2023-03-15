from astral import LocationInfo

from functions.data.data_manager import WeatherDataManager
import logging

module_logger = logging.getLogger('Forecasting-Tool.weather_data')

class Weather:
    """
    Function

        Weather class combines all functions needed to store, update and preprocess weather data from
        OWM and NASA. This class is a front end to the various underlying function.

    Principle

        Methods are mostly loosely connected and can be used interchangeably. Exception to this is
        preprocess_data which relies on the result of get_historical_data_from_server.

        Most methods are downloading data from servers and pre-processing them according to their
        special needs. All needed methods are also implemented in WeatherDataManager which also stores
        and loads all data needed for operation.


    Attributes

        dm : class
            WeatherDataManager which has all data for weather (NASA, OWM) stored and different
            methods to process

        dataset_short_term_forecast : pd.DataFrame
            collection of all weather data for short-term-prediction

        dataset_long_term_forecast : pd.DataFrame
            collection of Nasa weather data

    Methods

        __init_data-manager()
            initializes WeatherDataManager-instance and reads in already saved data

        get_historic_data_from_server()
            gets new weather data stored on a server

        preprocess_data(url_list)
            shows missing values and saves data (missing values are not corrected)

        get_forecast_data_from_server(url_list)
            retrieves OWM forecasts from server

        get_data_from_nasa(time_now)
            gets all data from NASA server up to current timestep

        set_up_sun_times()
            getting information when sun rises and sets
    """

    def __init__(self, current_path):
        """
        Attributes

            dm : class
                WeatherDataManager which has all data for weather (NASA, OWM) stored and different
                methods to process

            dataset_short_term_forecast : pd.DataFrame
                collection of all weather data for short-term-prediction

            dataset_long_term_forecast : pd.DataFrame
                collection of Nasa weather data
        """
        self.current_path = current_path
        self.dm = WeatherDataManager('Weather Data')
        self.dataset_short_term_forecast = None
        self.dataset_long_term_forecast = None
        self.load_all_data()

    def load_all_data(self):
        """
        Function

            Initializes needed functions and data

        Returns

            No returns needed as data is stored in WeatherDataManager-class itself

        """
        self.__init_data_manager(self.current_path)

    def __init_data_manager(self, current_path):
        """
        Function

            Initializes data manager and reads in weather data saved locally

        Returns

            No returns needed as data is stored in WeatherDataManager-class itself
        """
        # read values from config
        self.dm.read_config_from_file(current_path)
        # add update manager and load data from config
        self.dm.allow_updates(from_config=True, config_section='dataset_historical_weather')
        # load cached weather data
        self.dm.load_cached_historical_data()
        self.dm.load_cached_weather_forecast()
        self.dm.load_cached_long_term_weather()

    def get_historic_data_from_server(self, url_list):
        """
        Function

            Collect historical weather data from server, standardizes it and adds it to already cached data

        Parameters

            url_list: list
                list of urls to get OWM historical data from

        Returns

            No returns needed as data is stored in WeatherDataManager-class itself
        """
        module_logger.info('Requesting historical weather data.')
        # get data
        self.dm.update_data(url_list)
        # standardize them by putting in DataFrame and make timestamp readable
        self.dm.standardize_new_data()
        # add data to already cached data
        self.dm.add_data()
        # resample data to raw frequency
        self.dm.resample_data_to_raw_frequency()
        module_logger.info('Historical weather data requested and processed.')

    def preprocess_data(self):
        """
        Function

            Preprocess historical weather data

        Returns

            No returns needed as data is stored in WeatherDataManager-class itself
        """
        # look for missing values and log them
        self.dm.show_missing_values()
        # save all data
        self.dm.save_cached_data()

    def get_forecast_data_from_server(self, url_list):
        """
        Function

            Get weather forecast of OWM from server provided currently by INENSUS

        Parameters

            url_list: list
                list of urls for OWM weather prediction

        Returns

            No returns needed as data is stored in WeatherDataManager-class itself
        """
        module_logger.info('Requesting forecast weather data.')
        # checks if a link to new forecast is available
        if not url_list.empty:
            # get only last link (all prior data does not matter)
            forecast_weather_data_link = url_list.iloc[-1,].get('forecast_weather_data')
            latest_forecast_data_link = [forecast_weather_data_link]
            # get forecast data
            self.dm.load_weather_forecast(latest_forecast_data_link)
            module_logger.info('Historical weather data requested and processed.')

    def get_data_from_nasa(self, time_now):
        """
        Function

            get long term NASA weather data

        Parameters

            time_now : pd.datetime
                current timestamp format Y-Month-Day H:M

        Returns

            No returns needed as data is stored in WeatherDataManager-class itself
        """
        self.dm.get_and_process_long_term_weather_data(time_now)

    def set_up_sun_times(self):
        """
        Function

            get sunset and sunrise infomration

        Returns

            No returns needed as data is stored in WeatherDataManager-class itself
        """
        sun_times = LocationInfo(name='MG', region='Africa', timezone=self.dm.timezone_server, latitude=self.dm.lat,
                                 longitude=self.dm.lon)
        return sun_times
