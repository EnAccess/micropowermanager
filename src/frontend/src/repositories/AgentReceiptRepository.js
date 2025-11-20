import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/agents/receipt`

export default {
  list(agentId) {
    return Client.get(`${resource}/${agentId}`)
  },
  create(newReceipt) {
    return Client.post(`${resource}`, newReceipt)
  },
}
