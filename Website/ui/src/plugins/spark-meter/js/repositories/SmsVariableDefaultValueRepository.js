import { baseUrl } from '../../../../repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/spark-meters/sm-setting/sms-setting/sms-variable-default-value`

import Client from '@/repositories/Client/AxiosClient'

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
