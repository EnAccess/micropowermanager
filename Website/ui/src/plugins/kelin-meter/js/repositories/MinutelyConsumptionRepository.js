import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/kelin-meters/kelin-meter/minutely-consumptions`

import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    }
}