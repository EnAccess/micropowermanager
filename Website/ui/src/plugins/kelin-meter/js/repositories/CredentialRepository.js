import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/kelin-meters/kelin-credential`

import Client from './Client/AxiosClient'

export default {
    get () {
        return Client.get(`${resource}`)
    },
    put (credentials) {
        return Client.put(`${resource}`, credentials)
    }
}
