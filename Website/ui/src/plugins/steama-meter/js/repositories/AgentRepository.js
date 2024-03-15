import { baseUrl } from '../../../../repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/steama-meters/steama-agent`

import Client from '../../../../repositories/Client/AxiosClient'

export default {
    list() {
        return Client.get(`${resource}`)
    },
    sync() {
        return Client.get(`${resource}/sync`)
    },
    syncCheck() {
        return Client.get(`${resource}/sync-check`)
    },
    count() {
        return Client.get(`${resource}/count`)
    },
}
