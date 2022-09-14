import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/steama-meters/steama-setting/sms-setting/sms-variable-default-value`

import Client from '@/repositories/Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },

}