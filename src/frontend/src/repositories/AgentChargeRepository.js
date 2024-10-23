import Client from "@/repositories/Client/AxiosClient"
import { baseUrl } from "@/repositories/Client/AxiosClient"

const resource = `${baseUrl}/api/agents/charge`

export default {
  create(balance) {
    return Client.post(`${resource}`, balance)
  },
}
