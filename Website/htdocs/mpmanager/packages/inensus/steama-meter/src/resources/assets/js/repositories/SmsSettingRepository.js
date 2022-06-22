const resource = '/api/steama-meters/steama-setting/sms-setting'
import Client from './Client/AxiosClient'

export default {
    update(smsListPM) {
        return Client.put(`${resource}`, smsListPM)
    },

}