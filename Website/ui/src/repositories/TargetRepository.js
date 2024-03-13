import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/targets`

export default {
    store(target) {
        return Client.post(`${resource}`, target)
    },
}
