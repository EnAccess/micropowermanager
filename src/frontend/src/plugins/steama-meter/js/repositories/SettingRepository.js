import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/steama-meters/steama-setting`

export default {
  list() {
    return Client.get(`${resource}`)
  },
}
