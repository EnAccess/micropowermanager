import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/agents/charge`

export default {
  create(balance) {
    return Client.post(`${resource}`, balance)
  },
}
