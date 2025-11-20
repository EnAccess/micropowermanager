import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/settings/currency-list`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
