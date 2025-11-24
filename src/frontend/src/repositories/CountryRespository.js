import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/countries`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
