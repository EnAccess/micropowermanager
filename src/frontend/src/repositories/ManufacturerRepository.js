import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/manufacturers`

export default {
  list(type) {
    return Client.get(`${resource}`, { params: type ? { type } : {} })
  },
  search() {},
}
