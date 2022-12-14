import { baseUrl } from '../../../../repositories/Client/AxiosClient'
const resource = `${baseUrl}/api/sun-king-meters/sun-king-credential`

import Client from '../../../../repositories/Client/AxiosClient'
export default {
    get () {
        return Client.get(`${resource}`)
    },
    put (credentials) {
        return Client.put(`${resource}`, credentials)
    }
}
