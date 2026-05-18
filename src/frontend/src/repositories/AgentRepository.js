import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/agents`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  create(agentPm) {
    return Client.post(`${resource}`, agentPm)
  },
  detail(agentId) {
    return Client.get(`${resource}/${agentId}`)
  },
  update(agent) {
    return Client.put(`${resource}/${agent.id}`, agent)
  },
  changePassword(agentId, payload) {
    return Client.put(`${resource}/${agentId}/password`, payload)
  },
  delete(agentId) {
    return Client.delete(`${resource}/${agentId}`)
  },
}
