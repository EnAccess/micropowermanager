import { baseUrl } from '../../../../repositories/Client/AxiosClient'
const resource = `${baseUrl}/api/gome-long-meters/gome-long-credential`

import Client from '../../../../repositories/Client/AxiosClient'

export default {
    get() {
        return Client.get(`${resource}`)
    },
    put(credentials) {
        return Client.put(`${resource}`, credentials)
    },
}
