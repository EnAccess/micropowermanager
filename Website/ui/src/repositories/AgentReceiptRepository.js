import Client  from './Client/AxiosClient'
import { baseUrl } from './Client/AxiosClient'

const resource = `${baseUrl}/api/agents/receipt`

export default {

    list(agentId){
        return Client.get(`${resource}/${agentId}`)

    },
    create(newReceipt, agentId){
        return Client.post(`${resource}/${agentId}`,newReceipt)

    }
}
