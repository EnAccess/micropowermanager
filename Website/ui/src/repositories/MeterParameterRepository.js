import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/meters`

export default {
    update(meterId, params) {
        return Client.put(`${resource}/${meterId}/parameters/`, params)
    },
}
