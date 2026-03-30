import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/countries`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
