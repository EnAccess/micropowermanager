import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/devices/geoinformation`

export default {
  update(params) {
    return Client.post(`${resource}`, params)
  },
}
