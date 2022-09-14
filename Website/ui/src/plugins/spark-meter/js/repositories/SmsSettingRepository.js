import { baseUrl } from '../../../../repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/spark-meters/sm-setting/sms-setting`

import Client from '../../../../repositories/Client/AxiosClient'

export default {
    update (smsListPM) {
        return Client.put(`${resource}`, smsListPM)
    },

}