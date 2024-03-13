import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/device-addresses`

export default {
    update(params) {
        return Client.post(`${resource}`, params)
    },
}
