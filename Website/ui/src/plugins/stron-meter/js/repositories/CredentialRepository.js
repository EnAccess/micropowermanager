import { baseUrl } from '../../../../repositories/Client/AxiosClient'
const resource = `${baseUrl}/api/stron-meters/stron-credential`

import Client from '../../../../repositories/Client/AxiosClient'

export default {
    get() {
        return Client.get(`${resource}`)
    },
    put(credentials) {
        return Client.put(`${resource}`, credentials)
    },
    check() {
        return Client.get(`${resource}/check`)
    },
}
