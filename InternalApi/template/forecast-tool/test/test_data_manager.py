import unittest
from functions.data.data_manager import DataManager


class load_data(unittest.TestCase):
    def test_data_loading(self):
        dm = DataManager()
        dm.read_data('load_measurements.csv')
        data = [dm.data.data.columns[0], dm.data.data.columns[1]]
        unittest.TestCase.assertCountEqual(self, first = data, second=['load','read_out'])



if __name__ == '__main__':
    unittest.main()
