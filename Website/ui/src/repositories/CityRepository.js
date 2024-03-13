import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/cities`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    create(city) {
        return Client.post(`${resource}`, city)
    },
}
