import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/mini-grids`

export default {
    list(miniGridId) {
        return Client.get(`${resource}/${miniGridId}/devices`)
    },
}
