import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/steama-meters/steama-transaction`

export default {
  sync() {
    return Client.get(`${resource}/sync`)
  },
}
