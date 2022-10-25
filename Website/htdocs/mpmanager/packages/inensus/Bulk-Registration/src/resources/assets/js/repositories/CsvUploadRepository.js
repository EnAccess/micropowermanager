const resource = '/api/bulk-register/import-csv'
import Client from './Client/AxiosClient'

export default {
    post (csvData) {
        return Client.post(`${resource}`, csvData)
    },
}
