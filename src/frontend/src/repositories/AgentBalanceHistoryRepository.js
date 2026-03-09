import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/agents/balance/history`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
