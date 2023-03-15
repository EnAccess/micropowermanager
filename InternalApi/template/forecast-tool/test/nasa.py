import requests
import json
import pandas as pd
from datetime import datetime


def test_1(response):
    a = datetime.now()
    weather_data_raw = response.text
    parsed = pd.DataFrame.from_dict(
        json.loads(weather_data_raw), orient='columns')
    parsed_data = pd.DataFrame.from_dict(
        parsed['data'].tolist(), orient='columns')
    b = datetime.now()
    print(b-a)


def test_2(response):
    i = 0
    x = datetime.now()
    parsed_data = json.loads(response.text)
    data_from_parsed = parsed_data['data']
    data_frame = pd.DataFrame()
    for element in data_from_parsed:
        a = pd.DataFrame.from_dict(element, orient='index', columns=[i])
        data_frame = pd.concat([data_frame, a.T])
    b = datetime.now()
    print(b-x)


response = requests.get(
    'https://volt-terra-mpm.ga/storage/api/mini-grids/1/solar-readings?weather=1&end_date=2021-05-21 09:51:11&per_page=1000&page=60')
test_1(response)
test_2(response)
print(1)
