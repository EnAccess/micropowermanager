import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/usage-types`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
