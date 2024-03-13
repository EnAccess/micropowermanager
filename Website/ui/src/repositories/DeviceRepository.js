import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/devices`

export default {
    update(deviceId, params) {
        return Client.put(`${resource}/${deviceId}`, params)
    },
    list() {
        return Client.get(`${resource}`)
    },
}
