import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/map-settings`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    update(id, mapSettings) {
        return Client.put(`${resource}/${id}`, mapSettings)
    },
    checkBingApiKey(key) {
        return Client.get(`${resource}/key/${key}`)
    },
}
