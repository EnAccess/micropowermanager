import Client from './Client/AxiosClient'

const resource = 'api/agents/charge'
export default {

    create (newBalancePM, agentId) {
        return Client.post(`${resource}/${agentId}`, newBalancePM)
    }
}
