const resource = '/api/spark-meters/sm-setting'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },

}