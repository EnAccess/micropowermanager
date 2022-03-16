import Client from './Client/AxiosClient'

const resource = 'api/agents/assigned'
export default {

    list (agentId) {
        return Client.get(`${resource}/${agentId}`)
    },
    create(assignAppliancePm){
        return Client.post(`${resource}`,assignAppliancePm)
    }
}
