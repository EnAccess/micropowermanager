import Client from '@/repositories/Client/AxiosClient'
import { baseUrl } from '@/repositories/Client/AxiosClient'

const resource = `${baseUrl}/api/agents/charge`

export default {
    create(newBalancePM, agentId) {
        return Client.post(`${resource}/${agentId}`, newBalancePM)
    },
}
