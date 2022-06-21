import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/steama-meters/steama-setting`

import Client from './Client/AxiosClient'

export default {
    list() {
        return Client.get(`${resource}`)
    },

}