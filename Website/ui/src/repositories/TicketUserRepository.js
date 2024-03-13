import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/tickets/api`

export default {
    list() {
        return Client.get(`${resource}/users`)
    },

    create(user) {
        return Client.post(`${resource}/users`, user)
    },

    createExternal(user) {
        return Client.post(`${resource}/users/external`, user)
    },
}
