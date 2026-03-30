import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/steama-meters/steama-setting`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
