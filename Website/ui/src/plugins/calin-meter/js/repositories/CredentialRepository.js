import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/calin-meters/calin-credential`

import Client from '@/repositories/Client/AxiosClient'

export default {
    get () {
        return Client.get(`${resource}`)
    },
    put (credentials) {
        return Client.put(`${resource}`, credentials)
    },
    check () {
        return Client.get(`${resource}/check`)
    }
}
