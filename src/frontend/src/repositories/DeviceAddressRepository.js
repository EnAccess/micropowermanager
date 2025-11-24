import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/device-addresses`

export default {
  update(params) {
    return Client.post(`${resource}`, params)
  },
}
