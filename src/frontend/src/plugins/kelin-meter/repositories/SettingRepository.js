import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/kelin-meters/kelin-setting`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
