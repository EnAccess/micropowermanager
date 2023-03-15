import json
import logging
import os

import pandas as pd
import requests

from functions.Configuration.configuration_class import Configuration

module_logger = logging.getLogger('Forecasting-Tool.update_manager')


class UpdateManager:
    def __init__(self, url, timezone_server=None):
        self.url = url
        self.timezone_server = timezone_server

    # STANDARD FUNCTIONS

    def reformat_time(self, time, format='%Y-%m-%d %H:%M:%S'):
        """
        Function

            reformat time to URL usable time

        Parameter

            time: str or pd.datetime
                current time to check for

        Return

            converted time for URL
        """
        format = format
        if time is None:
            time = ''
        else:
            if isinstance(time, str):
                time = pd.to_datetime(time).tz_convert(self.timezone_server)
            elif isinstance(time, pd.Timestamp):
                time = time.tz_convert(self.timezone_server)
            else:
                pass
            time = str(time.strftime(format))
        return time

    def time_zone(self, time):
        if isinstance(time, str):
            time = pd.to_datetime(time)

    def build_url(self, start_time, end_time):
        """
        Function

            build url to get data from server

        Parameter

            start_time: None, str or pd.datetime

                start date for data to get

            end_time: None, str or pd.datetime
                end date for data to get

        Return

            url to be send for request
        """
        start_time = self.reformat_time(start_time)
        end_time = self.reformat_time(end_time)
        first_variable_set = False
        prefix = '?'
        if '?' in self.url:
            first_variable_set = True
            prefix = '&'
        else:
            pass
        if start_time != '':
            url_start_phrase = prefix + 'start_date=' + start_time
            first_variable_set = True
        else:
            url_start_phrase = ''
        if end_time != '':
            if first_variable_set is True:
                url_end_phrase = '&end_date=' + end_time
            else:
                url_end_phrase = '?end_date=' + end_time
        else:
            url_end_phrase = ''
        url = self.url + url_start_phrase + url_end_phrase + '&per_page=1000'
        return url

    def multipage_parse(self, full_url, first_page, last_page, section_name='data'):
        """
        Function

            parses data if multiple pages are available

        Parameter

            full_url: str
                url for data

            first_page: int
                number of first page

            last_page: int
                number of last page

            section_name: str
                section where data is stored

        Return

            parsed data
        """
        first_page = int(first_page)
        last_page = int(last_page)
        page_url = full_url
        data_frame = pd.DataFrame()
        for page in range(first_page, last_page + 1):
            try:
                # time.sleep(1)
                if '?' in full_url:
                    page_url = page_url + '&page=' + str(page)
                else:
                    page_url = page_url + '?page=' + str(page)
                response = requests.get(page_url)
                weather_data_raw = response.text
                parsed = pd.DataFrame.from_dict(
                    json.loads(weather_data_raw), orient='columns')
                parsed_data = pd.DataFrame.from_dict(
                    parsed[section_name].tolist(), orient='columns')
                data_frame = pd.concat([data_frame, parsed_data])
                page_url = full_url
            except:
                breakpoint()
        return data_frame

    def singlepage_parse(self, parsed, section_name):
        """
        Function

            parses data from single url

        Parameter

            parsed: json
                parsed data

            section_name: str
                section where data is stored

        Return

            dataframe with parsed data
        """
        data_from_parsed = parsed[section_name]
        data_frame = pd.DataFrame()
        i = 0
        for element in data_from_parsed:
            a = pd.DataFrame.from_dict(element, orient='index', columns=[i])
            data_frame = pd.concat([data_frame, a.T])
        return data_frame

    def request_data(self, start_time, current_time, section='data', return_exception_raised=False):
        """
        Function

            request data from server

        Parameter

            start_time: datetime
                last timestep available in data

            current_time: datetime
                current timestamp

            section: str
                section where data is stored

            return_exception_raised: bool
                if exception should be retured

        Return

            loaded data and exception
        """
        full_url = self.build_url(start_time, end_time=current_time)
        module_logger.info(f'Request data from: ' + full_url)
        response, exception_raised = self.__data_request(full_url)
        if exception_raised is False:
            data = response.text
            parsed = json.loads(data)
            if 'from' in parsed and 'last_page' in parsed:
                first_page = parsed['from']
                last_page = parsed['last_page']
                if first_page is None:
                    dataframe = pd.DataFrame()
                else:
                    dataframe = self.multipage_parse(
                        full_url, first_page, last_page, section)
            else:
                dataframe = self.singlepage_parse(parsed, section)
        else:
            module_logger.warning('Exception raised -> Skip')
            dataframe = pd.DataFrame()
        if return_exception_raised is False:
            return dataframe
        elif return_exception_raised is True:
            return dataframe, exception_raised
        else:
            raise Exception('get_exception has to be True or False')

    def extract_urls(self, url_list, section_name, url_prefix):
        """
        Function

            extract weather data urls

        Parameter

            url_list: list
                data to extract urls from

            section_name: str
                section where to extract data from

            url_prefix: str
                prefix to add to url

        Return

            list of extracted urls
        """
        urls = []
        for index, row in url_list.iterrows():
            if url_list.loc[index].get(section_name) is None:
                pass
            else:
                url = url_prefix + \
                    url_list.loc[index].get(section_name).get(
                        'current_weather_data')
            if url in urls:
                pass
            else:
                urls.append(url)
        return urls

    def __data_request(self, url):
        """
        Function

            Try to get data from the server. If it fails return empty DataFrame, otherwise it returns the
            data and an indicator for an exception raised. 403/404 is checked in the return because it is
            not detected by requests itself

        Parameter

            url: str
                url to download data

        Return

            retrieved data

        """
        exception_raised = False
        response = pd.DataFrame()
        try:
            response = requests.get(url)
            if response.status_code == 403:
                module_logger.warning('HTTP-Error: 403 for url: ' + url)
                exception_raised = True
            elif response.status_code == 404:
                module_logger.warning('HTTP-Error: 404 for url: ' + url)
                exception_raised = True
            elif response.status_code == 503:
                module_logger.warning(
                    'HTTP-Error:503 Service unavailable for url: ' + url)
                exception_raised = True
        except requests.exceptions.Timeout:
            module_logger.warning('Connection timed out')
            exception_raised = True
        except requests.exceptions.TooManyRedirects:
            module_logger.warning('Too many redirects')
            exception_raised = True
        except requests.exceptions.ConnectionError:
            module_logger.warning('ConnectionError for URL: ' + url)
            exception_raised = True
        return response, exception_raised

    def request_historical_weather_data_from_server(self, url_list):
        """
        Function

            get historical weather data from server

        Parameter

            url_list: list
                list of urls to get historical weather data from

        Return

            historic weather data
        """
        collected_weather_data = pd.DataFrame()
        if not url_list.empty:
            for url_partial in url_list:
                if isinstance(url_partial, dict):
                    url = self.url + url_partial.get('current_weather_data')
                    response, exception_raised = self.__data_request(url)
                    if exception_raised is False:
                        data = response.text
                        parsed = json.loads(data)
                        date = parsed.get('dt')
                        received_weather_data = pd.DataFrame()
                        weather = parsed.get('main')
                        received_weather_data.loc[date, 'temperature'] = weather.get(
                            'temp')
                        received_weather_data.loc[date, 'humidity'] = weather.get(
                            'humidity')
                        received_weather_data.loc[date, 'pressure'] = weather.get(
                            'pressure')
                        clouds = parsed.get('clouds')
                        received_weather_data.loc[date,
                                                  'cloudiness'] = clouds.get('all')
                        received_weather_data.loc[date, 'timestamp'] = date
                    else:
                        received_weather_data = pd.DataFrame()
                    if not received_weather_data.empty:
                        collected_weather_data = pd.concat(
                            [collected_weather_data, received_weather_data])
                else:
                    pass
        else:
            collected_weather_data = pd.DataFrame()
        return collected_weather_data

    def parse_forecast_data(self, parsed):
        """
        Function

            parse forecast data to dataframe

        Parameter

            parsed: json
                raw data

        Return

            forecast_data
        """
        forecast = pd.DataFrame()
        list = parsed.get('list')
        for item in list:
            date = item.get('dt')
            weather = item.get('main')
            forecast.loc[date, 'temperature'] = weather.get('temp')
            forecast.loc[date, 'humidity'] = weather.get('humidity')
            forecast.loc[date, 'pressure'] = weather.get('pressure')
            clouds = item.get('clouds')
            forecast.loc[date, 'cloudiness'] = clouds.get('all')
        forecast['Timestamp'] = forecast.index
        forecast_data = forecast[[
            'temperature', 'humidity', 'pressure', 'cloudiness', 'Timestamp']]
        return forecast_data

    def request_forecast(self, forecast_url, weather_forecast_path, weather_forecast_name):
        """
        Function

            get forecast data from server

        Parameter

            forecast_url : str
                url to download forecast

            weather_forecast_path : str
                path to save forecast

            weather_forecast_name
                name of weather forecast

        Return

            forecast data
        """
        module_logger.info('Requesting weather forecast data')
        response, exception_raised = self.__data_request(forecast_url)
        if exception_raised is False:
            data = response.text
            if not '403 Forbidden' in data:
                if data is None:
                    forecast_data = pd.DataFrame()
                else:
                    parsed = json.loads(data)
                    os.makedirs(name=weather_forecast_path, exist_ok=True)
                    with open(weather_forecast_path + weather_forecast_name, 'w') as outfile:
                        json.dump(data, fp=outfile)
                    Configuration.setConfigValue(
                        'data_weather_forecast', 'data_cache_name', weather_forecast_name)
                    forecast_data = self.parse_forecast_data(parsed)
                module_logger.info(
                    'Weather forecast data requested successfull')
            else:
                forecast_data = pd.DataFrame(columns=['Timestamp'])
                module_logger.info('Weather Forecast: got 403')
        else:
            module_logger.info('Exception raised Weather forecast: got 403')
            forecast_data = pd.DataFrame(columns=['Timestamp'])
        return forecast_data

    def request_nasa(self, lat, lon, time_now, start=None, counter=None):
        """
        Function

            get data from NASA

        Parameter

            lat: str
                latitude of site

            lon : str
                longitude of site

            time_now : pd.datetime
                current timestamp

        Return

            data and exception
        """
        if start is None:
            start = time_now - pd.to_timedelta('12Y')
        if counter is not None:
            end = time_now - \
                pd.to_timedelta(f'{counter}Y') - pd.to_timedelta('4D')
            start = time_now - pd.to_timedelta(f'{counter+1}Y')
            start_date = str(start.date()).replace('-', '')
        else:
            end = time_now - pd.to_timedelta('4D')
        start_date = str(start.date()).replace('-', '')
        end_date = str(end.date()).replace('-', '')
        if not int(start_date) > int(end_date):
            url = f'https://power.larc.nasa.gov/api/temporal/hourly/point?start={start_date}&end={end_date}&latitude={lat}&longitude={lon}&community=re&parameters=ALLSKY_SFC_SW_DWN%2CCLRSKY_SFC_SW_DWN%2CALLSKY_KT%2CALLSKY_SRF_ALB%2CSZA%2CALLSKY_SFC_PAR_TOT%2CCLRSKY_SFC_PAR_TOT%2CALLSKY_SFC_UVA%2CALLSKY_SFC_UVB%2CALLSKY_SFC_UV_INDEX%2CT2M%2CT2MDEW%2CT2MWET%2CQV2M%2CRH2M%2CPRECTOTCORR%2CPS&header=true&time-standard=utc'
            module_logger.info(
                f'Getting Data from NASA Power Service using: {url}')
            response, exception_raised = self.__data_request(url)
            if response.status_code == 403:
                module_logger.warning('HTTP-Error: 403 for url: ' + url)
                exception_raised = True
            elif response.status_code == 404:
                module_logger.warning('HTTP-Error: 404 for url: ' + url)
                exception_raised = True
            elif response.status_code == 503:
                module_logger.warning(
                    'HTTP-Error: 503 Service unavailable for url: ' + url)
                exception_raised = True
            elif response.status_code == 502:
                module_logger.warning(
                    'HTTP-Error: 502 Server returned an invalid or incomplete response: ' + url)
                exception_raised = True
            elif response.status_code == 504:
                module_logger.warning(
                    'HTTP-Error: 504 Server did not respond in time: ' + url)
                exception_raised = True
            return response, exception_raised
        return pd.DataFrame(), True
