import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/kelin-meters/kelin-setting/sync-setting`

import Client from './Client/AxiosClient'

export default {

    update(syncListPM) {
        return Client.put(`${resource}`, syncListPM)
    },

}