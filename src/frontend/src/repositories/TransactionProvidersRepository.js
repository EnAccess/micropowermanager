import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/transaction-providers`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
