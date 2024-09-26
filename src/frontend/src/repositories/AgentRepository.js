import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/agents`

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
  delete(agentId) {
    return Client.delete(`${resource}/${agentId}`)
  },
}
