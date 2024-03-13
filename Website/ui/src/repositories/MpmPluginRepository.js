import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

export const resource = `${baseUrl}/api/mpm-plugins`

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
