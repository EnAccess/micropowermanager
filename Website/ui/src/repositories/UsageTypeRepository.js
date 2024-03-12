import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/usage-types`

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
