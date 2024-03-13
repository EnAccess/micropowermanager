import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/transactions`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    analytics(period) {
        return Client.get(`${resource}/analytics/${period}`)
    },
    filteredList(term) {
        return Client.post(`${resource}/advanced`, term)
    },
    get(id) {
        return Client.get(`${resource}/${id}`)
    },
}
