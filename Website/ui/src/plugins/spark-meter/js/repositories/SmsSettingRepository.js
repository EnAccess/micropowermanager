import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/spark-meters/sm-setting/sms-setting`


import Client from './Client/AxiosClient'

export default {
    update (smsListPM) {
        return Client.put(`${resource}`, smsListPM)
    },

}