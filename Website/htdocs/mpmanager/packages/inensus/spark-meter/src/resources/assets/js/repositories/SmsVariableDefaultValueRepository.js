const resource = '/api/spark-meters/sm-setting/sms-setting/sms-variable-default-value'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },

}