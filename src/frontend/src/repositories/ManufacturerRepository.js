import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/manufacturers`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  search() {},
}
