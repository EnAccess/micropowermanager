import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/transaction-providers`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
