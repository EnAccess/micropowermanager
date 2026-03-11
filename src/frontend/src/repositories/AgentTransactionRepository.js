import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/agents/transactions`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
