import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/assets/payment`

export default {
    update(id, data) {
        return Client.post(`${resource}/${id}`, data)
    },
}
