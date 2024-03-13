import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/settings`

export default {
    list() {
        return Client.get(`${resource}/mail`)
    },
    update(id, mailSettings) {
        return Client.put(`${resource}/mail/${id}`, mailSettings)
    },
    create(mailSettings) {
        return Client.post(`${resource}/mail`, mailSettings)
    },
}
