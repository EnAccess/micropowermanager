import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/manufacturers`

export default {
  list() {
    return Client.get(`${resource}`)
  },
  search() {},
}
