const resource = '/api/kelin-meters/kelin-meter/daily-consumptions'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    }
}