import { baseUrl } from '../../../../repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/micro-star-meters/micro-star-cert`

import Client from '../../../../repositories/Client/AxiosClient'

export default {

    post (cert) {
        return Client.post(`${resource}`, cert)
    },
    get () {
        return Client.get(`${resource}`)
    },

}
