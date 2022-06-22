import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/kelin-meters/kelin-meter`

import Client from './Client/AxiosClient'

export default {
    list () {
        return Client.get(`${resource}`)
    },
    sync () {
        return Client.get(`${resource}/sync`)
    },
    syncCheck () {
        return Client.get(`${resource}/sync-check`)
    },

}