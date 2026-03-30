import Client from "@/repositories/Client/AxiosClient.js"

const resource = `/api/devices`

export default {
  update(deviceId, params) {
    return Client.put(`${resource}/${deviceId}`, params)
  },
  list(params = {}) {
    return Client.get(`${resource}`, { params })
  },
}
