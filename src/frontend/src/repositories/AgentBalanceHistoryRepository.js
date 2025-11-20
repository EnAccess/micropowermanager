import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/agents/balance/history`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
