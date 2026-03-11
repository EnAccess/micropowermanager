import Client from "@/repositories/Client/AxiosClient.js"

const resource = "/api/sms-transaction-parser/transactions"

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
