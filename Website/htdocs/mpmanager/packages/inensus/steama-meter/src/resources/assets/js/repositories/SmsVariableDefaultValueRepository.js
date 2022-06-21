const resource = '/api/steama-meters/steama-setting/sms-setting/sms-variable-default-value'
import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },

}