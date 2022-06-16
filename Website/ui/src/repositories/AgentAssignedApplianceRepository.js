
import Client from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/agents/assigned`

export default {

    list (agentId) {
        return Client.get(`${resource}/${agentId}`)
    },
    create(assignAppliancePm){
        return Client.post(`${resource}`,assignAppliancePm)
    }
}
