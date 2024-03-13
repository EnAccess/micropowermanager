import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/settings`

export default {
    list() {
        return Client.get(`${resource}/main`)
    },
    update(id, mainSettings) {
        return Client.put(`${resource}/main/${id}`, mainSettings)
    },
}
