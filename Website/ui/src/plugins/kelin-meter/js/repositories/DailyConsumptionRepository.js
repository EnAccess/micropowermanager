import { baseUrl } from '../../../../repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/kelin-meters/kelin-meter/daily-consumptions`

import Client from '../../../../repositories/Client/AxiosClient'

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
