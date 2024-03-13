import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/tickets`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    detail(id) {
        return Client.get(`${resource}/${id}`)
    },
}
