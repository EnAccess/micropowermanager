import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  importTransactions(data) {
    return Client.post(`${resource}/transactions`, { data })
  },
}
