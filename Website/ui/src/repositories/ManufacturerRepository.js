import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/manufacturers`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    search() {},
}
