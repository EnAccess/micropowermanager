import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/import`

export default {
  importCustomers(data) {
    return Client.post(`${resource}/customers`, { data })
  },
}
