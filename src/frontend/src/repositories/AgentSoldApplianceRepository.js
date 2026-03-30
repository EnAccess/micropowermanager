import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/agents/sold`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
