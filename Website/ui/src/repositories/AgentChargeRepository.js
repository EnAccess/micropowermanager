import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/agents/charge`

export default {

    create (newBalancePM, agentId) {
        return Client.post(`${resource}/${agentId}`, newBalancePM)
    }
}
