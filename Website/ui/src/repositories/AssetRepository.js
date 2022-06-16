import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/assets/types`

export default {
    list(){
        return Client.get(`${resource}`)
    },
    create (asset) {
        return Client.post(`${resource}`, asset)
    },

    update (asset) {
        return Client.put(`${resource}/${asset.id}`, asset)
    },

    delete (id) {
        return Client.delete(`${resource}/${id}`)
    }
}
