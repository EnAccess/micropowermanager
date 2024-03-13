import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/agents/balance/history`

export default {
    list() {
        return Client.get(`${resource}`)
    },
}
