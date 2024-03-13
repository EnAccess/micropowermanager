import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/assets/rates`

export default {
    update(id, terms) {
        return Client.put(`${resource}/${id}`, terms)
    },
}
