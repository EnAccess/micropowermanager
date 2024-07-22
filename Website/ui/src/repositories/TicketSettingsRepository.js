import Client from '@/repositories/Client/AxiosClient'
import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/ticket-settings`

export default {
    list() {
        return Client.get(`${resource}`)
    },
    update(id, ticketSettings) {
        return Client.put(`${resource}/${id}`, ticketSettings)
    },
}
