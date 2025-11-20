import Client from "@/repositories/Client/AxiosClient"

const resource = `/api/devices`

export default {
  update(deviceId, params) {
    return Client.put(`${resource}/${deviceId}`, params)
  },
  list() {
    return Client.get(`${resource}`)
  },
}
