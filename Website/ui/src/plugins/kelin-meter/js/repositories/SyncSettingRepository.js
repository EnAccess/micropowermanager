import { baseUrl } from '../../../../repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/kelin-meters/kelin-setting/sync-setting`

import Client from '../../../../repositories/Client/AxiosClient'

export default {

    update(syncListPM) {
        return Client.put(`${resource}`, syncListPM)
    },

}