import { baseUrl } from '../../../../repositories/Client/AxiosClient'
const resource = `${baseUrl}/api/swifta-payment/authentication`

import Client from '@/repositories/Client/AxiosClient'

export default {
    get() {
        return Client.get(`${resource}`)
    },
}
